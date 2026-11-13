<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisRequest;
use App\Analysis\CaseAnalyzer;
use App\Analysis\ContextVariant;
use App\Analysis\Tools\ShipmentStatusTool;
use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Knowledge\CustomerGroup;
use App\Models\LabelPrediction;
use App\Models\LabelTicket;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingException;
use App\Tracking\TrackingStatus;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;
use Laravel\Ai\Tools\Request;

function liveOrder(array $shipments): EocsOrder
{
    $now = CarbonImmutable::now('Europe/Berlin');

    return new EocsOrder(1, '303-0977429-2012311', 'Amazon', 'amazon', $now->subDays(5), 'Versendet', 'green', null, $shipments, [], [], 'https://eocs.test/orders/1');
}

function liveShipment(string $carrier = 'DHL', ?string $number = '003404340034123456'): EocsShipment
{
    return new EocsShipment($carrier, 'Paket', $number, CarbonImmutable::now()->subDays(2), false);
}

function liveRequest(array $orders = []): AnalysisRequest
{
    $ticket = new Ticket('2140364', 7, 'Betreff', 'open', false, null, null, 'Kunde', 'kunde@example.test', CarbonImmutable::now(), [], 'https://zammad.test/#ticket/zoom/7');
    $group = collect(CustomerGroup::all(config('knowledge.customer_groups')))->first();

    return new AnalysisRequest($ticket, 'Etienne', $group, [], $orders, [], '', ContextVariant::FullThread, null);
}

function livePrivate(string $method, AnalysisRequest $request, mixed ...$arguments): mixed
{
    return (new ReflectionMethod(CaseAnalyzer::class, $method))->invoke(app(CaseAnalyzer::class), $request, ...$arguments);
}

function liveTool(array $orders, ShipmentTracker $tracker): ShipmentStatusTool
{
    return new ShipmentStatusTool($orders, $tracker, '2140364');
}

it('puts no carrier status into the order section of the prompt', function () {
    $this->mock(ShipmentTracker::class)->shouldNotReceive('track');

    $text = livePrivate('orders', liveRequest([liveOrder([liveShipment()])]), false);

    expect($text)->not->toContain('Aktueller Status')->not->toContain('soeben beim Paketdienst')->toContain('Sendungsnummer 003404340034123456');
});

it('answers the delivered state with the tracking link', function () {
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(
        new TrackingStatus('DHL', '003404340034123456', true, 'Die Sendung wurde zugestellt.', at: '2026-11-13 11:02', delivered: true, deliveryDetails: 'In den Briefkasten gelegt', link: 'https://www.dhl.de/x?piececode=003404340034123456'),
    );

    $answer = (string) liveTool([liveOrder([liveShipment()])], $tracker)->handle(new Request);

    expect($answer)->toContain('303-0977429-2012311')->toContain('"delivered":true')->toContain('In den Briefkasten gelegt')->toContain('https://www.dhl.de/x?piececode=003404340034123456');
});

it('hands the pickup point on to the model', function () {
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(
        new TrackingStatus('UPS', '1Z1', true, 'Holen Sie Ihr Paket bald ab', pickupLocation: 'MYFLEXBOX 456, 128 LORY STRASSE, 1110 WIEN, AT', pickupReady: true, pickupDeadline: '14.11.2026', link: 'https://www.ups.com/track?tracknum=1Z1'),
    );

    expect((string) liveTool([liveOrder([liveShipment('UPS', '1Z1')])], $tracker)->handle(new Request))->toContain('"pickup_location":"MYFLEXBOX 456, 128 LORY STRASSE, 1110 WIEN, AT"')->toContain('"pickup_ready":true');
});

it('answers that the shipment is not yet known to the carrier', function () {
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(TrackingStatus::unknown('DHL', '003404340034123456', 'https://www.dhl.de/x'));

    expect((string) liveTool([liveOrder([liveShipment()])], $tracker)->handle(new Request))->toContain('noch nicht bekannt');
});

it('says so when the carrier cannot be asked', function () {
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andThrow(new TrackingException('DHL ist nicht erreichbar.'));

    expect((string) liveTool([liveOrder([liveShipment()])], $tracker)->handle(new Request))->toContain('nicht abrufbar');
});

it('says so for other carriers', function () {
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->once()->andReturn(null);

    expect((string) liveTool([liveOrder([liveShipment('Spedition')])], $tracker)->handle(new Request))->toContain('nicht abrufbar');
});

it('asks the carrier at most three times per order and only once per analysis', function () {
    $tracker = $this->mock(ShipmentTracker::class);
    $tracker->shouldReceive('track')->times(3)->andReturn(TrackingStatus::unknown('DHL', '1', null));
    $tool = liveTool([liveOrder(array_fill(0, 5, liveShipment()))], $tracker);

    $first = (string) $tool->handle(new Request);

    expect((string) $tool->handle(new Request))->toBe($first);
});

it('is offered only when an order has a shipment with a tracking number', function () {
    expect(ShipmentStatusTool::applies([liveOrder([liveShipment()])]))->toBeTrue()
        ->and(ShipmentStatusTool::applies([liveOrder([liveShipment('DHL', null)])]))->toBeFalse()
        ->and(ShipmentStatusTool::applies([liveOrder([])]))->toBeFalse()
        ->and(ShipmentStatusTool::applies([]))->toBeFalse();
});

it('hands the tools to the case agent', function () {
    $tool = liveTool([], $this->mock(ShipmentTracker::class));

    expect(iterator_to_array((new CaseAgent('p', [], [], [], [], [$tool]))->tools(), false))->toBe([$tool])
        ->and(iterator_to_array((new CaseAgent('p', [], [], []))->tools(), false))->toBe([]);
});

function classifiedTicket(?string $predicted, ?string $decision = null, ?string $split = null, string $state = 'classified'): void
{
    $ticket = LabelTicket::create(['zammad_id' => 7, 'number' => '2140364', 'state' => $state, 'text' => 'Wo ist mein Paket?', 'decision' => $decision, 'split' => $split ?? 'train']);

    if ($predicted !== null) {
        LabelPrediction::create(['label_ticket_id' => $ticket->id, 'category' => $predicted, 'unclear' => false, 'multiple_concerns' => false, 'confidence' => 0.9, 'model' => 'gpt-test', 'prompt_version' => 'labeling-test']);
    }
}

it('tells the model the classification of the first message', function () {
    classifiedTicket('wismo');

    expect(livePrivate('classificationLine', liveRequest()))->toContain('Einordnung der ersten Kundennachricht')->toContain('WISMO');
});

it('prefers the decision over the prediction', function () {
    classifiedTicket('wismo', 'reklamation');

    expect(livePrivate('classificationLine', liveRequest()))->toContain('Reklamation')->not->toContain('WISMO');
});

it('gives no classification for test tickets, unsure ones, unclassified ones and tickets without one', function (?string $predicted, ?string $decision, ?string $split, string $state) {
    classifiedTicket($predicted, $decision, $split, $state);

    expect(livePrivate('classificationLine', liveRequest()))->toBe('');
})->with([
    'test ticket' => ['wismo', null, 'test', 'classified'],
    'unsure' => [null, 'unsicher', null, 'classified'],
    'not classified yet' => ['wismo', null, null, 'new'],
]);

it('gives no classification without a stored ticket', function () {
    expect(livePrivate('classificationLine', liveRequest()))->toBe('');
});

it('tells the model to ask the carrier only when the delivery is the concern', function () {
    $prompt = file_get_contents(resource_path('prompts/analysis.md'));

    expect($prompt)->toContain('Rufe es nur auf, wenn die aktuelle Anfrage')
        ->toContain('Rufe es nicht auf bei Reklamationen')
        ->toContain('erwähne Versand, Zustellung und Sendungsstatus im Antwortentwurf')
        ->toContain('`pickup_location` einen Wert, nenne den Abholort samt Adresse wörtlich');
});

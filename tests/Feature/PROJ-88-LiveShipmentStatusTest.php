<?php

use App\Analysis\AnalysisRequest;
use App\Analysis\CaseAnalyzer;
use App\Analysis\ContextVariant;
use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Knowledge\CustomerGroup;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingException;
use App\Tracking\TrackingStatus;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;

function liveOrder(array $shipments): EocsOrder
{
    $now = CarbonImmutable::now('Europe/Berlin');

    return new EocsOrder(1, '303-0977429-2012311', 'Amazon', 'amazon', $now->subDays(5), 'Versendet', 'green', null, $shipments, [], [], 'https://eocs.test/orders/1');
}

function liveShipment(string $carrier = 'DHL', ?string $number = '003404340034123456'): EocsShipment
{
    return new EocsShipment($carrier, 'Paket', $number, CarbonImmutable::now()->subDays(2), false);
}

function livePromptOrders(EocsOrder $order): string
{
    $ticket = new Ticket('2140364', 1, 'Betreff', 'open', false, null, null, 'Kunde', 'kunde@example.test', CarbonImmutable::now(), [], 'https://zammad.test/#ticket/zoom/1');
    $group = collect(CustomerGroup::all(config('knowledge.customer_groups')))->first();
    $request = new AnalysisRequest($ticket, 'Etienne', $group, [], [$order], [], '', ContextVariant::FullThread, null);

    return (new ReflectionMethod(CaseAnalyzer::class, 'orders'))->invoke(app(CaseAnalyzer::class), $request, false);
}

it('tells the model the delivered state and the tracking link from the carrier', function () {
    $this->mock(ShipmentTracker::class)->shouldReceive('track')->once()->andReturn(
        new TrackingStatus('DHL', '003404340034123456', true, 'Die Sendung wurde zugestellt.', at: '2026-11-13 11:02', delivered: true, deliveryDetails: 'In den Briefkasten gelegt', link: 'https://www.dhl.de/x?piececode=003404340034123456'),
    );

    $text = livePromptOrders(liveOrder([liveShipment()]));

    expect($text)->toContain('Aktueller Status laut Paketdienst')
        ->toContain('"delivered":true')
        ->toContain('In den Briefkasten gelegt')
        ->toContain('https://www.dhl.de/x?piececode=003404340034123456');
});

it('tells the model that the shipment is not yet known to the carrier', function () {
    $this->mock(ShipmentTracker::class)->shouldReceive('track')->once()->andReturn(TrackingStatus::unknown('DHL', '003404340034123456', 'https://www.dhl.de/x'));

    expect(livePromptOrders(liveOrder([liveShipment()])))->toContain('noch nicht bekannt');
});

it('leaves out the status line when the query fails', function () {
    $this->mock(ShipmentTracker::class)->shouldReceive('track')->once()->andThrow(new TrackingException('DHL ist nicht erreichbar.'));

    $text = livePromptOrders(liveOrder([liveShipment()]));

    expect($text)->not->toContain('Aktueller Status laut Paketdienst')
        ->toContain('Sendungsnummer 003404340034123456');
});

it('leaves out the status line for other carriers', function () {
    $this->mock(ShipmentTracker::class)->shouldReceive('track')->once()->andReturn(null);

    expect(livePromptOrders(liveOrder([liveShipment('Spedition')])))->not->toContain('Aktueller Status laut Paketdienst');
});

it('does not query shipments without a tracking number', function () {
    $this->mock(ShipmentTracker::class)->shouldNotReceive('track');

    expect(livePromptOrders(liveOrder([liveShipment('DHL', null)])))->not->toContain('Aktueller Status laut Paketdienst');
});

it('queries at most three shipments per order', function () {
    $this->mock(ShipmentTracker::class)->shouldReceive('track')->times(3)->andReturn(TrackingStatus::unknown('DHL', '1', null));

    livePromptOrders(liveOrder(array_fill(0, 5, liveShipment())));
});

it('instructs the model to use the live status in the draft', function () {
    expect(file_get_contents(resource_path('prompts/analysis.md')))->toContain('Aktueller Status laut Paketdienst')->toContain('bereits zugestellt');
});

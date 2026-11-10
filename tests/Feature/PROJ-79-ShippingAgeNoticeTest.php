<?php

use App\Analysis\AnalysisRequest;
use App\Analysis\CaseAnalyzer;
use App\Analysis\ContextVariant;
use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Knowledge\CustomerGroup;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;

function shippedOrder(array $shippedDaysAgo, string $number = '303-0977429-2012311'): EocsOrder
{
    $now = CarbonImmutable::now('Europe/Berlin');
    $shipments = array_map(fn (int $days): EocsShipment => new EocsShipment('DHL', 'Paket', 'T1', $now->subDays($days)->setTime(9, 0), false), $shippedDaysAgo);

    return new EocsOrder(1, $number, 'Amazon', 'amazon', $now->subDays(40), 'Versendet', 'green', null, $shipments, [], [], 'https://eocs.test/orders/1');
}

function renderedNotice(EocsOrder $order, bool $showNumber = true): string
{
    return Blade::render('<x-ticket.shipping-age-notice :order="$order" :show-number="$showNumber" />', ['order' => $order, 'showNumber' => $showNumber]);
}

it('has no level below 14 days or without a shipment', function (array $days) {
    expect(shippedOrder($days)->shippingAgeLevel(CarbonImmutable::now()))->toBeNull();
})->with([[[]], [[0]], [[13]]]);

it('has the notice level from 14 up to 30 days and the strong level above', function (int $days, string $level) {
    expect(shippedOrder([$days])->shippingAgeLevel(CarbonImmutable::now()))->toBe($level);
})->with([[14, 'notice'], [30, 'notice'], [31, 'strong']]);

it('counts from the first shipment', function () {
    $order = shippedOrder([5, 20]);

    expect($order->daysSinceFirstShipment(CarbonImmutable::now()))->toBe(20)
        ->and($order->shippingAgeLevel(CarbonImmutable::now()))->toBe('notice');
});

it('shows nothing below 14 days and without a shipment', function () {
    expect(trim(renderedNotice(shippedOrder([13]))))->toBe('')
        ->and(trim(renderedNotice(shippedOrder([]))))->toBe('');
});

it('shows a warning with the shipping date and days between 14 and 30 days', function () {
    $html = renderedNotice(shippedOrder([21]));

    expect($html)->toContain('Der Versand liegt 21 Tage zurück')
        ->toContain(CarbonImmutable::now('Europe/Berlin')->subDays(21)->format('d.m.Y'))
        ->toContain('303-0977429-2012311')
        ->toContain('bg-warning-500/10')
        ->not->toContain('bg-danger-500/10');
});

it('shows the stronger notice above 30 days', function () {
    $html = renderedNotice(shippedOrder([40]));

    expect($html)->toContain('Der Versand liegt 40 Tage zurück')
        ->toContain('bg-danger-500/10')
        ->toContain('kaum noch als Mangel anzuerkennen');
});

it('omits the order number when asked to', function () {
    expect(renderedNotice(shippedOrder([21]), false))->not->toContain('303-0977429-2012311');
});

it('names no fixed deadline of the customer reply', function () {
    expect(renderedNotice(shippedOrder([21])))->not->toContain('14 Tage')
        ->and(renderedNotice(shippedOrder([40])))->not->toContain('14 Tage');
});

function promptOrders(array $orders): string
{
    $now = CarbonImmutable::now('Europe/Berlin');
    $ticket = new Ticket('2140331', 1, 'Betreff', 'open', false, null, null, 'Kunde', 'kunde@example.test', $now->subDays(1), [], 'https://zammad.test/#ticket/zoom/1');
    $group = collect(CustomerGroup::all(config('knowledge.customer_groups')))->first();
    $request = new AnalysisRequest($ticket, 'Etienne', $group, [], $orders, [], '', ContextVariant::FullThread, null);
    $method = new ReflectionMethod(CaseAnalyzer::class, 'orders');

    return $method->invoke(app(CaseAnalyzer::class), $request, false);
}

it('tells the model today and how many days ago the first shipment left', function () {
    $text = promptOrders([shippedOrder([5, 32])]);

    expect($text)->toContain('Heutiges Datum: '.CarbonImmutable::now('Europe/Berlin')->format('d.m.Y'))
        ->toContain('Die erste Sendung wurde vor 32 Tagen versandt.');
});

it('tells the model no days without a shipment', function () {
    $text = promptOrders([shippedOrder([])]);

    expect($text)->toContain('Heutiges Datum:')
        ->not->toContain('Die erste Sendung wurde vor')
        ->toContain('noch nicht versandt');
});

it('leaves out the date when there is no order', function () {
    expect(promptOrders([]))->not->toContain('Heutiges Datum');
});

test('POLICY-068 is a binding rule for everyone, triggered by damage words and without a condition on the shipping age', function () {
    config(['knowledge.path' => base_path('knowledge')]);
    app()->forgetScopedInstances();

    $library = app(\App\Knowledge\KnowledgeLibrary::class);
    $policy = $library->find('POLICY-068');

    expect($library->errors())->toBeEmpty()
        ->and($policy)->not->toBeNull()
        ->and($policy->retrieval())->toBe('binding')
        ->and($policy->customerTypes())->toBe([])
        ->and($policy->salesChannels())->toBe([])
        ->and($policy->products())->toBe([])
        ->and($policy->when())->toBe([])
        ->and($policy->triggers())->toContain('Kratzer', 'beschädig', 'damaged')
        ->and($policy->actions())->toContain('reproduction', 'reshipment')
        ->and($policy->relatedKnowledge())->toContain('POLICY-033', 'PERMISSION-004');
});

<?php

use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
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

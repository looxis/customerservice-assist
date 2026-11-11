<?php

use App\Eocs\EocsOrder;
use Carbon\CarbonImmutable;

function wismoOrder(?string $orderedAt, array $shipments = []): EocsOrder
{
    return new EocsOrder(1, '028-1058128-5149960', 'Amazon.de', 'amazon', $orderedAt === null ? null : CarbonImmutable::parse($orderedAt, 'Europe/Berlin'), 'In Produktion', 'blue', null, $shipments, [], [], 'https://eocs.test/orders/1');
}

it('counts only Monday to Friday between order date and today', function (): void {
    // Friday 2026-11-06 -> Wednesday 2026-11-11: Mon, Tue, Wed = 3 working days
    expect(wismoOrder('2026-11-06 10:00')->workdaysSinceOrdered(CarbonImmutable::parse('2026-11-11 09:00', 'Europe/Berlin')))->toBe(3);
});

it('flags an order without shipment after more than three working days', function (): void {
    expect(wismoOrder('2026-11-05 10:00')->stuckWorkdays(CarbonImmutable::parse('2026-11-11 09:00', 'Europe/Berlin')))->toBe(4)
        ->and(wismoOrder('2026-11-06 10:00')->stuckWorkdays(CarbonImmutable::parse('2026-11-11 09:00', 'Europe/Berlin')))->toBeNull();
});

it('does not flag without an order date', function (): void {
    expect(wismoOrder(null)->stuckWorkdays(CarbonImmutable::parse('2026-11-11', 'Europe/Berlin')))->toBeNull();
});

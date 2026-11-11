<?php

use App\Eocs\EocsOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;

function noticeOrder(string $orderedAt): EocsOrder
{
    return new EocsOrder(1, '028-1058128-5149960', 'Amazon.de', 'amazon', CarbonImmutable::parse($orderedAt, 'Europe/Berlin'), 'In Produktion', 'blue', null, [], [], [], 'https://eocs.test/orders/1');
}

test('the stuck-order notice shows for an old order without shipment', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-11 09:00', 'Europe/Berlin'));

    $html = Blade::render('<x-ticket.stuck-order-notice :order="$order" />', ['order' => noticeOrder('2026-11-04 10:00')]);

    expect($html)->toContain('Werktagen ohne Versand')->toContain('028-1058128-5149960')->toContain('role="alert"');
});

test('the stuck-order notice stays away for a young order', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-11 09:00', 'Europe/Berlin'));

    expect(trim(Blade::render('<x-ticket.stuck-order-notice :order="$order" />', ['order' => noticeOrder('2026-11-09 10:00')])))->toBe('');
});

test('the delivery-problem notice shows the reason and the time', function () {
    $html = Blade::render('<x-ticket.delivery-problem-notice :problem="$problem" />', ['problem' => ['reason' => 'Rücksendung an den Absender eingeleitet.', 'at' => '2026-11-11T08:30:00+00:00']]);

    expect($html)->toContain('Zustellproblem, bitte prüfen')->toContain('Rücksendung an den Absender eingeleitet.')->toContain('11.11.2026, 09:30 Uhr');
});

test('the delivery-problem notice stays away without a problem', function () {
    expect(trim(Blade::render('<x-ticket.delivery-problem-notice :problem="null" />')))->toBe('');
});

<?php

use App\Tracking\DeliveryText;
use App\Tracking\ShipmentHistory;
use App\Tracking\TrackingEvent;
use Carbon\CarbonImmutable;

/**
 * Real courses of tickets from November 2026 (texts of DHL and UPS, newest first as the carriers send them).
 *
 * @param  list<array{0: string, 1: string, 2?: string}>  $rows  [time, text, mark: delivered|failure]
 * @return list<TrackingEvent>
 */
function course(array $rows): array
{
    return array_map(fn (array $row): TrackingEvent => new TrackingEvent(CarbonImmutable::parse($row[0], 'Europe/Berlin'), $row[1], ($row[2] ?? '') === 'delivered', ($row[2] ?? '') === 'failure'), $rows);
}

it('reads a parcel that was not collected in time and came back to LOOXIS (ticket 2140425)', function () {
    $history = ShipmentHistory::from(course([
        ['2026-11-16 11:18', 'Die Sendung wurde zugestellt.', 'delivered'],
        ['2026-11-16 09:30', 'Die Sendung wurde in das Zustellfahrzeug geladen. Die Zustellung erfolgt voraussichtlich heute.'],
        ['2026-11-14 22:06', 'Die Sendung wurde zurückgestellt. Die Zustellung erfolgt vrs. am nächsten Werktag.'],
        ['2026-11-13 10:03', 'Der Empfänger hat die Sendung innerhalb der siebentägigen Lagerfrist nicht in der Filiale abgeholt.', 'failure'],
        ['2026-11-02 16:01', 'Sendung liegt in der Filiale zur Abholung bereit'],
        ['2026-10-31 10:22', 'Zustellung nicht möglich. Weiterleitung an Filiale'],
        ['2026-10-30 16:00', 'Ihre Sendung konnte heute leider wegen unerwarteter Umstände wie z.B. Krankheit, Unfall, Arbeitszeitüberschreitung nicht zugestellt werden. Ein neuer Zustellversuch erfolgt am nächsten Werktag.'],
        ['2026-10-29 05:25', 'Die Sendung ist in der Region des Empfängers angekommen und wird im nächsten Schritt zur Zustellbasis transportiert.'],
    ]));

    expect($history->case(true, true, false))->toBe(ShipmentHistory::RETURNED_NOT_COLLECTED)
        ->and($history->returnedToUs)->toBeTrue()->and($history->returnedOn)->toBe('16.11.2026')
        ->and($history->storageDays)->toBe(7)
        ->and(array_column($history->milestones, 'text'))->toBe([
            'Zustellung nicht möglich. Weiterleitung an Filiale',
            'Sendung liegt in der Filiale zur Abholung bereit',
            'Der Empfänger hat die Sendung innerhalb der siebentägigen Lagerfrist nicht in der Filiale abgeholt.',
            ShipmentHistory::BACK_AT_LOOXIS,
        ]);
});

it('reads an address the carrier could not find, also when DHL splits reason and return into two events (tickets 2140370, 2140513)', function (array $rows) {
    $history = ShipmentHistory::from(course($rows));

    expect($history->case(true, true, false))->toBe(ShipmentHistory::RETURNED_ADDRESS)->and($history->returnedToUs)->toBeTrue()->and($history->problem)->toContain('Empfänger');
})->with([
    'reason after return' => [[
        ['2026-11-10 11:06', 'Die Sendung wurde zugestellt.', 'delivered'],
        ['2026-11-04 12:55', 'Der Empfänger konnte unter der Adresse auf der Sendung nicht gefunden werden.', 'failure'],
        ['2026-11-04 12:55', 'Leider war eine Zustellung der Sendung nicht möglich. Die Sendung geht zurück zum Absender.', 'failure'],
    ]],
    'bell without name' => [[
        ['2026-11-16 11:18', 'Die Sendung wurde zugestellt.', 'delivered'],
        ['2026-11-13 15:44', 'Leider war eine Zustellung der Sendung nicht möglich. Die Sendung geht zurück zum Absender.', 'failure'],
        ['2026-11-13 15:44', 'Der Empfänger konnte nicht gefunden werden, da die Beschriftung der Klingel fehlte oder der Empfänger verzogen ist.', 'failure'],
    ]],
]);

it('tells an address error that waits at the carrier from one that went back (ticket 2140372)', function () {
    $waiting = course([
        ['2026-11-02 10:41', 'Die Sendung wurde im Zustell-Depot bearbeitet.'],
        ['2026-10-30 10:36', 'Aufgrund eines Adressfehlers konnte die Sendung nicht zugestellt werden und wird gelagert. Empfänger wurde benachrichtigt.', 'failure'],
    ]);
    $back = course([
        ['2026-11-16 11:18', 'Die Sendung wurde zugestellt.', 'delivered'],
        ['2026-11-09 09:47', 'Aufgrund eines Adressfehlers konnte die Sendung nicht zugestellt werden. Die Sendung wird an den Absender zurückgesandt.', 'failure'],
        ['2026-10-30 10:36', 'Aufgrund eines Adressfehlers konnte die Sendung nicht zugestellt werden und wird gelagert. Empfänger wurde benachrichtigt.', 'failure'],
    ]);

    expect(ShipmentHistory::from($waiting)->case(true, false, false))->toBe(ShipmentHistory::ADDRESS_HOLD)
        ->and(ShipmentHistory::from($back)->case(true, true, false))->toBe(ShipmentHistory::RETURNED_ADDRESS);
});

it('keeps a parcel that does not meet the shipping conditions for a person (ticket 2140443)', function () {
    $history = ShipmentHistory::from(course([
        ['2026-11-12 11:12', 'Die Sendung wurde zugestellt.', 'delivered'],
        ['2026-11-11 11:10', 'Leider ist eine Zustellung der Sendung nicht möglich, da sie nicht unseren Versandbedingungen entspricht. Die Sendung wird an den Absender zurückgeschickt.', 'failure'],
    ]));

    expect($history->case(true, true, false))->toBe(ShipmentHistory::SPECIAL)->and(ShipmentHistory::closable(ShipmentHistory::SPECIAL))->toBeFalse();
});

it('takes a surcharge that was paid and a later delivery as delivered (ticket 2140423)', function () {
    $history = ShipmentHistory::from(course([
        ['2026-11-02 14:28', 'Die Sendung wurde erfolgreich zugestellt.', 'delivered'],
        ['2026-10-28 04:56', 'Die Sendung liegt für den Empfänger zur Abholung bereit.'],
        ['2026-10-28 02:27', 'Die Sendung konnte aufgrund eines Nachentgelts nicht zugestellt werden und wird gelagert. Der Empfänger wurde benachrichtigt.', 'failure'],
    ]));

    expect($history->case(true, true, false))->toBe(ShipmentHistory::DELIVERED)->and($history->returned())->toBeFalse();
});

it('keeps a surcharge without a later step for a person', function () {
    $history = ShipmentHistory::from(course([
        ['2026-10-28 02:27', 'Die Sendung konnte aufgrund eines Nachentgelts nicht zugestellt werden und wird gelagert. Der Empfänger wurde benachrichtigt.', 'failure'],
    ]));

    expect($history->case(true, false, false))->toBe(ShipmentHistory::SPECIAL);
});

it('reads a missed attempt followed by a pickup point as ready for pickup, not as a problem (ticket 2140516)', function () {
    $history = ShipmentHistory::from(course([
        ['2026-11-16 10:12', 'Sendung liegt in der Filiale zur Abholung bereit'],
        ['2026-11-14 15:09', 'Zustellung nicht möglich. Abholinfos folgen per Brief'],
    ]));

    expect($history->case(true, false, true))->toBe(ShipmentHistory::PICKUP_READY)->and($history->problem)->toBeNull()->and($history->milestones)->toHaveCount(2);
});

it('reads the UPS course of an expired storage time and the return delivered to LOOXIS, whose receiver is never the customer (ticket 2140397)', function () {
    $history = ShipmentHistory::from(course([
        ['2026-11-17 11:56', 'Paket wurde an Absender zurückgesandt', 'delivered'],
        ['2026-11-13 19:52', 'Das Paket wurde bis zum Ablaufdatum nicht beim UPS Access Point™ Standort abgeholt und wird an den Absender zurückgesendet.'],
        ['2026-11-13 08:41', 'Die max. Aufbewahrungsdauer am UPS Access Point™ Standort ist abgelaufen. Paket w. zurückgesendet.'],
        ['2026-11-09 11:16', 'Holen Sie Ihr Paket bald ab, da es sich dem maximalen Aufbewahrungslimit nähert und eventuell an den Absender zurückgesendet wird.'],
        ['2026-11-05 15:23', 'An UPS Access Point™ zugestellt'],
        ['2026-11-05 11:14', 'Wir konnten ihr Paket nicht zustellen. Es ist auf dem Weg zu einem sicheren UPS Access Point™, wo Sie es abholen können.'],
        ['2026-11-05 03:06', 'In Einrichtung eingetroffen'],
    ]));

    expect($history->case(true, true, false))->toBe(ShipmentHistory::RETURNED_NOT_COLLECTED)
        ->and($history->returnedToUs)->toBeTrue()->and($history->returnedOn)->toBe('17.11.2026')
        ->and(collect($history->milestones)->pluck('text')->contains('In Einrichtung eingetroffen'))->toBeFalse();
});

it('takes the absence at the final attempt as the reason, not an earlier remark (ticket 2140362)', function () {
    $history = ShipmentHistory::from(course([
        ['2026-11-11 19:15', 'Der Empfänger war beim endgültigen Zustellversuch nicht anwesend. / Das Paket wird an den Absender zurückgesandt.'],
        ['2026-11-04 19:47', 'Der Empfänger war beim endgültigen Zustellversuch nicht anwesend.'],
        ['2026-11-03 15:29', 'Ihr Paket übersteigt den maximalen Umfang, den wir akzeptieren können.'],
    ]));

    expect($history->case(true, false, false))->toBe(ShipmentHistory::RETURNED_NOT_COLLECTED)->and($history->returnedToUs)->toBeFalse();
});

it('reads a refused parcel as a return', function () {
    $history = ShipmentHistory::from(course([['2026-11-10 10:00', 'Die Annahme der Sendung wurde verweigert.', 'failure']]));

    expect($history->case(true, false, false))->toBe(ShipmentHistory::RETURNED_REFUSED);
});

it('leaves a plain delivery and a parcel on its way alone', function () {
    $delivered = ShipmentHistory::from(course([['2026-11-19 12:12', 'Die Sendung wurde zugestellt.', 'delivered'], ['2026-11-19 09:55', 'Die Sendung wurde in das Zustellfahrzeug geladen.']]));
    $onTheWay = ShipmentHistory::from(course([['2026-11-19 09:55', 'Die Sendung wurde im Briefzentrum bearbeitet.']]));

    expect($delivered->case(true, true, false))->toBe(ShipmentHistory::DELIVERED)->and($delivered->milestones)->toHaveCount(1)
        ->and($onTheWay->case(true, false, false))->toBe(ShipmentHistory::IN_TRANSIT)->and($onTheWay->milestones)->toBe([])
        ->and(ShipmentHistory::from([])->case(false, false, false))->toBe(ShipmentHistory::UNKNOWN);
});

it('names the most serious case of several shipments', function () {
    expect(ShipmentHistory::worst([ShipmentHistory::DELIVERED, ShipmentHistory::RETURNED_ADDRESS, ShipmentHistory::IN_TRANSIT]))->toBe(ShipmentHistory::RETURNED_ADDRESS)
        ->and(ShipmentHistory::worst([ShipmentHistory::RETURNED_ADDRESS, ShipmentHistory::SPECIAL]))->toBe(ShipmentHistory::SPECIAL)
        ->and(ShipmentHistory::worst([]))->toBeNull();
});

it('reads the storage period from the text', function (string $text, ?int $days) {
    expect(DeliveryText::storageDays($text))->toBe($days);
})->with([
    ['Der Empfänger hat die Sendung innerhalb der siebentägigen Lagerfrist nicht in der Filiale abgeholt.', 7],
    ['Die Lagerfrist von 10 Tagen ist abgelaufen.', 10],
    ['Die max. Aufbewahrungsdauer am UPS Access Point™ Standort ist abgelaufen.', null],
]);

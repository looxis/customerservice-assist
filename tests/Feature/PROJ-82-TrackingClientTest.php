<?php

use App\Tracking\DhlTrackingClient;
use App\Tracking\TrackingException;
use App\Tracking\UpsTrackingClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config([
        'cache.default' => 'array',
        'services.dhl_tracking' => ['url' => 'https://dhl.test/track/shipments', 'key' => 'dhl-key', 'timeout' => 5],
        'services.ups' => ['url' => 'https://ups.test', 'client_id' => 'id', 'client_secret' => 'secret', 'timeout' => 5],
    ]);
    Http::preventStrayRequests();
});

function upsPackage(array $overrides = []): array
{
    return array_merge([
        'trackingNumber' => '1Z0000000000000001',
        'deliveryDate' => [],
        'currentStatus' => ['description' => 'Letzter Zustellversuch unternommen', 'code' => '024', 'simplifiedTextDescription' => 'Bei unserem letzten Zustellversuch konnten wir Ihr Paket nicht zustellen.'],
        'accessPointInformation' => ['companyName' => '', 'upsAccessPointLocationID' => 'U1', 'address' => ['addressLine1' => 'MUSTERPLATZ 1', 'addressLine2' => '', 'city' => 'MUSTERDORF', 'postalCode' => '1234', 'countryCode' => 'AT']],
        'activity' => [
            ['location' => ['address' => ['city' => 'Vienna', 'countryCode' => 'AT']], 'status' => ['type' => 'X', 'description' => 'Der Empfänger war beim endgültigen Zustellversuch nicht anwesend.', 'code' => '49'], 'date' => '20261104', 'time' => '194723'],
        ],
    ], $overrides);
}

function freshHttp(): void
{
    Http::swap(new Factory);
    Http::preventStrayRequests();
}

function fakeUps(array $package, int $status = 200): void
{
    freshHttp();
    Http::fake([
        'ups.test/security/v1/oauth/token' => Http::response(['access_token' => 'tok', 'expires_in' => '14399']),
        'ups.test/api/track/v1/details/*' => Http::response($status === 200 ? ['trackResponse' => ['shipment' => [['package' => [$package]]]]] : ['response' => ['errors' => [['code' => '151018']]]], $status),
    ]);
}

describe('UPS', function () {
    test('it reads the access point address from accessPointInformation when it is complete', function () {
        fakeUps(upsPackage());

        $status = app(UpsTrackingClient::class)->track('1Z0000000000000001', '1234');

        expect($status->carrier)->toBe('UPS')
            ->and($status->pickupLocation)->toBe('MUSTERPLATZ 1, 1234 MUSTERDORF, AT')
            ->and($status->delivered)->toBeFalse()
            ->and($status->problem)->toBeNull()
            ->and($status->at)->toBe('04.11.2026, 19:47 Uhr')
            ->and($status->link)->toBe('https://www.ups.com/track?loc=de_DE&tracknum=1Z0000000000000001');
    });

    test('an incomplete address is never passed off as the pickup point', function () {
        fakeUps(upsPackage(['accessPointInformation' => ['address' => ['city' => 'MUSTERDORF', 'postalCode' => '1234']]]));

        expect(app(UpsTrackingClient::class)->track('1Z0000000000000001', null)->pickupLocation)->toBeNull();
    });

    test('a pickup point is claimed ready only when the status says so', function () {
        fakeUps(upsPackage());
        expect(app(UpsTrackingClient::class)->track('1Z0000000000000001', null)->pickupReady)->toBeFalse();

        fakeUps(upsPackage(['currentStatus' => ['description' => 'Ihr Paket liegt zur Abholung bereit', 'simplifiedTextDescription' => '']]));
        expect(app(UpsTrackingClient::class)->track('1Z0000000000000001', null)->pickupReady)->toBeTrue();
    });

    test('a delivered shipment has no pickup point and carries the delivery', function () {
        fakeUps(upsPackage([
            'deliveryDate' => [['type' => 'DEL', 'date' => '20261105']],
            'activity' => [['location' => ['address' => ['city' => 'Wien']], 'status' => ['type' => 'D', 'description' => 'Zugestellt: Hinterlegt beim Nachbarn'], 'date' => '20261105', 'time' => '101500']],
            'currentStatus' => ['description' => 'Zugestellt', 'simplifiedTextDescription' => ''],
        ]));

        $status = app(UpsTrackingClient::class)->track('1Z0000000000000001', null);

        expect($status->delivered)->toBeTrue()->and($status->pickupLocation)->toBeNull()->and($status->deliveryDetails)->toContain('Nachbarn');
    });

    test('a return to the sender is a delivery problem and hides the pickup point', function () {
        fakeUps(upsPackage(['activity' => [['status' => ['type' => 'X', 'description' => 'Die Sendung wird an den Absender zurück gesendet (Rücksendung an Absender)'], 'date' => '20261105', 'time' => '101500']]]));

        $status = app(UpsTrackingClient::class)->track('1Z0000000000000001', null);

        expect($status->problem)->not->toBeNull()->and($status->pickupLocation)->toBeNull()->and($status->facts()['delivery_problem'])->toBeTrue()->and($status->facts())->not->toHaveKey('pickup_location');
    });

    test('a reminder to collect the parcel soon is no delivery problem and keeps the pickup point (ticket 2140398)', function () {
        fakeUps(upsPackage([
            'currentStatus' => ['description' => 'Holen Sie Ihr Paket bald ab, da es sich dem maximalen Aufbewahrungslimit nähert und eventuell an den Absender zurückgesendet wird.'],
            'accessPointInformation' => ['companyName' => 'MYFLEXBOX 456', 'address' => ['addressLine1' => '128 LORY STRASSE ', 'addressLine2' => '', 'city' => 'WIEN', 'postalCode' => '1110', 'countryCode' => 'AT']],
            'activity' => [['location' => ['address' => ['city' => 'Vienna']], 'status' => ['type' => 'X', 'description' => 'Holen Sie Ihr Paket bald ab, da es sich dem maximalen Aufbewahrungslimit nähert und eventuell an den Absender zurückgesendet wird.'], 'date' => '20261110', 'time' => '111849']],
        ]));

        $status = app(UpsTrackingClient::class)->track('1Z0000000000000001', null);

        expect($status->problem)->toBeNull()
            ->and($status->pickupLocation)->toBe('MYFLEXBOX 456, 128 LORY STRASSE, 1110 WIEN, AT')
            ->and($status->pickupReady)->toBeTrue()
            ->and($status->facts())->toHaveKey('pickup_location')->and($status->facts()['delivery_problem'])->toBeFalse();
    });

    test('once the storage time is over and the parcel goes back, it is a delivery problem again', function () {
        fakeUps(upsPackage([
            'currentStatus' => ['description' => 'Wird an Absender zurückgesendet'],
            'activity' => [
                ['status' => ['type' => 'X', 'description' => 'Die max. Aufbewahrungsdauer am UPS Access Point™ Standort ist abgelaufen. Paket w. zurückgesendet.'], 'date' => '20261113', 'time' => '144904'],
                ['status' => ['type' => 'X', 'description' => 'Holen Sie Ihr Paket bald ab, da es sich dem maximalen Aufbewahrungslimit nähert und eventuell an den Absender zurückgesendet wird.'], 'date' => '20261110', 'time' => '111849'],
            ],
        ]));

        $status = app(UpsTrackingClient::class)->track('1Z0000000000000001', null);

        expect($status->problem)->not->toBeNull()->and($status->pickupLocation)->toBeNull()->and($status->facts())->not->toHaveKey('pickup_location');
    });

    test('the token is fetched once and cached', function () {
        fakeUps(upsPackage());

        app(UpsTrackingClient::class)->track('1Z0000000000000001', null);
        app(UpsTrackingClient::class)->track('1Z0000000000000002', null);

        Http::assertSentCount(3);
    });

    test('an unknown number is a known state, a refused login is a permanent problem', function () {
        fakeUps([], 404);
        expect(app(UpsTrackingClient::class)->track('1Z0000000000000001', null)->known)->toBeFalse();

        Cache::flush();
        freshHttp();
        Http::fake(['ups.test/security/v1/oauth/token' => Http::response([], 401)]);
        expect(fn () => app(UpsTrackingClient::class)->track('1Z0000000000000001', null))->toThrow(fn (TrackingException $e): bool => $e->temporary === false);
    });

    test('a server error is a temporary problem', function () {
        freshHttp();
        Http::fake(['ups.test/security/v1/oauth/token' => Http::response(['access_token' => 'tok', 'expires_in' => '100']), 'ups.test/api/*' => Http::response([], 503)]);

        expect(fn () => app(UpsTrackingClient::class)->track('1Z0000000000000001', null))->toThrow(fn (TrackingException $e): bool => $e->temporary === true);
    });
});

describe('DHL', function () {
    test('it sends the key and the postcode and reads a delivered shipment', function () {
        Http::fake(['dhl.test/*' => Http::response(['shipments' => [[
            'status' => ['statusCode' => 'delivered', 'description' => 'Die Sendung wurde zugestellt'],
            'details' => ['receiver' => ['name' => 'Frau Beispiel']],
            'events' => [['statusCode' => 'delivered', 'description' => 'Die Sendung wurde zugestellt', 'remark' => 'Zustellung an den Nachbarn', 'timestamp' => '2026-11-05T10:15:00+01:00', 'location' => ['address' => ['addressLocality' => 'Oberhausen']]]],
        ]]])]);

        $status = app(DhlTrackingClient::class)->track('00340434171079146248', '46049');

        expect($status->delivered)->toBeTrue()->and($status->receivedBy)->toBe('Frau Beispiel')->and($status->at)->toBe('05.11.2026, 10:15 Uhr')->and($status->location)->toBe('Oberhausen');
        Http::assertSent(fn ($request) => $request->hasHeader('DHL-API-Key', 'dhl-key') && $request['recipientPostalCode'] === '46049' && $request['trackingNumber'] === '00340434171079146248');
    });

    test('it names a pickup point from the latest event, but never when a problem was found', function () {
        $events = fn (string $description) => ['shipments' => [['status' => ['statusCode' => 'transit'], 'events' => [['statusCode' => 'transit', 'description' => $description, 'remark' => 'Packstation 123, Musterstraße 1, 12345 Musterstadt', 'timestamp' => '2026-11-05T10:15:00+01:00']]]]];

        Http::fake(['dhl.test/*' => Http::response($events('Die Sendung liegt zur Abholung in der Packstation bereit'))]);
        $status = app(DhlTrackingClient::class)->track('1', null);
        expect($status->pickupReady)->toBeTrue()->and($status->pickupLocation)->toContain('Packstation 123');

        freshHttp();
        Http::fake(['dhl.test/*' => Http::response($events('Die Sendung wird an den Absender zurück gesendet'))]);
        expect(app(DhlTrackingClient::class)->track('1', null)->facts())->not->toHaveKey('pickup_location');
    });

    test('a 404 is an unknown shipment and a refused key a permanent problem', function () {
        Http::fake(['dhl.test/*' => Http::response([], 404)]);
        expect(app(DhlTrackingClient::class)->track('1', null)->known)->toBeFalse();

        freshHttp();
        Http::fake(['dhl.test/*' => Http::response([], 401)]);
        expect(fn () => app(DhlTrackingClient::class)->track('1', null))->toThrow(fn (TrackingException $e): bool => $e->temporary === false);
    });

    test('without a key nothing is asked', function () {
        config(['services.dhl_tracking.key' => null]);

        expect(fn () => app(DhlTrackingClient::class)->track('1', null))->toThrow(TrackingException::class);
        Http::assertNothingSent();
    });
});

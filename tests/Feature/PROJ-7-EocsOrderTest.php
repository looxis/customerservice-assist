<?php

use App\Http\Requests\AddOrderRequest;
use App\Orders\OrderNumberDetector;
use App\Orders\OrderNumberFormat;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.zammad.timeout' => 10,
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => '7|geheim', 'services.eocs.timeout' => 10,
    ]);
});

/**
 * A fictitious EOCS order with customer data that must never reach the page.
 *
 * @return array<string, mixed>
 */
function eocsOrder(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 700001,
        'external_order_id' => '402-0000000-0000001',
        'order_date' => '2026-09-09T13:06:53.000000Z',
        'status_name' => 'Vollständig und verschickt',
        'state' => ['name' => 'Complete', 'color' => 'green'],
        'ordered_at' => 'Amazon.it',
        'client' => ['id' => 106, 'name' => 'Amazon.it', 'sales_platform' => ['id' => 7, 'name' => 'Amazon']],
        'origin_order_id' => null,
        'name' => 'Geheime Kundin',
        'customer_email' => 'geheim@example.org',
        'shipping' => ['street' => 'Geheimweg 1', 'city' => 'Geheimstadt', 'telephone' => '0123456789'],
        'billing' => ['total' => 24.9, 'payment_method' => 'amazon'],
        'invoice' => ['id' => 500001, 'document_number' => 'LX26-00001'],
        'shipments' => ['data' => [['carrier' => ['name' => 'DHL'], 'shipping_method' => ['name' => 'DHL PAKET'], 'tracking_no' => 'TRACK123456DE', 'created_at' => '10.09.2026 11:54', 'delivered' => null]]],
        'order_items' => ['data' => [['quantity' => 2, 'status_name' => 'Vollständig', 'custom_code' => 'cfg123', 'custom_code_url' => 'https://configurator.example/cfg123', 'item' => ['item_id' => 11281, 'name' => 'Fototasse Schwarz ', 'product_type' => 'Tasse'], 'data' => ['asin' => 'B000TEST01', 'customizationData' => ['text' => 'Hallo']]]]],
    ], $overrides);
}

/**
 * Fake EOCS: exact searches by external number or id answer from the given orders.
 *
 * @param  list<array<string, mixed>>  $orders
 */
function fakeEocs(array $orders = []): void
{
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) use ($orders) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $filter = $query['filter'] ?? [];

            $matches = array_values(array_filter($orders, function (array $order) use ($filter): bool {
                if (isset($filter['id'])) {
                    return (string) $order['id'] === (string) $filter['id'];
                }

                [$number] = explode(',', (string) ($filter['external_order_id'] ?? ''));

                return $order['external_order_id'] === $number;
            }));

            return Http::response(['data' => $matches, 'meta' => []]);
        },
    ]);
}

function ticketWith(string $body): void
{
    fakeZammad([zammadArticle(['body' => $body])]);
}

describe('order number formats', function () {
    test('typed numbers are cleaned up and recognised', function (string $input, ?string $expected, ?OrderNumberFormat $format) {
        $number = app(OrderNumberDetector::class)->normalize($input);

        expect($number?->value)->toBe($expected)
            ->and($number?->format)->toBe($format);
    })->with([
        'amazon' => ['402-4907715-1581912', '402-4907715-1581912', OrderNumberFormat::Amazon],
        'amazon with spaces and hash' => ["  #402-4907715-1581912\n", '402-4907715-1581912', OrderNumberFormat::Amazon],
        'looxis.de' => ['7JI-0WC1-6M49', '7JI-0WC1-6M49', OrderNumberFormat::Vanilo],
        'looxis.de lower case' => ['7ji-0wc1-6m49', '7JI-0WC1-6M49', OrderNumberFormat::Vanilo],
        'looxis-pro' => ['30019578', '30019578', OrderNumberFormat::LooxisPro],
        'looxis-pro with prefix' => ['BEST-PRO30019578', '30019578', OrderNumberFormat::LooxisPro],
        'looxis.fr' => ['700411247', '700411247', OrderNumberFormat::LooxisFr],
        'masterpics keeps case' => ['LmagQ9PV25', 'LmagQ9PV25', OrderNumberFormat::Masterpics],
        'eocs id' => ['753194', '753194', OrderNumberFormat::EocsId],
        'unknown' => ['12345', null, null],
        'looxis.fr too short' => ['70041124', null, null],
        'words only' => ['Bestellung', null, null],
        'too long' => ['402-4907715-15819123', null, null],
    ]);

    test('numbers in the thread are suggested once each in order of appearance, amazon notice first', function () {
        fakeZammad([
            zammadArticle(['id' => 1, 'body' => '<p>Meine Bestellungen 7JI-0WC1-6M49 und 30019578, nochmal 7JI-0WC1-6M49.</p>']),
            zammadArticle(['id' => 2, 'body' => amazonNotice('Frage', '402-0000000-0000001'), 'created_at' => '2026-10-02T07:00:00.000Z']),
            zammadArticle(['id' => 3, 'sender' => 'Agent', 'from' => 'Nele', 'body' => '<p>Danke.</p><p>Am 01.10.2026 schrieb X:</p><blockquote>Auftrag 700411247 und LmagQ9PV25</blockquote>', 'created_at' => '2026-10-03T07:00:00.000Z']),
        ]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder([
            '402-0000000-0000001', 'Amazon · aus Amazon-Nachricht', 'Bestelldetails aus EOCS abrufen',
            '7JI-0WC1-6M49', 'looxis.de / Fachhändler · im Ticket gefunden',
            '30019578', 'LOOXIS-Pro',
            '700411247', 'looxis.fr',
            'LmagQ9PV25', 'masterpics',
            'Andere Bestellnummer eingeben',
        ]);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'eocs.test'));
    });

    test('a number in the ticket title is suggested too, before the thread', function () {
        fakeZammad([zammadArticle(['body' => '<p>Bestellnummer Tasse: 7JJ-0LQH-1RB6</p>'])], ['title' => 'Bestellung 7JJ-0LFR-2GU7']);

        $this->get('/tickets/2137942')->assertSee('aria-label="Bestellung 7JJ-0LFR-2GU7"', false)
            ->assertSeeInOrder(['aria-label="Bestellung 7JJ-0LFR-2GU7"', 'aria-label="Bestellung 7JJ-0LQH-1RB6"'], false);
    });

    test('tracking numbers, phone numbers, postcodes and links are not suggested', function () {
        ticketWith('<p>Sendung CM983471129DE, Tracking 00340434171079990018, Tel. 0031626176737, PLZ 32423, Code ABCDEFGHIJ, <a href="https://x.example/?o=402-4907715-1581912">Link</a>, Wort Bestellung1</p>');

        $this->get('/tickets/2137942')
            ->assertSeeText('Im Ticket wurde keine Bestellnummer gefunden.')
            ->assertSeeText('Bestellnummer von Hand eingeben')
            ->assertDontSeeText('Andere Bestellnummer eingeben')
            ->assertDontSee('aria-label="Bestellung', false);
    });

    test('at most ten suggestions are shown', function () {
        $numbers = implode(' ', array_map(fn (int $i): string => sprintf('402-0000000-%07d', $i), range(1, 12)));
        ticketWith("<p>{$numbers}</p>");

        $html = $this->get('/tickets/2137942')->assertSeeText('Weitere Bestellnummern bitte von Hand eintragen.')->getContent();

        expect($html)->toContain('aria-label="Bestellung 402-0000000-0000010"')->not->toContain('aria-label="Bestellung 402-0000000-0000011"');
    });
});

describe('loading orders', function () {
    test('nothing is loaded from eocs until the employee chooses a number', function () {
        ticketWith('<p>Bestellung 402-0000000-0000001</p>');
        Http::assertNothingSent();

        $this->get('/tickets/2137942')->assertOk();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'eocs.test'));
    });

    test('a suggestion links to the ticket address with the order added', function () {
        ticketWith('<p>Bestellung 402-0000000-0000001</p>');

        $this->get('/tickets/2137942')
            ->assertSee('href="'.e(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']])).'"', false);
    });

    test('a chosen order shows number, eocs id, channel, date, status, invoice and shipment', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder()]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertOk()
            ->assertSeeTextInOrder([
                '402-0000000-0000001', 'Amazon.it', 'EOCS-ID 700001', 'Vollständig und verschickt',
                'Bestellt', '09.09.2026', 'Rechnungsnummer', 'LX26-00001',
                'Versand', 'DHL', 'TRACK123456DE', '10.09.2026',
                'In EOCS öffnen', 'Entfernen',
            ])
            ->assertSee('href="https://eocs.test/orders/700001" target="_blank"', false)
            ->assertSee('bg-success-500/10', false);
    });

    test('the request to eocs uses the exact search, includes and the bearer token', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder()]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]));

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://eocs.test/api/v1/orders')
                && ($query['filter']['external_order_id'] ?? null) === '402-0000000-0000001,exact'
                && ($query['include'] ?? null) === 'shipments,order_items'
                && $request->hasHeader('Authorization', 'Bearer 7|geheim');
        });
    });

    test('an eocs id is searched by id', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder(['id' => 753194])]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['753194']]))->assertSeeText('EOCS-ID 753194');

        Http::assertSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'filter[id]=753194'));
    });

    test('customer data from eocs never reaches the page', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder()]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertDontSee('Geheime Kundin')
            ->assertDontSee('geheim@example.org')
            ->assertDontSee('Geheimweg')
            ->assertDontSee('0123456789')
            ->assertDontSee('7|geheim');
    });

    test('several orders appear as separate blocks, each removable', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder(), eocsOrder(['id' => 700002, 'external_order_id' => '7JI-0WC1-6M49', 'client' => ['name' => 'looxis.de Vanilo', 'sales_platform' => ['name' => 'Looxis Vanilo Cloud']]])]);

        $html = $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001', '7JI-0WC1-6M49']]))
            ->assertSeeTextInOrder(['402-0000000-0000001', 'Amazon.it', '7JI-0WC1-6M49', 'looxis.de Vanilo'])
            ->getContent();

        expect($html)->toContain('href="'.e(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['7JI-0WC1-6M49']])).'"')
            ->toContain('href="'.e(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']])).'"');
    });

    test('invalid entries in the address are ignored, duplicates count once, at most ten', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder()]);

        $numbers = ['402-0000000-0000001', '402-0000000-0000001', 'quatsch', ...array_map(fn (int $i): string => sprintf('402-1111111-%07d', $i), range(1, 12))];

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => $numbers]))->assertOk();

        // Zammad: search, articles, customer; EOCS: ten orders, one follow-up check for the one found.
        Http::assertSentCount(3 + 10 + 1);
    });

    test('an amazon order from the notice is merged with the eocs order into one block', function () {
        fakeZammad([zammadArticle(['body' => amazonNotice('Frage', '402-0000000-0000001', [['B000TEST01', 'Zaubertasse schwarz']])])]);
        fakeEocs([eocsOrder()]);

        $html = $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertSeeTextInOrder(['402-0000000-0000001', 'Amazon.it', 'LX26-00001', 'Zaubertasse schwarz', 'ASIN B000TEST01'])
            ->assertDontSee('aria-label="Im Ticket gefundene Bestellungen"', false)
            ->getContent();

        expect(substr_count($html, 'aria-label="Bestellung 402-0000000-0000001"'))->toBe(1);
    });

    test('several orders for one number are all shown with a hint', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder(), eocsOrder(['id' => 700009])]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertSeeText('Mehrere Bestellungen zu 402-0000000-0000001:')
            ->assertSeeText('EOCS-ID 700001')
            ->assertSeeText('EOCS-ID 700009');
    });

    test('an order without shipment says not shipped yet', function () {
        ticketWith('<p>Hallo</p>');
        $order = eocsOrder();
        $order['shipments']['data'] = [];
        fakeEocs([$order]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->assertSeeText('noch nicht versandt');
    });
});

describe('complaint orders', function () {
    test('follow-up orders pointing back to the order are listed in its block', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([
            eocsOrder(),
            eocsOrder(['id' => 700101, 'external_order_id' => 'R1-402-0000000-0000001', 'origin_order_id' => 700001, 'order_date' => '2026-09-22T08:00:00.000Z', 'status_name' => 'In Verarbeitung', 'state' => ['color' => 'orange']]),
            eocsOrder(['id' => 700102, 'external_order_id' => 'R2-402-0000000-0000001', 'origin_order_id' => 999999]),
        ]);

        $html = $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertSeeTextInOrder(['Reklamationsaufträge', 'R1-402-0000000-0000001', 'vom 22.09.2026', 'In Verarbeitung', 'In EOCS öffnen'])
            ->assertDontSeeText('R2-402-0000000-0000001')
            ->getContent();

        expect($html)->toContain('href="https://eocs.test/orders/700101"');
    });

    test('follow-up orders are asked one first, then in batches of five until there are no more', function () {
        ticketWith('<p>Hallo</p>');
        $claims = array_map(fn (int $n): array => eocsOrder(['id' => 700100 + $n, 'external_order_id' => "R{$n}-402-0000000-0000001", 'origin_order_id' => 700001]), range(1, 6));
        fakeEocs([eocsOrder(), ...$claims]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertSeeText('R6-402-0000000-0000001');

        Http::assertSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'R11-402-0000000-0000001,exact'));
        Http::assertNotSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'R12-'));
    });

    test('without follow-up orders only R1 is asked', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([eocsOrder()]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->assertDontSeeText('Reklamationsaufträge');

        Http::assertNotSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'R2-'));
    });
});

describe('typing in a number', function () {
    test('a typed number is cleaned up and added to the selection', function () {
        $this->get(route('tickets.orders.add', ['number' => '2137942', 'bestellnummer' => ' 7ji-0wc1-6m49 ', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertRedirect(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001', '7JI-0WC1-6M49']]));
    });

    test('an unknown format goes back with the message at the field and keeps the selection', function () {
        $this->get(route('tickets.orders.add', ['number' => '2137942', 'bestellnummer' => '12345', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertRedirect(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertSessionHasErrors(['bestellnummer' => AddOrderRequest::MESSAGE]);
    });

    test('the message appears at the field', function () {
        ticketWith('<p>Hallo</p>');

        $this->followingRedirects()
            ->get(route('tickets.orders.add', ['number' => '2137942', 'bestellnummer' => 'quatsch']))
            ->assertSeeText(AddOrderRequest::MESSAGE)
            ->assertSee('aria-invalid="true"', false);
    });

    test('an empty input asks for a number without asking eocs', function () {
        Http::fake();

        $this->get(route('tickets.orders.add', ['number' => '2137942', 'bestellnummer' => '']))->assertSessionHasErrors(['bestellnummer' => AddOrderRequest::EMPTY]);

        Http::assertNothingSent();
    });
});

describe('errors', function () {
    test('an unknown number shows a hint with a remove link, the ticket stays', function () {
        ticketWith('<p>Hallo</p>');
        fakeEocs([]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000009']]))
            ->assertOk()
            ->assertSeeText('Bestellung 402-0000000-0000009 wurde in EOCS nicht gefunden.')
            ->assertSeeText('Hallo');
    });

    test('eocs problems show one message, the ticket and suggestions stay', function (Closure $fake, string $message, bool $retry) {
        fakeZammad([zammadArticle(['body' => '<p>Bestellung 402-0000000-0000001</p>'])]);
        $fake();

        $response = $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertOk()
            ->assertSeeText($message)
            ->assertSeeText('Bestellung 402-0000000-0000001')
            ->assertSeeText('Andere Bestellnummer eingeben');

        $retry ? $response->assertSeeText('Erneut versuchen') : $response->assertDontSeeText('Erneut versuchen');
    })->with([
        'unreachable' => [fn () => Http::fake(['eocs.test/*' => fn () => throw new ConnectionException('timeout')]), 'EOCS ist gerade nicht erreichbar.', true],
        'server error' => [fn () => Http::fake(['eocs.test/*' => Http::response('x', 500)]), 'EOCS ist gerade nicht erreichbar.', true],
        'invalid token' => [fn () => Http::fake(['eocs.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]), 'Die Verbindung zu EOCS ist nicht eingerichtet oder ungültig.', false],
    ]);

    test('missing eocs configuration is reported without asking eocs', function () {
        ticketWith('<p>Hallo</p>');
        config(['services.eocs.token' => null]);

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))
            ->assertSeeText('Die Verbindung zu EOCS ist nicht eingerichtet oder ungültig.');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'eocs.test'));
    });

    test('the log names number and problem but no order or customer data', function () {
        ticketWith('<p>Hallo</p>');
        Http::fake(['eocs.test/*' => Http::response(['message' => 'x'], 401)]);
        Log::spy();

        $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]));

        Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $message === 'EOCS request failed' && $context === ['order' => '402-0000000-0000001', 'problem' => 'misconfigured', 'status' => 401]);
    });
});

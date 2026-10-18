<?php

use App\Analysis\AnalysisStore;
use App\Models\TicketOrderMemory;
use App\Orders\OrderNumberDetector;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.zammad.timeout' => 10,
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => '7|geheim', 'services.eocs.timeout' => 10,
        'staff.admins' => ['Etienne'],
    ]);
});

/**
 * A fictitious EOCS order (as far as the app reads it).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function rememberedEocsOrder(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 700001,
        'external_order_id' => '402-0000000-0000001',
        'order_date' => '2026-09-09T13:06:53.000000Z',
        'status_name' => 'Vollständig und verschickt',
        'state' => ['name' => 'Complete', 'color' => 'green'],
        'ordered_at' => 'Fachhändler',
        'client' => ['id' => 106, 'name' => 'Fachhändler', 'sales_platform' => ['id' => 7, 'name' => 'Shop']],
        'origin_order_id' => null,
        'name' => 'Geheime Kundin',
        'customer_email' => 'geheim@example.org',
        'shipping' => ['street' => 'Geheimweg 1', 'city' => 'Geheimstadt', 'telephone' => '0123456789'],
        'billing' => ['total' => 24.9, 'payment_method' => 'mollie'],
        'invoice' => ['id' => 500001, 'document_number' => 'LX26-00001'],
        'shipments' => ['data' => []],
        'order_items' => ['data' => []],
    ], $overrides);
}

function rememberedOrderFixture(array $orders): void
{
    fakeZammad([zammadArticle(['body' => '<p>Bitte stellen Sie um.</p>'])]);
    Http::fake([
        'eocs.test/api/v1/orders*' => function ($request) use ($orders) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => array_values(array_filter($orders, fn (array $order): bool => $order['external_order_id'] === $number))]);
        },
    ]);
}

describe('a number written directly in front of a word', function () {
    test('is still recognised when it cannot contain lowercase letters', function (string $text, array $expected) {
        expect(array_map(fn ($order) => $order->value, app(OrderNumberDetector::class)->inText($text)))->toBe($expected);
    })->with([
        'upper-case format' => ['BestNr.: 7JQ-18SU-DVA4bitte', ['7JQ-18SU-DVA4']],
        'digit format' => ['Bestellung 302-1234567-1234567danke', ['302-1234567-1234567']],
        'normal' => ['7JQ-18SU-DVA4', ['7JQ-18SU-DVA4']],
        'more capital letters stay part of the word' => ['7JQ-18SU-DVA4X', []],
        'glued in front' => ['X7JQ-18SU-DVA4', []],
        'format with lowercase letters is not loosened' => ['abcDEF1234xyz', []],
    ]);
});

describe('order numbers typed in by hand', function () {
    test('are remembered for the ticket when EOCS knows them, and come back without the address', function () {
        rememberedOrderFixture([rememberedEocsOrder(['external_order_id' => '7JQ-18SU-DVA4', 'id' => 700555])]);

        $this->get('/tickets/2137942')->assertOk();
        expect(TicketOrderMemory::query()->count())->toBe(0);

        $this->get('/tickets/2137942?bestellungen[0]=7JQ-18SU-DVA4')->assertOk();
        expect(app(AnalysisStore::class)->rememberedOrders('2137942'))->toBe(['7JQ-18SU-DVA4']);

        $html = $this->get('/tickets/2137942')->assertOk()->getContent();
        expect($html)->toContain('7JQ-18SU-DVA4');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'eocs.test') && str_contains(urldecode($request->url()), '7JQ-18SU-DVA4'));
    });

    test('a number EOCS does not know is not remembered', function () {
        rememberedOrderFixture([]);

        $this->get('/tickets/2137942?bestellungen[0]=7JQ-18SU-DVA4')->assertOk();

        expect(TicketOrderMemory::query()->count())->toBe(0);
    });

    test('a number the app recognised in the ticket on its own is not remembered', function () {
        rememberedOrderFixture([rememberedEocsOrder(['external_order_id' => '7JQ-18SU-DVA4', 'id' => 700555])]);
        fakeZammad([zammadArticle(['body' => '<p>Bestellung 7JQ-18SU-DVA4</p>'])]);
        Http::fake(['eocs.test/api/v1/orders*' => Http::response(['data' => [rememberedEocsOrder(['external_order_id' => '7JQ-18SU-DVA4', 'id' => 700555])]])]);

        $this->get('/tickets/2137942')->assertOk();

        expect(TicketOrderMemory::query()->count())->toBe(0);
    });

    test('dropping a number from the selection forgets it', function () {
        rememberedOrderFixture([rememberedEocsOrder(['external_order_id' => '7JQ-18SU-DVA4', 'id' => 700555])]);
        $this->get('/tickets/2137942?bestellungen[0]=7JQ-18SU-DVA4')->assertOk();

        $this->get('/tickets/2137942?bestellungen[0]=')->assertOk();

        expect(app(AnalysisStore::class)->rememberedOrders('2137942'))->toBe([]);
    });

    test('deleting the analyses of a ticket does not make the app forget the number', function () {
        rememberedOrderFixture([rememberedEocsOrder(['external_order_id' => '7JQ-18SU-DVA4', 'id' => 700555])]);
        $this->get('/tickets/2137942?bestellungen[0]=7JQ-18SU-DVA4')->assertOk();

        app(AnalysisStore::class)->deleteTicket('2137942', 'Etienne');

        expect(app(AnalysisStore::class)->rememberedOrders('2137942'))->toBe(['7JQ-18SU-DVA4']);
    });

    test('the memory is dropped after the retention period', function () {
        rememberedOrderFixture([rememberedEocsOrder(['external_order_id' => '7JQ-18SU-DVA4', 'id' => 700555])]);
        $this->get('/tickets/2137942?bestellungen[0]=7JQ-18SU-DVA4')->assertOk();

        $this->travel(13)->months();
        app(AnalysisStore::class)->purge();

        expect(TicketOrderMemory::query()->count())->toBe(0);
    });
});

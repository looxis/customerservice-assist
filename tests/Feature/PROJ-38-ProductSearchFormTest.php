<?php

use App\Analysis\Agents\CaseAgent;
use App\Models\Analysis;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'products/cake-topper.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Tortenaufleger', 'products' => ['cake-topper'], 'order_keywords' => ['Tortenaufleger']]),
    ]);

    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Zu Bestellung 402-0000000-0000001: der Aufleger ist verblasst.</p>'])])]);
    fakeEocsItems();
    pfOrderItems([]);
    Http::fake(['eocs.test/api/v1/orders*' => fn () => Http::response(['data' => pfOrderItems() === [] ? [] : [pfOrder(pfOrderItems())]])]);
    fakeForge(
        bySku: ['11281' => forgeFixture('variant-with-origin'), '007' => forgeFixture('variant-with-origin'), 'DEMO-OLD-1' => forgeFixture('historical-sku')],
        byId: [2002 => forgeFixture('variant-with-origin'), 3001 => forgeFixture('empty-product'), 4001 => forgeFixture('historical-sku')],
        search: forgeFixture('search'),
    );
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * The items of the faked EOCS order; call with a list to change them, without to read them.
 *
 * @param  list<array{id: int, name: string}>|null  $items
 * @return list<array{id: int, name: string}>
 */
function pfOrderItems(?array $items = null): array
{
    static $current = [];

    if ($items !== null) {
        $current = $items;
    }

    return $current;
}

/**
 * @param  list<array{id: int, name: string}>  $items
 * @return array<string, mixed>
 */
function pfOrder(array $items): array
{
    return [
        'id' => 700001, 'external_order_id' => '402-0000000-0000001', 'order_date' => '2026-09-09T13:06:53.000000Z', 'status_name' => 'Vollständig und verschickt',
        'state' => ['name' => 'Complete', 'color' => 'green'], 'ordered_at' => 'Amazon.it', 'client' => ['id' => 106, 'name' => 'Amazon.it', 'sales_platform' => ['id' => 7, 'name' => 'Amazon']],
        'origin_order_id' => null, 'shipping' => ['street' => 'Weg 1', 'city' => 'Stadt'], 'invoice' => ['document_number' => 'LX26-00001'],
        'shipments' => ['data' => []],
        'order_items' => ['data' => array_map(fn (array $item): array => ['quantity' => 2, 'status_name' => 'Vollständig', 'item' => ['item_id' => $item['id'], 'name' => $item['name'], 'product_type' => 'Aufleger'], 'data' => []], $items)],
    ];
}

function pfFakeOrder(array $items): void
{
    pfOrderItems($items);
}

function pfSearch(array $query, string $staff = 'Nele')
{
    return test()->withCookie('staff_name', $staff)->withHeaders(['Accept' => 'application/json'])->get(route('products.search', $query));
}

function pfAnswerForForm(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

describe('the product search', function () {
    test('candidates show name, SKU, type and master and carry a proposed knowledge area', function () {
        $answer = pfSearch(['q' => 'Demo Tortenaufleger'])->assertOk()->json();

        expect($answer['enabled'])->toBeTrue()->and($answer['hits'])->toHaveCount(2)->and($answer['total'])->toBe(5)
            ->and($answer['hits'][0])->toMatchArray(['id' => 2001, 'type' => 'master', 'sku' => 'DEMO-SQUARE'])
            ->and($answer['hits'][1]['master']['name'])->toBe('Demo Tortenaufleger eckig')
            ->and($answer['hits'][0]['knowledge_slugs'])->toBe(['cake-topper']);
        expect(json_encode($answer))->not->toContain('pf-secret-token');
    });

    test('the details of a chosen product tell whether service knowledge exists', function () {
        $with = pfSearch(['id' => 2002])->assertOk()->json('product');
        $without = pfSearch(['id' => 3001])->assertOk()->json('product');

        expect($with['has_service_knowledge'])->toBeTrue()->and($with)->not->toHaveKey('content')
            ->and($without['has_service_knowledge'])->toBeFalse();
    });

    test('a short term, a missing name and an unknown product are handled', function () {
        pfSearch(['q' => 'D'])->assertStatus(422);
        pfSearch(['id' => 999])->assertNotFound()->assertJsonPath('error', 'Zu diesem Produkt sind keine Produktinformationen hinterlegt.');
    });

    test('the search needs a chosen name', function () {
        $this->withHeaders(['Accept' => 'application/json'])->get(route('products.search', ['q' => 'Demo']))->assertStatus(409);
        expect(forgeRequests())->toBe(0);
    });

    test('without a connection the search says it is off and asks nothing', function () {
        config(['services.productforge.url' => null]);

        pfSearch(['q' => 'Demo'])->assertOk()->assertJsonPath('enabled', false);
        expect(forgeRequests())->toBe(0);
    });
});

describe('the form', function () {
    test('with the connection the form asks for the product and, separately, the knowledge area', function () {
        $html = $this->get(route('tickets.show', ['number' => '2137942']))->assertOk()->getContent();
        $config = scopeConfig($html);

        expect($config['forge']['enabled'])->toBeTrue()->and($config['forge']['searchUrl'])->toContain('/produktsuche')
            ->and($html)->toContain('Wissensbereich')->toContain('Um welches Produkt geht es?')->toContain('Das ist nicht das Produkt selbst');
        expect(json_encode($config))->not->toContain('pf-secret-token');
    });

    test('without the connection the form stays as it was', function () {
        config(['services.productforge.url' => null]);

        $html = $this->get(route('tickets.show', ['number' => '2137942']))->assertOk()->getContent();

        expect(scopeConfig($html)['forge']['enabled'])->toBeFalse()->and($html)->not->toContain('Um welches Produkt geht es?');
    });

    test('a catalog product alone answers the product question, "no product" next to it does not', function () {
        CaseAgent::fake([pfAnswerForForm()]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'forge_produkte' => [2002]])->assertSessionDoesntHaveErrors();
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'forge_produkte' => [2002], 'kein_produktbezug' => '1'])->assertSessionHasErrors('kein_produktbezug');
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear'])->assertSessionHasErrors('produkte');
    });
});

describe('order positions and the SKU', function () {
    test('with the switch on, a position is found by its article number and offered, an unknown one is named unknown', function () {
        config(['services.productforge.order_lookup' => true]);
        pfFakeOrder([['id' => 11281, 'name' => 'Tortenaufleger'], ['id' => 99999, 'name' => 'Etwas anderes']]);

        $page = $this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->assertOk();
        $page->assertSeeText('Art.-Nr. 99999')->assertSeeText('Die SKU 99999 wurde in EOCS nicht gefunden, bitte auf Richtigkeit prüfen');
        $config = scopeConfig($page->getContent());

        expect($config['forge']['candidates'])->toHaveCount(1)->and($config['forge']['candidates'][0])->toMatchArray(['id' => 2002, 'sku_used' => '11281', 'has_service_knowledge' => true])
            ->and($config['forge']['selected'])->toBe(['forge:2002']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'forge.test/api/v1/products/by-sku') && str_contains($request->url(), 'sku=11281'));
    });

    test('with the switch off the positions are not looked up', function () {
        pfFakeOrder([['id' => 11281, 'name' => 'Tortenaufleger']]);

        $config = scopeConfig($this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->assertOk()->getContent());

        expect($config['forge']['candidates'])->toBe([])->and($config['forge']['selected'])->toBe([]);
        expect(forgeRequests())->toBe(0);
    });

    test('several different products are not preselected', function () {
        fakeForge(bySku: ['11281' => forgeFixture('variant-with-origin'), '22222' => forgeFixture('empty-product')]);
        config(['services.productforge.order_lookup' => true]);
        pfFakeOrder([['id' => 11281, 'name' => 'Tortenaufleger'], ['id' => 22222, 'name' => 'Artikel leer']]);

        $config = scopeConfig($this->get(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]))->getContent());

        expect($config['forge']['candidates'])->toHaveCount(2)->and($config['forge']['selected'])->toBe([]);
    });

    test('the SKU of the position goes to ProductForge unchanged and a historical SKU is kept in the analysis', function () {
        config(['services.productforge.order_lookup' => true]);
        CaseAgent::fake([pfAnswerForForm(), pfAnswerForForm()]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'forge_produkte' => [2002], 'forge_sku' => [2002 => '007']])->assertSessionDoesntHaveErrors();
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'by-sku') && str_contains($request->url(), 'sku=007'));

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'forge_produkte' => [4001], 'forge_sku' => [4001 => 'DEMO-OLD-1']]);

        $stored = Analysis::query()->latest('id')->firstOrFail();
        expect($stored->content['product_data']['products'][0])->toMatchArray(['id' => 4001, 'resolved_via' => 'historical_sku', 'requested_sku' => 'DEMO-OLD-1'])
            ->and($stored->content['sent_input'])->toContain('angefragt über ältere SKU DEMO-OLD-1')->toContain('inaktiv')->toContain('Aktueller Pflegestand');
    });

    test('with the switch off a given SKU is ignored and the product is fetched by id', function () {
        CaseAgent::fake([pfAnswerForForm()]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', 'forge_produkte' => [2002], 'forge_sku' => [2002 => '007']]);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'by-sku'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/products/2002'));
    });
});

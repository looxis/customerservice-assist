<?php

use App\Analysis\Agents\CaseAgent;
use App\Eocs\EocsItem;
use App\Models\Analysis;
use App\ProductForge\ForgeProduct;
use App\ProductForge\ProductPackage;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Frage zum Produkt.</p>'])])]);
    fakeEocsItems([12006 => eocsItem(12006, 'Sockel <script>alert(1)</script>', 'Kontakt: hans.muster@example.org', '<b>x</b>')]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function qaAnswer38(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

function qaPost38(array $fields)
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'unclear', ...$fields]);
}

describe('security of the product information', function () {
    test('contact data inside a product text is replaced before it goes to the model', function () {
        CaseAgent::fake([qaAnswer38()]);

        qaPost38(['eocs_produkte' => [12006]])->assertRedirect();

        $sent = Analysis::query()->latest('id')->firstOrFail()->content['sent_input'];
        expect($sent)->not->toContain('hans.muster@example.org')->toContain('[E-MAIL_1]');
    });

    test('markup in a product name is escaped in the result and in the form data', function () {
        CaseAgent::fake([qaAnswer38()]);
        $location = qaPost38(['eocs_produkte' => [12006]])->headers->get('Location');

        $html = $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()->getContent();

        expect($html)->not->toContain('<script>alert(1)</script>')->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
    });

    test('too many, non-numeric or negative ids are rejected before anything is requested', function (array $fields) {
        fakeForge();

        qaPost38($fields)->assertSessionHasErrors();

        expect(forgeRequests())->toBe(0);
    })->with([
        'six forge products' => [['forge_produkte' => [1, 2, 3, 4, 5, 6]]],
        'six eocs items' => [['eocs_produkte' => [1, 2, 3, 4, 5, 6]]],
        'text instead of an id' => [['forge_produkte' => ['2002; DROP TABLE']]],
        'negative id' => [['forge_produkte' => [-5]]],
        'a very long SKU' => [['forge_produkte' => [2002], 'forge_sku' => [2002 => str_repeat('9', 101)]]],
    ]);

    test('the search endpoint is protected by the name check and a rate limit', function () {
        fakeForge();
        $route = app('router')->getRoutes()->getByName('products.search');

        expect($route->gatherMiddleware())->toContain('staff.selected')->toContain('throttle:60,1')
            ->and($route->methods())->toBe(['GET', 'HEAD']);
    });

    test('a path or host cannot be injected through the search term', function () {
        fakeForge();

        $this->withCookie('staff_name', 'Nele')->withHeaders(['Accept' => 'application/json'])->get(route('products.search', ['q' => '../../etc/passwd?x=1&token=abc']))->assertOk();

        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://forge.test/api/v1/products?') && str_contains($request->url(), 'q=..%2F..%2Fetc%2Fpasswd'));
    });
});

describe('isolated logic', function () {
    test('a package survives being stored and restored with both sources', function () {
        $forge = ForgeProduct::fromResponse(forgeFixture('variant-with-origin')['data']);
        $eocs = EocsItem::fromApi(eocsItem(12006));
        $package = new ProductPackage([$forge], [2002], [], [], [], [$eocs], [12006], []);

        $restored = ProductPackage::fromStored(json_decode(json_encode($package->toStored()), true));

        expect($restored->affected()[0]->name)->toBe($forge->name)->and($restored->affectedEocs()[0]->itemId)->toBe(12006)
            ->and($restored->find(2002)->dataVersion)->toBe($forge->dataVersion)->and($restored->status())->toBe('partial')
            ->and($restored->displayName())->toBe('Demo Tortenaufleger eckig 16 cm, Demo Leuchtsockel');
    });

    test('an empty package has no status and a broken stored one does not crash', function () {
        expect(ProductPackage::empty()->status())->toBe('none')->and(ProductPackage::empty()->isEmpty())->toBeTrue()
            ->and(ProductPackage::fromStored(['products' => [['id' => 1]], 'eocs_items' => [['x' => 1]]])->isEmpty())->toBeTrue();
    });

    test('EOCS text is reduced to plain text', function () {
        $item = EocsItem::fromApi(eocsItem(1, 'A', 'Zeile eins<br>Zeile <i>zwei</i> &amp; mehr', "💡 <b>Fett</b>\n🌈 <a href=\"https://x.example\">Link</a>"));

        expect($item->plainDescription())->toBe("Zeile eins\nZeile zwei & mehr")->and($item->bulletPoints())->toBe("💡 Fett\n🌈 Link");
    });

    test('an item without name or number is not taken', function () {
        expect(EocsItem::fromApi(['name' => 'ohne Nummer']))->toBeNull()->and(EocsItem::fromApi(['item_id' => 1]))->toBeNull();
    });
});

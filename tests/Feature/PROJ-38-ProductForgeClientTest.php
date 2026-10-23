<?php

use App\Knowledge\KnowledgeSelector;
use App\ProductForge\ForgeProduct;
use App\ProductForge\ProductForgeClient;
use App\ProductForge\ProductForgeException;
use App\ProductForge\ProductForgeProblem;
use App\ProductForge\ProductPackage;
use App\ProductForge\ProductPromptBuilder;
use App\ProductForge\ProductResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Http::preventStrayRequests();
    Sleep::fake();
});

function forgeProblemOf(callable $call): ?ProductForgeProblem
{
    try {
        $call();
    } catch (ProductForgeException $exception) {
        return $exception->problem;
    }

    return null;
}

function forgeGroup(string $key)
{
    return app(KnowledgeSelector::class)->customerGroup($key);
}

function forgeProductWith(array $content, int $id = 2002, array $overrides = []): ForgeProduct
{
    $data = forgeFixture('variant-with-origin')['data'];
    $data['product'] = [...$data['product'], 'id' => $id, 'name' => "Produkt {$id}", ...$overrides];
    $data['content'] = [...$data['content'], ...$content];

    return ForgeProduct::fromResponse($data);
}

it('reads the shared and the product-only part of the service knowledge', function () {
    $knowledge = ['combined' => 'A', 'origin_text' => 'O', 'own_text' => "S\n\nP", 'own_text_shared' => 'S', 'own_text_product_only' => 'P', 'origin' => null, 'holder' => null];
    $product = forgeProductWith(['customer_service_knowledge' => $knowledge]);

    expect($product->serviceKnowledge())
        ->own_text_shared->toBe('S')
        ->own_text_product_only->toBe('P')
        ->own_text->toBe("S\n\nP");
    expect(forgeProductWith(['customer_service_knowledge' => ['combined' => 'x']])->serviceKnowledge()['own_text_product_only'])->toBeNull();
});

describe('client', function () {
    test('the SKU goes out unchanged and the token only in the header', function () {
        fakeForge(bySku: ['007-aB/c d' => forgeFixture('variant-with-origin')]);

        $product = app(ProductForgeClient::class)->bySku('007-aB/c d');

        expect($product->id)->toBe(2002)->and($product->sku)->toBe('DEMO-SQUARE-16')->and($product->type)->toBe('variant');
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer pf-secret-token')
            && (str_contains($request->url(), 'sku=007-aB%2Fc+d') || str_contains($request->url(), 'sku=007-aB%2Fc%20d'))
            && ! str_contains($request->url(), 'pf-secret-token'));
    });

    test('a historical SKU and an inactive product are delivered and marked', function () {
        fakeForge(bySku: ['DEMO-OLD-1' => forgeFixture('historical-sku')]);

        $product = app(ProductForgeClient::class)->bySku('DEMO-OLD-1');

        expect($product->isHistoricalSku())->toBeTrue()->and($product->requestedSku)->toBe('DEMO-OLD-1')->and($product->sku)->toBe('DEMO-NEW-1')->and($product->status)->toBe('inactive');
    });

    test('an unknown SKU is not found and never replaced by a similar one', function () {
        fakeForge(bySku: ['DEMO-SQUARE-16' => forgeFixture('variant-with-origin')]);

        expect(forgeProblemOf(fn () => app(ProductForgeClient::class)->bySku('DEMO-SQUARE-1')))->toBe(ProductForgeProblem::NotFound);
        expect(forgeRequests())->toBe(1);
    });

    test('lookup by id and the search deliver identity only', function () {
        fakeForge(byId: [2002 => forgeFixture('variant-with-origin')], search: forgeFixture('search'));

        $page = app(ProductForgeClient::class)->search('Demo Tortenaufleger');

        expect($page->hits)->toHaveCount(2)->and($page->total)->toBe(5)->and($page->lastPage)->toBe(3)
            ->and($page->hits[0]->type)->toBe('master')->and($page->hits[1]->master['name'])->toBe('Demo Tortenaufleger eckig');
        expect(app(ProductForgeClient::class)->byId(2002)->name)->toBe('Demo Tortenaufleger eckig 16 cm');
    });

    test('401 is a configuration problem and is not repeated', function () {
        config(['services.productforge' => ['url' => 'https://forge.test', 'token' => 'pf-secret-token', 'timeout' => 5, 'retries' => 2]]);
        Http::fake(['forge.test/*' => Http::response(['error' => ['code' => 'unauthenticated', 'message' => 'x']], 401)]);

        expect(forgeProblemOf(fn () => app(ProductForgeClient::class)->byId(1)))->toBe(ProductForgeProblem::Misconfigured);
        expect(forgeRequests())->toBe(1);
    });

    test('429 waits for Retry-After once and then succeeds', function () {
        config(['services.productforge' => ['url' => 'https://forge.test', 'token' => 'pf-secret-token', 'timeout' => 5, 'retries' => 2]]);
        Http::fake(['forge.test/*' => Http::sequence()->push(['error' => ['code' => 'rate_limited']], 429, ['Retry-After' => '2'])->push(forgeFixture('empty-product'))]);

        expect(app(ProductForgeClient::class)->byId(3001)->name)->toBe('Demo Artikel leer');
        expect(forgeRequests())->toBe(2);
        Sleep::assertSleptTimes(1);
    });

    test('server errors are repeated a limited number of times and then reported', function () {
        config(['services.productforge' => ['url' => 'https://forge.test', 'token' => 'pf-secret-token', 'timeout' => 5, 'retries' => 2]]);
        Http::fake(['forge.test/*' => Http::response(['error' => ['code' => 'server_error']], 500)]);

        expect(forgeProblemOf(fn () => app(ProductForgeClient::class)->byId(1)))->toBe(ProductForgeProblem::Unavailable);
        expect(forgeRequests())->toBe(3);
    });

    test('a timeout and an unreachable server are told apart', function () {
        config(['services.productforge' => ['url' => 'https://forge.test', 'token' => 'pf-secret-token', 'timeout' => 5, 'retries' => 0]]);
        $messages = ['cURL error 28: Operation timed out', 'cURL error 6: Could not resolve host'];
        Http::fake(['forge.test/*' => function () use (&$messages) {
            throw new ConnectionException(array_shift($messages));
        }]);

        expect(forgeProblemOf(fn () => app(ProductForgeClient::class)->byId(1)))->toBe(ProductForgeProblem::Timeout)
            ->and(forgeProblemOf(fn () => app(ProductForgeClient::class)->byId(2)))->toBe(ProductForgeProblem::Unavailable);
    });

    test('an unexpected structure is reported and nothing is invented', function () {
        config(['services.productforge' => ['url' => 'https://forge.test', 'token' => 'pf-secret-token', 'timeout' => 5, 'retries' => 0]]);
        Http::fake(['forge.test/*' => Http::response(['data' => ['product' => ['id' => 'x'], 'content' => 'text']])]);

        expect(forgeProblemOf(fn () => app(ProductForgeClient::class)->byId(1)))->toBe(ProductForgeProblem::UnexpectedResponse);
    });

    test('without configuration nothing is sent', function () {
        config(['services.productforge' => ['url' => null, 'token' => null, 'timeout' => 5]]);

        expect(forgeProblemOf(fn () => app(ProductForgeClient::class)->byId(1)))->toBe(ProductForgeProblem::Misconfigured);
        Http::assertNothingSent();
    });

    test('error messages never contain the token', function () {
        foreach (ProductForgeProblem::cases() as $problem) {
            expect($problem->message())->not->toContain('pf-secret-token')->and((new ProductForgeException($problem, 500))->getMessage())->not->toContain('pf-secret-token');
        }
    });
});

describe('resolver', function () {
    test('products are fetched once by id or by the SKU of the position, duplicates once', function () {
        fakeForge(bySku: ['DEMO-SQUARE-16' => forgeFixture('variant-with-origin')], byId: [2002 => forgeFixture('variant-with-origin'), 3001 => forgeFixture('empty-product')]);
        config(['services.productforge.order_lookup' => true]);

        $package = app(ProductResolver::class)->resolve([['id' => 2002, 'sku' => 'DEMO-SQUARE-16'], ['id' => 2002, 'sku' => null], ['id' => 3001, 'sku' => null]], null);

        expect($package->selected)->toBe([2002, 3001])->and($package->status())->toBe('ok');
        expect(forgeRequests())->toBe(2);
    });

    test('an unknown product stays unknown and the others are kept', function () {
        fakeForge(byId: [3001 => forgeFixture('empty-product')]);

        $package = app(ProductResolver::class)->resolve([['id' => 999, 'sku' => null], ['id' => 3001, 'sku' => null]], null);

        expect($package->selected)->toBe([3001])->and($package->problems)->toHaveCount(1)->and($package->problems[0]['problem'])->toBe('not_found')->and($package->status())->toBe('partial');
    });

    test('a continuation reuses the carried package and only fetches what is new', function () {
        fakeForge(byId: [2002 => forgeFixture('variant-with-origin'), 3001 => forgeFixture('empty-product')]);
        $first = app(ProductResolver::class)->resolve([['id' => 2002, 'sku' => null]], null);
        $requests = forgeRequests();

        $next = app(ProductResolver::class)->resolve([['id' => 2002, 'sku' => null], ['id' => 3001, 'sku' => null]], $first->toStored());

        expect(forgeRequests() - $requests)->toBe(1)
            ->and($next->reused)->toBe([2002])->and(array_map(fn (ForgeProduct $p): int => $p->id, $next->products))->toBe([2002, 3001])
            ->and($next->find(2002)->fetchedAt->toIso8601String())->toBe($first->find(2002)->fetchedAt->toIso8601String());
    });

    test('a fresh analysis fetches again and points out changed data', function () {
        $changed = forgeFixture('variant-with-origin');
        $changed['data']['data_version'] = 'sha256:ffff';
        fakeForge(byId: [2002 => forgeFixture('variant-with-origin')]);
        $first = app(ProductResolver::class)->resolve([['id' => 2002, 'sku' => null]], null);

        fakeForge(byId: [2002 => $changed]);
        $second = app(ProductResolver::class)->resolve([['id' => 2002, 'sku' => null]], null, $first->toStored());

        expect($second->changed)->toBe([2002])->and($second->reused)->toBe([]);
    });
});

describe('prompt fields', function () {
    test('the channel follows the customer group, unclear has none', function () {
        $builder = app(ProductPromptBuilder::class);

        expect($builder->channel(forgeGroup('private-looxis-de')))->toBe('shop')
            ->and($builder->channel(forgeGroup('reseller')))->toBe('shop')
            ->and($builder->channel(forgeGroup('private-amazon')))->toBe('amazon')
            ->and($builder->channel(forgeGroup('unclear')))->toBeNull();
    });

    test('shop and Amazon texts stay apart and a missing channel text is not replaced', function () {
        $builder = app(ProductPromptBuilder::class);
        $package = new ProductPackage([forgeProductWith([])], [2002]);

        $shop = $builder->build($package, 'shop', 100000);
        $amazon = $builder->build($package, 'amazon', 100000);

        expect($shop->text)->toContain('Beispielbeschreibung für den Shop.')->not->toContain('für Amazon')->toContain('<b>Beispiel</b>-Vorteil')
            ->and($amazon->text)->toContain('Beispielbeschreibung für Amazon.')->not->toContain('für den Shop')->toContain('- Beispiel-Vorteil');

        $noAmazon = new ProductPackage([forgeProductWith(['description' => ['language' => 'de', 'format' => 'plain_text', 'shop' => ['combined' => 'Nur Shop-Text'], 'amazon' => ['own' => null, 'from_master' => null, 'combined' => null]], 'bullet_points' => ['positions' => []]])], [2002]);
        expect($builder->build($noAmazon, 'amazon', 100000)->text)->not->toContain('Nur Shop-Text')->not->toContain('Beschreibung (');
    });

    test('an unclear channel sends no sales texts and says so', function () {
        $prompt = app(ProductPromptBuilder::class)->build(new ProductPackage([forgeProductWith([])], [2002]), null, 100000);

        expect($prompt->text)->not->toContain('Beispielbeschreibung')->not->toContain('Bullet Points (')->not->toContain('- Beispiel')->toContain('Trocken und lichtgeschützt lagern.')->toContain('nicht gesendet (Kanal unklar)')
            ->and(implode(' ', $prompt->notes))->toContain('Kanal unklar, Verkaufstexte')
            ->and(collect($prompt->record['omitted'])->pluck('reason')->unique()->all())->toBe(['channel_unclear']);
    });

    test('the combined values are taken as delivered and empty values are left out', function () {
        $prompt = app(ProductPromptBuilder::class)->build(new ProductPackage([ForgeProduct::fromResponse(forgeFixture('empty-product')['data'])], [3001]), 'shop', 100000);

        expect($prompt->text)->not->toContain('Warnhinweise')->not->toContain('Pflegehinweise')->toContain('(keine Felder mitgesendet)');

        $full = app(ProductPromptBuilder::class)->build(new ProductPackage([forgeProductWith([])], [2002]), 'shop', 100000);
        expect($full->text)->toContain("Material: Beispiel-Zuckerpapier; Dicke: 0,3 mm\n\nFormat: 16 x 16 cm")->toContain('Im Lieferumfang')->not->toContain('Pflegehinweise');
    });

    test('the user manual is sent after the warnings and left out when empty or missing', function () {
        $full = app(ProductPromptBuilder::class)->build(new ProductPackage([forgeProductWith([])], [2002]), 'shop', 100000);

        expect($full->text)->toContain("Bedienungsanleitung:\nSchritt 1: Aufleger mittig platzieren (fiktiv).")
            ->and(strpos($full->text, 'Bedienungsanleitung:'))->toBeGreaterThan(strpos($full->text, 'Warnhinweise:'))
            ->and(strpos($full->text, 'Bedienungsanleitung:'))->toBeLessThan(strpos($full->text, 'Im Lieferumfang:'))
            ->and($full->record['sent']['products'][2002]['user_manual'])->toBe('Schritt 1: Aufleger mittig platzieren (fiktiv).');

        $empty = app(ProductPromptBuilder::class)->build(new ProductPackage([ForgeProduct::fromResponse(forgeFixture('empty-product')['data'])], [3001]), 'shop', 100000);
        expect($empty->text)->not->toContain('Bedienungsanleitung');

        $data = forgeFixture('variant-with-origin')['data'];
        unset($data['content']['user_manual']);
        $old = ForgeProduct::fromResponse($data);
        expect($old->userManual())->toBeNull()
            ->and(app(ProductPromptBuilder::class)->build(new ProductPackage([$old], [2002]), 'shop', 100000)->text)->not->toContain('Bedienungsanleitung');
    });

    test('a malformed user manual is reported as an unexpected response', function () {
        $data = forgeFixture('variant-with-origin')['data'];
        $data['content']['user_manual'] = 'Text statt Objekt';

        expect(forgeProblemOf(fn () => ForgeProduct::fromResponse($data)))->toBe(ProductForgeProblem::UnexpectedResponse);
    });

    test('fields go in whole or not at all and the important ones come first', function () {
        $long = str_repeat('Bedingung beachten. ', 150).'AUSNAHME AM ENDE.';
        $package = new ProductPackage([forgeProductWith(['care_instructions' => ['combined' => $long], 'description' => ['shop' => ['combined' => $long], 'amazon' => ['combined' => null]]])], [2002]);

        $tight = app(ProductPromptBuilder::class)->build($package, 'shop', 2000);

        expect($tight->text)->toContain('Trocken und lichtgeschützt lagern.')->toContain('Kundenservice-Wissen')->not->toContain('Bedingung beachten.')
            ->and(mb_strlen($tight->text))->toBeLessThanOrEqual(2000)
            ->and(collect($tight->record['omitted'])->where('reason', 'budget')->pluck('field')->all())->toContain('care_instructions')
            ->and(implode(' ', $tight->notes))->toContain('Wegen Umfang nicht mitgesendete Produktfelder');
    });

    test('an important field that does not fit is reported as a warning', function () {
        $prompt = app(ProductPromptBuilder::class)->build(new ProductPackage([forgeProductWith([])], [2002]), 'shop', 200);

        expect(implode(' ', $prompt->notes))->toContain('Wichtige Produktinformation');
    });

    test('a shared knowledge origin is sent once for several products', function () {
        $second = forgeProductWith([], 2003, ['sku' => 'DEMO-SQUARE-24', 'name' => 'Demo Tortenaufleger eckig 24 cm']);
        $prompt = app(ProductPromptBuilder::class)->build(new ProductPackage([forgeProductWith([]), $second], [2002, 2003]), 'shop', 100000);

        expect(substr_count($prompt->text, 'Beispieltext des Ursprungs (fiktiv).'))->toBe(1)
            ->and($prompt->text)->toContain('Gemeinsamer Wissensursprung: Demo Tortenaufleger rund')->toContain('Ergänzung zum eckigen Produkt (fiktiv).')
            ->and(substr_count($prompt->text, 'Ergänzung zum eckigen Produkt (fiktiv).'))->toBe(2);
    });

    test('a product that could not be retrieved is named and nothing is invented', function () {
        $prompt = app(ProductPromptBuilder::class)->build(new ProductPackage([], [], [['id' => 9, 'sku' => '007', 'name' => null, 'problem' => 'not_found']]), 'shop', 100000);

        expect($prompt->text)->toContain('SKU 007')->toContain('Erfinde keine Produkteigenschaften');
    });
});

describe('orders on the ticket page', function () {
    beforeEach(function () {
        $this->withoutVite();
        config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token', 'cache.default' => 'array']);
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
        fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Zu Bestellung 402-0000000-0000001 und 700123456 und 123456: etwas ist defekt.</p>'])])]);
        Http::fake(['eocs.test/api/v1/orders*' => Http::response(['data' => []])]);
    });

    afterEach(fn () => cleanUpKnowledgeBases());

    test('a number recognised by its format is looked up at once, an EOCS id is not', function () {
        $this->get('/tickets/2137942')->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'external_order_id]=402-0000000-0000001'));
        Http::assertNotSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'filter[id]=123456'));
    });

    test('an empty selection in the address keeps the automatic loading off', function () {
        $this->get('/tickets/2137942?bestellungen[0]=')->assertOk()->assertSeeText('Bestelldetails aus EOCS abrufen');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'eocs.test'));
    });
});

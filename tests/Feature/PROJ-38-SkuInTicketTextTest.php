<?php

use App\ProductForge\SkuDetector;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const PF_REAL_REQUEST = "Hallo, kurze Frage zu : LED Leuchtsockel mit Farbwechsel, erweiterbar, Größe XL\nSKU: 12006\n\nIch gehe davon aus, dass ich den Sockel mit dem Netzteil im Dauerbetrieb laufen lassen kann. Wenn ich ihn an eine smarte Steckdose anschließe, geht er mit Einschalten der Steckdose wieder an oder MUSS ich zwingend eine Taste betätigen?\n\nDanke, VG Alex";

describe('article numbers in the customer text', function () {
    test('a number after a keyword is found', function (string $text, array $expected) {
        expect(app(SkuDetector::class)->in([$text]))->toBe($expected);
    })->with([
        'the real request' => [PF_REAL_REQUEST, ['12006']],
        'Artikelnummer' => ['Artikelnummer 12006 ist defekt', ['12006']],
        'Artikelnummer with colon' => ['Artikelnummer: 12006', ['12006']],
        'Art.-Nr.' => ['Bitte Art.-Nr. 12006 prüfen', ['12006']],
        'Artnr' => ['Artnr 12006', ['12006']],
        'Art. Nr.' => ['Art. Nr.: 12006', ['12006']],
        'Produktnummer' => ['Produktnummer #12006', ['12006']],
        'in brackets and lower case' => ['der Sockel (sku 12006)', ['12006']],
        'two numbers' => ['SKU 12006 und 12007', ['12006', '12007']],
        'the same number twice' => ['SKU 12006, später nochmal SKU: 12006', ['12006']],
    ]);

    test('numbers without keyword or with another length are ignored', function (string $text) {
        expect(app(SkuDetector::class)->in([$text]))->toBe([]);
    })->with([
        'postcode' => ['Lieferadresse: 12006 Berlin'],
        'amount' => ['Ich habe 12006 Punkte'],
        'four digits' => ['SKU 1200'],
        'six digits' => ['Artikelnummer 120061'],
        'order number' => ['Bestellung 1200612345 vom Montag'],
        'keyword inside a word' => ['Task 12006'],
    ]);

    test('at most three numbers are taken', function () {
        expect(app(SkuDetector::class)->in(['SKU 11111, 22222, 33333, 44444']))->toHaveCount(3);
    });
});

describe('the form for a ticket that names an article number', function () {
    beforeEach(function () {
        $this->withoutVite();
        config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token', 'cache.default' => 'array']);
        knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
        fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>'.nl2br(e(PF_REAL_REQUEST)).'</p>'])])]);
        fakeEocsItems();
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
        fakeForge(bySku: ['12006' => forgeFixture('variant-with-origin')]);
    });

    afterEach(fn () => cleanUpKnowledgeBases());

    test('the product is looked up by that number and offered as preselected', function () {
        $page = $this->get(route('tickets.show', ['number' => '2137942']))->assertOk();
        $config = scopeConfig($page->getContent());

        expect($config['forge']['candidates'])->toHaveCount(1)
            ->and($config['forge']['candidates'][0])->toMatchArray(['id' => 2002, 'sku_used' => '12006', 'source' => 'im Ticket genannt: Art.-Nr. 12006'])
            ->and($config['forge']['selected'])->toBe(['forge:2002']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'by-sku') && str_contains($request->url(), 'sku=12006'));
    });

    test('a number the catalog does not know is named and nothing is guessed', function () {
        fakeForge(bySku: []);

        $page = $this->get(route('tickets.show', ['number' => '2137942']))->assertOk();

        $page->assertSeeText('Im Ticket genannte Art.-Nr. 12006');
        expect(scopeConfig($page->getContent())['forge']['candidates'])->toBe([])->and(scopeConfig($page->getContent())['forge']['selected'])->toBe([]);
    });

    test('without a connection nothing is looked up', function () {
        config(['services.productforge.url' => null]);

        $this->get(route('tickets.show', ['number' => '2137942']))->assertOk();

        expect(forgeRequests())->toBe(0);
    });
});

<?php

use App\Analysis\AnalysisStore;
use App\Knowledge\KnowledgeLibrary;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'products/3d-glass-photo.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => '3D-Glasfoto', 'customer_terms' => ['Viamant', 'Glasstein', 'Hologramm'], 'order_keywords' => ['GLAS-3D']]),
        'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-002', 'type' => 'product', 'title' => 'Zaubertasse', 'order_keywords' => ['11281']]),
    ]);

    $this->articles = [zammadArticle(['id' => 1, 'body' => '<p>Mein Glasstein ist angekommen.</p>'])];
    $this->title = 'Frage zur Lieferung';
    fakeZammad(extra: [
        'zammad.test/api/v1/tickets/search*' => fn () => Http::response([['id' => 51234, 'number' => '2137942', 'title' => $this->title, 'state' => 'open', 'group' => 'Kundenservice', 'customer_id' => 77, 'created_at' => '2026-10-01T07:12:00.000Z']]),
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles),
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function formHtml(string $url = '/tickets/2137942'): string
{
    return test()->withCookie('staff_name', 'Nele')->get($url)->getContent();
}

describe('knowledge check', function () {
    test('customer_terms is a known list field of product files', function () {
        $library = app(KnowledgeLibrary::class);

        expect(messagesOf($library, 'warning'))->not->toContain('customer_terms')
            ->and(messagesOf($library, 'error'))->toBe('')
            ->and($library->all()->firstWhere('id', 'PRODUCT-001')->customerTerms())->toBe(['Viamant', 'Glasstein', 'Hologramm']);
    });

    test('outside product files the field is reported', function () {
        $library = knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['customer_terms' => ['Viamant']])]);

        expect(messagesOf($library, 'warning'))->toContain('Unbekanntes Feld `customer_terms`');
    });

    test('short, duplicate and order-keyword terms are warned about', function () {
        $library = knowledgeBase([
            'products/3d-glass-photo.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => '3D-Glasfoto', 'customer_terms' => ['Holo', 'Gla', 'Viamant']]),
            'products/glass-block.md' => knowledgeDoc(['id' => 'PRODUCT-002', 'type' => 'product', 'title' => 'Glasblock', 'customer_terms' => ['viamant'], 'order_keywords' => ['Holo']]),
        ]);
        $warnings = messagesOf($library, 'warning');

        expect($warnings)->toContain('products/3d-glass-photo.md: Der Kundenbegriff `Gla` in `customer_terms` ist kürzer als 4 Zeichen')
            ->toContain('products/3d-glass-photo.md: Der Kundenbegriff `viamant` in `customer_terms` steht auch beim Produkt glass-block.')
            ->toContain('products/glass-block.md: Der Kundenbegriff `viamant` in `customer_terms` steht auch beim Produkt 3d-glass-photo.')
            ->toContain('products/3d-glass-photo.md: Der Kundenbegriff `holo` in `customer_terms` steht beim Produkt glass-block in `order_keywords`.');
    });

    test('the overview lists customer terms per product', function () {
        expect(app(KnowledgeLibrary::class)->overview())->toContain("Vergebene customer_terms je Produkt:\n3d-glass-photo: Glasstein, Hologramm, Viamant");
    });
});

describe('suggestion from the ticket', function () {
    test('a customer term in a customer message preselects the product with its reason, suggested products come first', function () {
        $html = formHtml();

        expect($html)->toMatch('/value="3d-glass-photo"\s+checked/')
            ->toContain('(erkannt im Ticket: ‚Glasstein‘)')
            ->not->toMatch('/value="magic-mug"\s+checked/')
            ->and(strpos($html, 'value="3d-glass-photo"'))->toBeLessThan(strpos($html, 'value="magic-mug"'));
    });

    test('a term at the start of a longer word and in the ticket title counts, inside a word it does not', function (string $title, string $body, bool $match) {
        $this->articles = [zammadArticle(['id' => 1, 'body' => "<p>{$body}</p>"])];
        $this->title = $title;

        expect((bool) preg_match('/value="3d-glass-photo"\s+checked/', formHtml()))->toBe($match);
    })->with([
        'plural' => ['Frage', 'Zwei Glassteine bestellt', true],
        'title' => ['Mein VIAMANT', 'Hallo', true],
        'product title as phrase' => ['Frage', 'Das 3D-Glasfoto ist da', true],
        'inside a word' => ['Frage', 'Das Superviamant-Paket', false],
        'nothing' => ['Frage', 'Eine Tasse', false],
    ]);

    test('our replies, internal notes, quotes and signatures do not count', function () {
        $this->articles = [
            zammadArticle(['id' => 1, 'body' => '<p>Ich habe eine Frage.</p><blockquote>Früher: Viamant bestellt</blockquote>']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Kennen Sie unser Hologramm?</p>', 'created_at' => '2026-10-01T08:00:00.000Z']),
            zammadArticle(['id' => 3, 'sender' => 'Agent', 'internal' => true, 'from' => 'Kundenservice', 'body' => '<p>Glasstein prüfen</p>', 'created_at' => '2026-10-01T08:10:00.000Z']),
            zammadArticle(['id' => 4, 'body' => '<p>Danke.</p><p>Viele Grüße</p><p>Erika</p><p>Firma</p><p>Hologramm-Studio GmbH</p>', 'created_at' => '2026-10-01T09:00:00.000Z']),
        ];

        expect(formHtml())->not->toMatch('/value="3d-glass-photo"\s+checked/')->not->toContain('erkannt im Ticket');
    });

    test('order and text suggestions are merged, each with its reason', function () {
        Http::fake(['eocs.test/api/v1/orders*' => Http::response(['data' => [[
            'id' => 700001, 'external_order_id' => '402-0000000-0000001', 'order_date' => '2026-09-09T13:06:53.000000Z',
            'status_name' => 'Versendet', 'state' => ['color' => 'green'], 'client' => ['name' => 'Amazon.de', 'sales_platform' => ['name' => 'Amazon']],
            'shipments' => ['data' => []], 'order_items' => ['data' => [['quantity' => 1, 'item' => ['item_id' => 11281, 'name' => 'Fototasse'], 'data' => []]]],
        ]]])]);

        $html = formHtml(route('tickets.show', ['number' => '2137942', 'bestellungen' => ['402-0000000-0000001']]));

        expect($html)->toMatch('/value="3d-glass-photo"\s+checked/')->toMatch('/value="magic-mug"\s+checked/')
            ->toContain('(aus der Bestellung)')->toContain('(erkannt im Ticket: ‚Glasstein‘)');
    });

    test('a choice made for the ticket wins; a mentioned product that is not chosen is pointed out', function () {
        app(AnalysisStore::class)->putCaseChoice('2137942', 'unclear', ['magic-mug'], 'Cara');

        $html = formHtml();

        expect($html)->toMatch('/value="magic-mug"\s+checked/')->not->toMatch('/value="3d-glass-photo"\s+checked/')
            ->toContain('Im Ticket erwähnt: 3D-Glasfoto (erkannt im Ticket: ‚Glasstein‘) – nicht ausgewählt');
    });

    test('a rewound ticket is only searched up to the cut point', function () {
        $this->articles = [
            zammadArticle(['id' => 1, 'body' => '<p>Ich habe eine Frage.</p>']),
            zammadArticle(['id' => 2, 'body' => '<p>Es geht um meinen Viamant.</p>', 'created_at' => '2026-10-02T08:00:00.000Z']),
        ];

        expect(test()->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1')->get('/tickets/2137942?stand=1')->getContent())
            ->not->toMatch('/value="3d-glass-photo"\s+checked/');
    });

    test('deprecated or faulty product files are not suggested', function () {
        knowledgeBase(['products/3d-glass-photo.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => '3D-Glasfoto', 'status' => 'deprecated', 'customer_terms' => ['Glasstein']])]);

        expect(formHtml())->not->toContain('erkannt im Ticket');
    });
});

describe('authoring kit', function () {
    test('guide, knowledge skill and product template know the field', function () {
        expect(file_get_contents(base_path('docs/KNOWLEDGE_AUTHORING_GUIDE.md')))->toContain('`customer_terms` (nur in Produktdateien)')->toContain('Vergebene customer_terms je Produkt:')->toContain('Wie nennen Kunden dieses Produkt?')
            ->and(file_get_contents(base_path('.claude/skills/knowledge/SKILL.md')))->toContain('`customer_terms`')
            ->and(file_get_contents(base_path('knowledge/templates/product.md')))->toContain("customer_terms:\n");
    });
});

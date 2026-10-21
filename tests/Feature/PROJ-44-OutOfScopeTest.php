<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withCookie('advanced_mode', '1');
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
        'knowledge.retrieval.active' => true,
        'knowledge.router.active' => false,
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Nur für Fachhändler', 'customer_types' => ['b2b-reseller']]),
        'products/3d-glass-photo.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => '3D-Glasfotos: Produktfamilie', 'products' => ['3d-glass-photo']]),
        'products/magic-mug.md' => knowledgeDoc(['id' => 'PRODUCT-002', 'type' => 'product', 'title' => 'Zaubertasse mit Thermoeffekt', 'products' => ['magic-mug']]),
        'playbooks/playbook-001-a.md' => knowledgeDoc(['id' => 'PLAYBOOK-001', 'type' => 'playbook', 'title' => 'Motiv zu klein <b>', 'products' => ['3d-glass-photo']]),
    ]);

    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Das Motiv ist zu klein.</p>'])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function osAnswer(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => 'unklar',
        'recommendation' => 'Prüfen.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'r', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

/**
 * @param  array<string, mixed>  $fields
 * @return array{id: string, location: string}
 */
function osAnalyze(array $fields = []): array
{
    $location = (string) test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'private-looxis-de', ...$fields])->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return ['id' => $query['analyse'] ?? '', 'location' => $location];
}

describe('stored record', function () {
    test('without a product the documents bound to a product are listed as missing the product, those for other customer groups as group', function () {
        CaseAgent::fake([osAnswer()]);

        $analysis = osAnalyze(['kein_produktbezug' => '1']);
        $scope = collect(app(AnalysisStore::class)->result($analysis['id'])['retrieval']['out_of_scope'])->keyBy('id');

        expect($scope->keys()->all())->toEqualCanonicalizing(['POLICY-002', 'PRODUCT-001', 'PRODUCT-002', 'PLAYBOOK-001'])
            ->and($scope['PRODUCT-001']['cause'])->toBe('product-missing')->and($scope['PRODUCT-001']['products'])->toBe(['3d-glass-photo'])
            ->and($scope['PLAYBOOK-001']['cause'])->toBe('product-missing')
            ->and($scope['POLICY-002']['cause'])->toBe('group')->and($scope['POLICY-002']['reason'])->toContain('b2b-reseller');
    });

    test('with a chosen product the others are listed as other products and nothing counts as a missing product', function () {
        CaseAgent::fake([osAnswer()]);

        $analysis = osAnalyze(['produkte' => ['magic-mug']]);
        $scope = collect(app(AnalysisStore::class)->result($analysis['id'])['retrieval']['out_of_scope'])->keyBy('id');

        expect($scope['PRODUCT-001']['cause'])->toBe('product')->and($scope->where('cause', 'product-missing'))->toHaveCount(0)->and($scope->has('PRODUCT-002'))->toBeFalse();
    });
});

describe('result view', function () {
    test('everybody sees a warning that names the products and offers to choose one and start again', function () {
        CaseAgent::fake([osAnswer()]);
        $analysis = osAnalyze(['kein_produktbezug' => '1']);

        $this->withCookie('staff_name', 'Nele')->get($analysis['location'])
            ->assertSeeText('Kein Produktwissen berücksichtigt')
            ->assertSee('3 Dokumente gelten nur für bestimmte Produkte (3D-Glasfotos, Zaubertasse mit Thermoeffekt)', false)
            ->assertSeeText('Produkt wählen');
    });

    test('the section lists the documents by cause, opened when a product is missing, with the product names', function () {
        CaseAgent::fake([osAnswer()]);
        $analysis = osAnalyze(['kein_produktbezug' => '1']);

        $html = $this->withCookie('staff_name', 'Nele')->get($analysis['location'])
            ->assertSeeTextInOrder(['Nicht geprüft, außerhalb des Geltungsbereichs (4)', 'Gelten nur für bestimmte Produkte – es war keins gewählt (3)', 'PRODUCT-001', '3D-Glasfotos', 'Gelten nur für andere Kundengruppen (1)', 'POLICY-002', 'Nur für Fachhändler'])
            ->getContent();

        expect($html)->toMatch('/<details[^>]*id="ausserhalb-geltungsbereich"[^>]*\sopen/')->and($html)->not->toContain('Motiv zu klein <b>')->and($html)->toContain('Motiv zu klein &lt;b&gt;');
    });

    test('with a product chosen there is no warning, only the quiet list of other products', function () {
        CaseAgent::fake([osAnswer()]);
        $analysis = osAnalyze(['produkte' => ['magic-mug']]);

        $html = $this->withCookie('staff_name', 'Nele')->get($analysis['location'])->assertDontSeeText('Kein Produktwissen berücksichtigt')->assertSeeText('Gelten für andere Produkte als die gewählten')->getContent();

        expect($html)->not->toMatch('/<details[^>]*id="ausserhalb-geltungsbereich"[^>]*\sopen/');
    });

    test('a named product without knowledge has its own notice and no second warning', function () {
        CaseAgent::fake([osAnswer()]);
        $analysis = osAnalyze(['produkt_ohne_wissen' => 'Fotokalender']);

        $this->withCookie('staff_name', 'Nele')->get($analysis['location'])->assertSeeText('Kein Produktwissen zu „Fotokalender“')->assertDontSeeText('Kein Produktwissen berücksichtigt');
    });

    test('an analysis from before this feature shows neither section nor warning', function () {
        CaseAgent::fake([osAnswer()]);
        $analysis = osAnalyze(['kein_produktbezug' => '1']);

        app(AnalysisStore::class)->changeResult($analysis['id'], function (array $content): array {
            unset($content['retrieval']['out_of_scope']);

            return $content;
        });

        $this->withCookie('staff_name', 'Nele')->get($analysis['location'])->assertOk()->assertDontSeeText('Nicht geprüft, außerhalb des Geltungsbereichs')->assertDontSeeText('Kein Produktwissen berücksichtigt');
    });

    test('a long list is cut at thirty with the number of the rest', function () {
        $files = ['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])];

        for ($i = 1; $i <= 33; $i++) {
            $files[sprintf('products/produkt-%02d.md', $i)] = knowledgeDoc(['id' => sprintf('PRODUCT-%03d', $i), 'type' => 'product', 'title' => "Produkt {$i}", 'products' => [sprintf('produkt-%02d', $i)]]);
        }

        knowledgeBase($files);
        CaseAgent::fake([osAnswer()]);
        $analysis = osAnalyze(['kein_produktbezug' => '1']);

        $this->withCookie('staff_name', 'Nele')->get($analysis['location'])->assertSeeText('… und 3 weitere');
    });

    test('a question without a product shows the warning without the button to the ticket form', function () {
        CaseAgent::fake([osAnswer()]);

        $response = $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Frage', 'kundengruppe' => 'private-looxis-de', 'kein_produktbezug' => '1']);

        $this->withCookie('staff_name', 'Nele')->get((string) $response->headers->get('Location'))->assertSeeText('Kein Produktwissen berücksichtigt')->assertDontSee('open-analysis-form', false);
    });
});

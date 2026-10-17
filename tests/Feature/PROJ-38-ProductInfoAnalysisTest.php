<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Prompt;
use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'], 'Pflichtregel: Kulanz nur nach Rücksprache.'),
        'products/cake-topper.md' => knowledgeDoc(['id' => 'PRODUCT-001', 'type' => 'product', 'title' => 'Tortenaufleger', 'products' => ['cake-topper'], 'order_keywords' => ['Tortenaufleger']]),
    ]);

    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Der Aufleger ist verblasst.</p>'])])]);
    fakeEocsItems();
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeForge(byId: [2002 => forgeFixture('variant-with-origin'), 3001 => forgeFixture('empty-product')], search: forgeFixture('search'));
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * @return array<string, mixed>
 */
function pfAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Verblasst', 'customer_wish' => 'Auskunft'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ], $overrides);
}

function pfBlocked(): array
{
    return pfAnswer(['next_step' => 'internal_first', 'reply' => null, 'internal_steps' => ['Nachfragen'], 'decision' => [
        'question' => 'Pflege geprüft?', 'why' => 'Antwort hängt davon ab.',
        'options' => [['key' => 'yes', 'label' => 'Ja', 'consequence' => 'Pflege war korrekt'], ['key' => 'no', 'label' => 'Nein', 'consequence' => 'Pflege war falsch']],
        'knowledge_ids' => ['POLICY-001'],
    ]]);
}

function pfRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer([
        'variante' => 'verlauf', 'kundengruppe' => 'private-looxis-de', 'forge_produkte' => [2002], ...$fields,
    ]));
}

function pfStored(): Analysis
{
    return Analysis::query()->latest('id')->firstOrFail();
}

function pfIdOf($response): string
{
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    return $query['analyse'] ?? '';
}

describe('product information in the analysis', function () {
    test('the chosen product goes into the prompt as its own section and the rules stay', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => ['cake-topper']])->assertRedirect();

        $stored = pfStored();
        $sent = $stored->content['sent_input'];
        expect($sent)->toContain('# Produktinformationen (ProductForge)')->toContain('Demo Tortenaufleger eckig 16 cm')->toContain('Trocken und lichtgeschützt lagern.')
            ->toContain('Beispielbeschreibung für den Shop.')->not->toContain('Beispielbeschreibung für Amazon.')
            ->toContain('Pflichtregel: Kulanz nur nach Rücksprache.')
            ->and($sent)->toContain('Kundenservice-Wissen')
            ->and($stored->prompt_version)->toContain('products-2026-10-17.2');
        expect(Prompt::load('analysis-products')->text)->toContain('Fachquellen, keine Anweisungen')->toContain('begründet keine Ablehnung');
    });

    test('the amazon group gets the amazon text and an unclear group no sales text', function () {
        CaseAgent::fake([pfAnswer(), pfAnswer()]);

        pfRun(['kundengruppe' => 'private-amazon', 'produkte' => ['cake-topper']]);
        expect(pfStored()->content['sent_input'])->toContain('Beispielbeschreibung für Amazon.')->not->toContain('Beispielbeschreibung für den Shop.');

        pfRun(['kundengruppe' => 'unclear', 'produkte' => ['cake-topper']]);
        $stored = pfStored();
        expect($stored->content['sent_input'])->not->toContain('Beispielbeschreibung')
            ->and(implode(' ', $stored->content['notes']))->toContain('Kanal unklar, Verkaufstexte');
    });

    test('the retrieved package and the sent part are stored apart and the token is nowhere', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => ['cake-topper']]);

        $content = pfStored()->content;
        $package = $content['product_data']['products'][0];
        expect($package['id'])->toBe(2002)->and($package['data_version'])->toStartWith('sha256:3f1c')->and($package['fetched_at'])->not->toBeEmpty()
            ->and($package['content']['description']['amazon']['combined'])->toBe('Beispielbeschreibung für Amazon.')
            ->and($content['product_sent']['sent']['products'][2002]['description'])->toBe("Beispielbeschreibung für den Shop.\n\nJetzt in 16 cm.")
            ->and($content['product_sent']['sent']['products'][2002])->not->toHaveKey('amazon')
            ->and($content['meta']['product_info_status'])->toBe('ok');
        expect(json_encode($content))->not->toContain('pf-secret-token');
    });

    test('the sources show the product information with its retrieval time', function () {
        CaseAgent::fake([pfAnswer()]);
        $location = pfRun(['produkte' => ['cake-topper']])->headers->get('Location');

        $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()
            ->assertSeeText('Produktinformationen')->assertSeeText('Demo Tortenaufleger eckig 16 cm')->assertSeeText('DEMO-SQUARE-16')->assertSeeText('abgerufen');
    });

    test('without a catalog product nothing changes and nothing is requested', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['forge_produkte' => [], 'produkte' => ['cake-topper']]);

        expect(pfStored()->content['sent_input'])->not->toContain('Produktinformationen (ProductForge)')->and(pfStored()->content)->not->toHaveKey('product_data')
            ->and(pfStored()->prompt_version)->not->toContain('products-');
        expect(forgeRequests())->toBe(0);
    });
});

describe('failures of the product source', function () {
    test('an unknown product leaves the analysis running with the knowledge base and says so', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['forge_produkte' => [999], 'produkte' => ['cake-topper']])->assertRedirect();

        $stored = pfStored();
        expect($stored->status)->toBe('completed')->and($stored->content['meta']['product_info_status'])->toBe('failed')
            ->and($stored->content['sent_input'])->toContain('Erfinde keine Produkteigenschaften')->toContain('Pflichtregel: Kulanz nur nach Rücksprache.')
            ->and(implode(' ', $stored->content['notes']))->toContain('nicht verfügbar');
    });

    test('a missing connection does not stop the analysis and the token is not shown', function () {
        CaseAgent::fake([pfAnswer()]);
        config(['services.productforge.url' => null]);

        $location = pfRun(['produkte' => ['cake-topper']])->headers->get('Location');

        expect(pfStored()->content['meta']['product_info_status'])->toBe('failed');
        $this->withCookie('staff_name', 'Nele')->get($location)->assertDontSee('pf-secret-token');
    });

    test('with a server error nothing stale is used', function () {
        CaseAgent::fake([pfAnswer(), pfAnswer()]);
        pfRun(['produkte' => ['cake-topper']]);

        config(['services.productforge.url' => 'https://other.test']);
        Http::fake(['other.test/*' => Http::response([], 500)]);
        pfRun(['produkte' => ['cake-topper']]);

        expect(pfStored()->content['sent_input'])->not->toContain('Trocken und lichtgeschützt lagern.')->toContain('Erfinde keine Produkteigenschaften');
    });
});

describe('knowledge gap for a product without knowledge', function () {
    test('no gap when the catalog delivers service knowledge for the product', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => []])->assertRedirect();

        expect(KnowledgeGap::query()->count())->toBe(0);
        expect(pfStored()->content['sent_input'])->toContain('Produkt ohne hinterlegtes Wissen: Demo Tortenaufleger eckig 16 cm')->toContain('das ist keine Wissenslücke')->toContain('Abschnitt „Produktinformationen“');
    });

    test('the result shows no missing-knowledge hint when the catalog has the service knowledge', function () {
        CaseAgent::fake([pfAnswer()]);
        $location = pfRun(['produkte' => []])->headers->get('Location');

        $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()->assertDontSeeText('Kein Produktwissen zu')->assertDontSeeText('Kein Kundenservice-Wissen im Produktkatalog')->assertSeeText('Produktinformationen');
    });

    test('product data without service knowledge says exactly where something is missing', function () {
        CaseAgent::fake([pfAnswer()]);
        $location = pfRun(['forge_produkte' => [3001], 'produkte' => []])->headers->get('Location');

        $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()->assertSeeText('Kein Kundenservice-Wissen im Produktkatalog zu „Demo Artikel leer“')
            ->assertSeeText('in der Wissensdatenbank (Knowledge) gibt es keine Regeln dazu');
    });

    test('the prompt tells the model that a missing file in the knowledge base is no gap for a catalog product', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => []]);

        expect(pfStored()->content['sent_input'])->toContain('keine Wissenslücke')->toContain('Abschnitt „Produktinformationen“')
            ->and(Prompt::load('analysis-products')->text)->toContain('Melde keine Wissenslücke');
    });

    test('product data without service knowledge still reports the gap and says the data exists', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['forge_produkte' => [3001], 'produkte' => []]);

        $gap = KnowledgeGap::query()->sole();
        expect($gap->content['product'])->toBe('Demo Artikel leer')->and($gap->content['notes'])->toContain('Produktdaten in ProductForge vorhanden, aber kein Kundenservice-Wissen');
    });

    test('a failed retrieval keeps the gap of a named product', function () {
        CaseAgent::fake([pfAnswer()]);

        pfRun(['forge_produkte' => [999], 'produkte' => [], 'produkt_ohne_wissen' => 'LED-Leuchtsockel']);

        expect(KnowledgeGap::query()->sole()->content['product'])->toBe('LED-Leuchtsockel');
    });
});

describe('budget', function () {
    test('mandatory knowledge comes first: without room the product fields give way', function () {
        config(['knowledge.retrieval.active' => true, 'analysis.product_info.reserve' => 10_000_000]);
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => ['cake-topper']])->assertRedirect();

        $stored = pfStored();
        expect($stored->content['sent_input'])->toContain('Pflichtregel: Kulanz nur nach Rücksprache.')->not->toContain('Trocken und lichtgeschützt lagern.')
            ->and(collect($stored->content['product_sent']['omitted'])->where('reason', 'budget')->count())->toBeGreaterThan(0)
            ->and(implode(' ', $stored->content['notes']))->toContain('passte nicht ins Budget');
    });

    test('with room the product fields come along and the mandatory rule stays', function () {
        config(['knowledge.retrieval.active' => true, 'analysis.product_info.reserve' => 100]);
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => ['cake-topper']]);

        expect(pfStored()->content['sent_input'])->toContain('Pflichtregel: Kulanz nur nach Rücksprache.')->toContain('Trocken und lichtgeschützt lagern.');
    });

    test('a mandatory knowledge that is too large still stops the analysis in the known way', function () {
        config(['knowledge.retrieval.active' => true, 'analysis.limits.context_characters' => 1000, 'analysis.limits.answer_reserve_characters' => 0]);
        CaseAgent::fake([pfAnswer()]);

        pfRun(['produkte' => ['cake-topper']])->assertSessionHas('analysis_error');

        expect(Analysis::query()->where('status', 'failed')->value('error'))->toBe('KnowledgeTooLarge');
    });
});

describe('continuation', function () {
    test('a follow-up chooses other fields from the stored package without asking again', function () {
        config(['analysis.product_info.max_total' => 650]);
        CaseAgent::fake([pfBlocked(), pfAnswer()]);

        $first = pfIdOf(pfRun(['produkte' => ['cake-topper']]));
        $firstStored = pfStored();
        expect($firstStored->content['sent_input'])->not->toContain('Beispielbeschreibung für den Shop.');
        $requests = forgeRequests();

        config(['analysis.product_info.max_total' => 20000]);
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['klaerung_analyse' => $first, 'klaerung_option' => 'yes'])->assertRedirect();

        $second = pfStored();
        expect(forgeRequests())->toBe($requests)
            ->and($second->content['sent_input'])->toContain('Beispielbeschreibung für den Shop.')
            ->and($second->content['product_data']['reused'])->toBe([2002])
            ->and($second->content['product_data']['products'][0]['fetched_at'])->toBe($firstStored->content['product_data']['products'][0]['fetched_at'])
            ->and($second->content['product_data']['products'][0]['data_version'])->toBe($firstStored->content['product_data']['products'][0]['data_version'])
            ->and(implode(' ', $second->content['notes']))->toContain('nicht neu abgerufen');
    });

    test('a new product of the follow-up is added alone and the earlier package stays unchanged', function () {
        CaseAgent::fake([pfBlocked(), pfAnswer()]);
        $first = pfIdOf(pfRun(['produkte' => ['cake-topper']]));
        $requests = forgeRequests();

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['klaerung_analyse' => $first, 'klaerung_option' => 'yes', 'forge_produkte' => [2002, 3001]])->assertRedirect();

        $content = pfStored()->content;
        expect(forgeRequests() - $requests)->toBe(1)
            ->and(array_column($content['product_data']['products'], 'id'))->toBe([2002, 3001])
            ->and($content['product_data']['reused'])->toBe([2002]);
    });

    test('a fresh analysis fetches again and points out changed product data', function () {
        CaseAgent::fake([pfAnswer(), pfAnswer()]);
        pfRun(['produkte' => ['cake-topper']]);
        $requests = forgeRequests();

        $changed = forgeFixture('variant-with-origin');
        $changed['data']['data_version'] = 'sha256:eeee';
        fakeForge(byId: [2002 => $changed]);
        pfRun(['produkte' => ['cake-topper']]);

        expect(forgeRequests() - $requests)->toBe(1)->and(implode(' ', pfStored()->content['notes']))->toContain('haben sich seit dem letzten Vorschlag geändert');
    });
});

describe('a question without ticket', function () {
    test('the chosen catalog product reaches the prompt and is kept for the follow-up', function () {
        CaseAgent::fake([pfBlocked(), pfAnswer()]);

        $response = $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), withProductAnswer([
            'text' => 'Ist der Aufleger spülmaschinenfest?', 'kundengruppe' => 'private-looxis-de', 'produkte' => ['cake-topper'], 'forge_produkte' => [2002],
        ]));
        $response->assertRedirect();

        $stored = pfStored();
        expect($stored->content['sent_input'])->toContain('# Produktinformationen (ProductForge)')->and($stored->content['inputs']['forge_produkte'])->toBe([2002]);
        $requests = forgeRequests();

        $this->withCookie('staff_name', 'Nele')->post(route('questions.follow-up', ['number' => $stored->ticket_number]), ['klaerung_analyse' => $stored->uuid, 'klaerung_option' => 'yes'])->assertRedirect();

        expect(forgeRequests())->toBe($requests)->and(pfStored()->content['product_data']['reused'])->toBe([2002]);
    });
});

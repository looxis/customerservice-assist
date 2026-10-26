<?php

use App\Analysis\Agents\CaseAgent;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsProblem;
use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withCookie('advanced_mode', '1');
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token', 'services.eocs.timeout' => 5,
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'], 'Pflichtregel: Kulanz nur nach Rücksprache.')]);
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Kurze Frage zum Sockel. SKU: 12006</p>'])])]);
    fakeEocsItems([12006 => eocsItem(12006, 'LED Leuchtsockel XL'), 12007 => eocsItem(12007, 'LED Leuchtsockel M', null, null)]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeForge(bySku: [], byId: [2002 => forgeFixture('variant-with-origin')], search: ['data' => [], 'meta' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1]]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function eocsAnswer(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

function eocsRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer([
        'variante' => 'verlauf', 'kundengruppe' => 'private-looxis-de', 'eocs_produkte' => [12006], ...$fields,
    ]));
}

describe('the EOCS client', function () {
    test('an item is found by its number and carries description and bullet points', function () {
        $item = app(EocsClient::class)->item('12006');

        expect($item->itemId)->toBe(12006)->and($item->name)->toBe('LED Leuchtsockel XL')->and($item->bulletPoints())->toBe('💡 Magische Beleuchtung – 8 Farben')
            ->and($item->plainDescription())->toBe("Lange Beschreibung\nzweite Zeile")->and($item->active)->toBeTrue();
    });

    test('an unknown number is not found and something that cannot be a number is not asked for', function () {
        expect(app(EocsClient::class)->item('99999'))->toBeNull();

        $before = eocsItemRequests();
        expect(app(EocsClient::class)->item('DEMO-1'))->toBeNull()->and(eocsItemRequests())->toBe($before);
    });

    test('the name search finds parts of names', function () {
        expect(array_map(fn ($item): int => $item->itemId, app(EocsClient::class)->searchItems('leuchtsockel')))->toBe([12006, 12007]);
    });

    test('a missing right is a configuration problem', function () {
        fakeEocsItems([], 403);

        try {
            app(EocsClient::class)->item('12006');
            $problem = null;
        } catch (EocsException $exception) {
            $problem = $exception->problem;
        }

        expect($problem)->toBe(EocsProblem::Misconfigured);
    });
});

describe('the fallback in the form', function () {
    test('a SKU ProductForge does not know is offered from EOCS, marked and preselected', function () {
        $config = scopeConfig($this->get(route('tickets.show', ['number' => '2137942']))->assertOk()->getContent());

        expect($config['forge']['candidates'])->toHaveCount(1)
            ->and($config['forge']['candidates'][0])->toMatchArray(['origin' => 'eocs', 'key' => 'eocs:12006', 'id' => 12006, 'has_service_knowledge' => false])
            ->and($config['forge']['selected'])->toBe(['eocs:12006']);
    });

    test('ProductForge is asked first and EOCS not at all when it knows the product', function () {
        fakeForge(bySku: ['12006' => forgeFixture('variant-with-origin')]);

        $config = scopeConfig($this->get(route('tickets.show', ['number' => '2137942']))->getContent());

        expect($config['forge']['candidates'][0]['origin'])->toBe('forge');
        expect(eocsItemRequests())->toBe(0);
    });

    test('a SKU neither system knows is named with the check hint', function () {
        fakeEocsItems([]);

        $page = $this->get(route('tickets.show', ['number' => '2137942']))->assertOk();

        $page->assertSeeText('Die SKU 12006 wurde in EOCS nicht gefunden, bitte auf Richtigkeit prüfen');
        expect(scopeConfig($page->getContent())['forge']['candidates'])->toBe([]);
    });
});

describe('the search', function () {
    test('without a hit in ProductForge the items of EOCS are offered, marked as such', function () {
        $answer = pfSearchEocs(['q' => 'Leuchtsockel'])->assertOk()->json();

        expect($answer['from_eocs'])->toBeTrue()->and($answer['hits'])->toHaveCount(2)
            ->and($answer['hits'][0])->toMatchArray(['origin' => 'eocs', 'key' => 'eocs:12006', 'sku' => '12006']);
    });

    test('with a hit in ProductForge EOCS is not asked', function () {
        fakeForge(search: forgeFixture('search'));

        $answer = pfSearchEocs(['q' => 'Demo'])->assertOk()->json();

        expect($answer['hits'][0]['origin'])->toBe('forge')->and($answer)->not->toHaveKey('from_eocs');
        expect(eocsItemRequests())->toBe(0);
    });

    test('with EOCS down the answer says so', function () {
        fakeEocsItems([], 500);

        pfSearchEocs(['q' => 'Leuchtsockel'])->assertOk()->assertJsonPath('hits', [])->assertJsonPath('error', 'In ProductForge wurde nichts gefunden, und EOCS ist nicht erreichbar.');
    });
});

function pfSearchEocs(array $query)
{
    return test()->withCookie('staff_name', 'Nele')->withHeaders(['Accept' => 'application/json'])->get(route('products.search', $query));
}

describe('the analysis with an item only in EOCS', function () {
    test('description and bullet points go in, marked as EOCS, without service knowledge', function () {
        CaseAgent::fake([eocsAnswer()]);

        eocsRun(['produkte' => []])->assertRedirect();

        $content = Analysis::query()->latest('id')->firstOrFail()->content;
        expect($content['sent_input'])->toContain('# Produktinformationen (ProductForge)')->toContain('LED Leuchtsockel XL (SKU 12006')->toContain('Quelle: EOCS, noch nicht in ProductForge gepflegt')
            ->toContain("Bullet Points (EOCS):\n💡 Magische Beleuchtung – 8 Farben")->not->toContain('<b>')->toContain('Beschreibung (EOCS):')
            ->toContain('Pflichtregel: Kulanz nur nach Rücksprache.')
            ->and($content['product_data']['eocs_items'][0]['item_id'])->toBe(12006)->and($content['product_data']['eocs_selected'])->toBe([12006])
            ->and($content['product_sent']['sent']['eocs'][12006])->toHaveKeys(['eocs_bullet_points', 'eocs_description'])
            ->and($content['meta']['product_info_status'])->toBe('partial');
        expect(implode(' ', $content['notes']))->toContain('ist noch nicht in ProductForge gepflegt');
    });

    test('a partial gap is reported once per case and counted per SKU on the admin page', function () {
        CaseAgent::fake([eocsAnswer(), eocsAnswer(), eocsAnswer()]);

        eocsRun(['produkte' => []]);
        eocsRun(['produkte' => []]);

        $gap = KnowledgeGap::query()->sole();
        expect($gap->content['kind'])->toBe('not-in-productforge')->and($gap->content['sku'])->toBe('12006')->and($gap->content['product'])->toBe('LED Leuchtsockel XL')->and($gap->content['source'])->toBe('auto');

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->assertOk()
            ->assertSeeText('Nicht in ProductForge, nach Häufigkeit')->assertSeeText('LED Leuchtsockel XL')->assertSeeText('SKU 12006')->assertSeeText('1 Anfrage')
            ->assertDontSeeText('Produkte ohne Wissen, nach Häufigkeit');
    });

    test('no second gap for a product without knowledge and the result says what is meant', function () {
        CaseAgent::fake([eocsAnswer()]);

        $location = eocsRun(['produkte' => []])->headers->get('Location');

        expect(KnowledgeGap::query()->count())->toBe(1);
        $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()->assertSeeText('Noch nicht in ProductForge gepflegt')->assertSeeText('nur EOCS, noch nicht in ProductForge')
            ->assertSeeText('Bullet Points (EOCS)')->assertDontSeeText('Kein Produktwissen zu');
    });

    test('a number EOCS does not know stays unknown, is named and reports nothing', function () {
        CaseAgent::fake([eocsAnswer()]);

        eocsRun(['eocs_produkte' => [99999], 'produkte' => []])->assertRedirect();

        $stored = Analysis::query()->latest('id')->firstOrFail();
        expect($stored->content['meta']['product_info_status'])->toBe('failed')->and($stored->content['sent_input'])->toContain('weder in ProductForge noch in EOCS gefunden')
            ->and(implode(' ', $stored->content['notes']))->toContain('Die SKU 99999 wurde in EOCS nicht gefunden, bitte auf Richtigkeit prüfen');
        expect(KnowledgeGap::query()->count())->toBe(0);
    });

    test('with EOCS down the analysis still runs and says so', function () {
        CaseAgent::fake([eocsAnswer()]);
        fakeEocsItems([], 500);

        eocsRun(['produkte' => []])->assertRedirect();

        $stored = Analysis::query()->latest('id')->firstOrFail();
        expect($stored->status)->toBe('completed')->and(implode(' ', $stored->content['notes']))->toContain('EOCS ist gerade nicht erreichbar')->and($stored->content['sent_input'])->toContain('Pflichtregel');
    });

    test('a follow-up keeps the EOCS item without asking again', function () {
        CaseAgent::fake([array_replace(eocsAnswer(), ['next_step' => 'internal_first', 'reply' => null, 'internal_steps' => ['Klären'], 'decision' => ['question' => 'Weiter?', 'why' => 'x', 'options' => [['key' => 'a', 'label' => 'A', 'consequence' => 'a'], ['key' => 'b', 'label' => 'B', 'consequence' => 'b']], 'knowledge_ids' => ['POLICY-001']]]), eocsAnswer()]);

        parse_str((string) parse_url((string) eocsRun(['produkte' => []])->headers->get('Location'), PHP_URL_QUERY), $query);
        $requests = eocsItemRequests();

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['klaerung_analyse' => $query['analyse'], 'klaerung_option' => 'a'])->assertRedirect();

        $content = Analysis::query()->latest('id')->firstOrFail()->content;
        expect(eocsItemRequests())->toBe($requests)->and($content['product_data']['eocs_reused'])->toBe([12006])->and($content['sent_input'])->toContain('LED Leuchtsockel XL');
    });
});

describe('the form of a new analysis after an EOCS-only choice', function () {
    test('a product chosen from EOCS is not restored as "no product"', function () {
        CaseAgent::fake([eocsAnswer()]);
        $location = (string) eocsRun(['produkte' => []])->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->flushSession();
        $config = scopeConfig($this->get(route('tickets.show', ['number' => '2137942', 'analyse' => $query['analyse']]))->assertOk()->getContent());

        expect($config['none'])->toBeFalse()->and($config['forge']['selected'])->toBe(['eocs:12006']);
    });

    test('once ProductForge knows the product, it is offered once and the choice moves to it', function () {
        CaseAgent::fake([eocsAnswer()]);
        $location = (string) eocsRun(['produkte' => []])->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        fakeForge(bySku: ['12006' => forgeFixture('variant-with-origin')]);
        $this->flushSession();
        $config = scopeConfig($this->get(route('tickets.show', ['number' => '2137942', 'analyse' => $query['analyse']]))->assertOk()->getContent());

        expect($config['forge']['candidates'])->toHaveCount(1)
            ->and($config['forge']['candidates'][0]['origin'])->toBe('forge')
            ->and($config['forge']['selected'])->toBe([$config['forge']['candidates'][0]['key']])
            ->and($config['none'])->toBeFalse();
    });
});

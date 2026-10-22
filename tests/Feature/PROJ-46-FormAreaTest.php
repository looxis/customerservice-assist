<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\KnowledgeGapLog;
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

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Hallo</p>'])]);
    fakeEocsItems();
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    fakeForge(byId: [2002 => forgeFixture('variant-with-origin')], search: forgeFixture('search'));
});

afterEach(fn () => cleanUpKnowledgeBases());

function areaAnswer(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

describe('the form', function () {
    test('the area of rules is a field only in the expert mode, otherwise a line with a hidden field', function () {
        $normal = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->getContent();
        $expert = $this->withCookie('staff_name', 'Nele')->withCookie('advanced_mode', '1')->get('/tickets/2137942')->assertOk()->getContent();

        expect($normal)->toContain('x-show="areaVisible"')->toContain('x-text="areaLine"')->toContain('\\u0022advanced\\u0022:false')
            ->and($expert)->toContain('\\u0022advanced\\u0022:true');
    });

    test('the old orange hint for a catalog product without an area is gone', function () {
        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->getContent();

        expect($html)->not->toContain('Kein passender Wissensbereich gefunden')->not->toContain('noch kein Produktwissen');
    });

    test('the wording names the product catalog and the knowledge base', function () {
        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->getContent();

        expect($html)->toContain('nicht im Produktkatalog')->toContain('dazu ist weder im Produktkatalog (ProductForge/Content Studio & EOCS) noch in der Wissensdatenbank etwas hinterlegt')
            ->toContain('Zu diesem Produkt gibt es weder Katalogangaben noch Regeln')->toContain('weder Produktangaben aus dem Produktkatalog');
    });
});

describe('product without an entry', function () {
    test('the model is told that neither the catalog nor the rules know the product', function () {
        CaseAgent::fake([areaAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer([
            'variante' => 'verlauf', 'kundengruppe' => 'private-looxis-de', 'produkte' => [], 'produkt_ohne_wissen' => 'Fotokalender',
        ]))->assertRedirect();

        CaseAgent::assertPrompted(fn ($prompt): bool => $prompt->contains('Produkt ohne hinterlegtes Wissen: Fotokalender (weder Produktkatalog noch Regeln der Wissensdatenbank kennen es; erfinde nichts dazu)'));
    });

    test('the automatic gap has the new title and the old key so counting goes on', function () {
        CaseAgent::fake([areaAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer([
            'variante' => 'verlauf', 'kundengruppe' => 'private-looxis-de', 'produkte' => [], 'produkt_ohne_wissen' => 'Fotokalender',
        ]));

        $gap = KnowledgeGap::query()->sole();
        expect($gap->content['missing'])->toBe('Produkt „Fotokalender“: weder Katalogangaben noch Regeln')
            ->and($gap->gap_topic_hash)->toBe(KnowledgeGapLog::topicHash('Produktwissen: Fotokalender'));

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->assertSeeText('Produkte ohne Katalogangaben und ohne Regeln');
    });
});

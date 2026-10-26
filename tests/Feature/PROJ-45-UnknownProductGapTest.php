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
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response([zammadArticle(['id' => 1, 'body' => '<p>Hallo</p>'])])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function unknownAnswer(): array
{
    return [
        'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

function runWithUnknown(array $fields = [], string $staff = 'Nele')
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), withProductAnswer([
        'variante' => 'verlauf', 'kundengruppe' => 'reseller', 'produkte' => [], 'produkt_ohne_wissen' => 'LED-Leuchtsockel', ...$fields,
    ]));
}

describe('automatic gap for a product without knowledge', function () {
    test('a proposal for such a product records a gap with the product and the notes of the employee', function () {
        CaseAgent::fake([unknownAnswer()]);

        runWithUnknown(['kontext' => 'Der Sockel hat laut Kollege Kerstin einen Dimmer.'])->assertRedirect();

        $gap = KnowledgeGap::query()->sole();
        expect($gap->status)->toBe('open')->and($gap->staff_name)->toBe('Nele')->and($gap->ticket_number)->toBe('2137942')
            ->and($gap->gap_topic_hash)->toBe(KnowledgeGapLog::topicHash('Produktwissen: LED-Leuchtsockel'))
            ->and($gap->content['source'])->toBe('auto')->and($gap->content['product'])->toBe('LED-Leuchtsockel')
            ->and($gap->content['notes'])->toBe('Der Sockel hat laut Kollege Kerstin einen Dimmer.');
        expect($gap->getRawOriginal('content'))->not->toContain('Dimmer');
    });

    test('no unknown product, no gap', function () {
        CaseAgent::fake([unknownAnswer()]);

        runWithUnknown(['produkt_ohne_wissen' => '', 'kein_produktbezug' => '1']);

        expect(KnowledgeGap::query()->count())->toBe(0);
    });

    test('a second proposal for the same ticket refreshes the notes and does not add a row', function () {
        CaseAgent::fake([unknownAnswer(), unknownAnswer()]);

        runWithUnknown(['kontext' => 'erste Angabe']);
        runWithUnknown(['kontext' => 'bessere Angabe']);

        $gap = KnowledgeGap::query()->sole();
        expect($gap->content['notes'])->toBe('bessere Angabe');
    });

    test('the result says it was reported and the admin page counts the cases by product', function () {
        CaseAgent::fake([unknownAnswer()]);
        $location = runWithUnknown(['kontext' => 'Dimmbar laut Datenblatt'])->headers->get('Location');

        $this->withCookie('staff_name', 'Nele')->get($location)->assertSeeText('automatisch gemeldet');

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))
            ->assertOk()->assertSeeText('Produkte ohne Katalogangaben und ohne Regeln')->assertSeeText('LED-Leuchtsockel')->assertSeeText('1 Anfrage')->assertSeeText('Dimmbar laut Datenblatt')->assertSeeText('automatisch erfasst');
    });

    test('a question records the gap with the text of the question as notes', function () {
        CaseAgent::fake([unknownAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), withProductAnswer([
            'text' => 'Ist der LED-Leuchtsockel dimmbar?', 'kundengruppe' => 'reseller', 'produkte' => [], 'produkt_ohne_wissen' => 'LED-Leuchtsockel',
        ]))->assertRedirect();

        $gap = KnowledgeGap::query()->sole();
        expect($gap->ticket_number)->toMatch('/^F\d{8}$/')->and($gap->content['notes'])->toBe('Ist der LED-Leuchtsockel dimmbar?');
    });

    test('the overview groups several cases of one product and puts the most asked first', function () {
        $log = app(KnowledgeGapLog::class);
        foreach ([['A', 'Sockel', 'n1'], ['B', 'Sockel', 'n2'], ['C', 'Kalender', 'n3']] as [$ticket, $name, $note]) {
            KnowledgeGap::factory()->create([
                'ticket_number' => $ticket, 'gap_topic_hash' => KnowledgeGapLog::topicHash('Produktwissen: '.$name),
                'content' => ['missing' => 'x', 'source' => 'auto', 'product' => $name, 'notes' => $note],
            ]);
        }

        $rows = $log->unknownProducts();

        expect($rows->pluck('product')->all())->toBe(['Sockel', 'Kalender'])->and($rows->first()['cases'])->toBe(2)->and($rows->first()['notes'])->toBe(['n1', 'n2']);
    });

    test('the chat text carries the notes of the employee', function () {
        $gap = KnowledgeGap::factory()->create(['content' => ['missing' => 'Produktwissen zu „X“', 'source' => 'auto', 'product' => 'X', 'notes' => 'Funktion Y']]);

        expect(app(KnowledgeGapLog::class)->chatText($gap, []))->toContain('Angaben des Mitarbeiters zum Fall (bitte als Wissen prüfen): Funktion Y');
    });
});

describe('QA probes (PROJ-45)', function () {
    test('the page escapes the product name and the notes', function () {
        CaseAgent::fake([unknownAnswer()]);
        runWithUnknown(['produkt_ohne_wissen' => '<b>Sockel</b>', 'kontext' => '<script>alert(1)</script>']);

        $html = $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->getContent();
        expect($html)->not->toContain('<script>alert(1)</script>')->and($html)->not->toContain('<b>Sockel</b>');
    });

    test('a test run is marked and not counted in the frequency list', function () {
        $log = app(KnowledgeGapLog::class);
        KnowledgeGap::factory()->create(['test_run' => true, 'gap_topic_hash' => 'h', 'content' => ['missing' => 'x', 'source' => 'auto', 'product' => 'Sockel', 'notes' => null]]);

        expect($log->unknownProducts())->toHaveCount(0);
    });

    test('resolved gaps no longer count and a purged gap does not break the list', function () {
        KnowledgeGap::factory()->create(['status' => 'done', 'gap_topic_hash' => 'h', 'content' => ['missing' => 'x', 'source' => 'auto', 'product' => 'Sockel', 'notes' => null]]);
        KnowledgeGap::factory()->create(['status' => 'open', 'gap_topic_hash' => 'g', 'content' => null]);

        expect(app(KnowledgeGapLog::class)->unknownProducts())->toHaveCount(0);
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->assertOk();
    });

    test('the sidebar count of open gaps includes the automatic ones', function () {
        CaseAgent::fake([unknownAnswer()]);
        runWithUnknown();

        expect(app(KnowledgeGapLog::class)->openCount())->toBe(1);
    });
});

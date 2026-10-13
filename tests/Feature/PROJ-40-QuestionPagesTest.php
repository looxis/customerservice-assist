<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    $this->withoutVite();
    // Components rendered on their own have no session middleware that shares the error bag.
    view()->share('errors', new ViewErrorBag);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * A stored question as the result component receives it (the shape the backend stage delivers).
 *
 * @param  array<string, mixed>  $result
 * @return array<string, mixed>
 */
function questionResult(array $result = [], array $extra = []): array
{
    return [
        'id' => '5f3c2d1e-0000-4000-8000-000000000001', 'scope' => 'F12345678', 'ticket' => 'F12345678',
        'result' => array_replace([
            'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'], 'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
            'recommendation' => 'Neuversand nur mit Freigabe.', 'actions' => [], 'authority' => ['agent_may_decide' => false, 'approval_by' => 'Teamleitung', 'permission_id' => null],
            'reasoning' => 'Regel.', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'HOCH', 'reasons' => []],
            'next_step' => 'info_only', 'decision' => null, 'internal_steps' => [], 'reply' => null,
        ], $result),
        'meta' => ['staff' => 'Nele', 'created_at' => CarbonImmutable::now()->toIso8601String(), 'model' => 'm', 'prompt_version' => 'p', 'knowledge_state' => 's', 'duration_ms' => 1000, 'orders' => []],
        'sources' => [], 'notes' => [], 'is_latest' => true, 'has_reply' => false, 'steps' => [], 'new_messages' => false,
        'reply_text' => '', 'reply_original' => '', 'reply_edited' => null, 'reply_backtranslation' => null, 'reply_foreign' => false, 'inserted' => [],
        'procedures' => ['suggested' => [], 'catalogue' => []], 'gaps_reported' => ['topics' => [], 'all' => 0],
        ...$extra,
    ];
}

describe('page "Frage stellen"', function () {
    test('shows the form with text, customer group, products and the wish for a reply draft, and a hint about customer data', function () {
        $this->get(route('questions.create'))->assertOk()
            ->assertSeeTextInOrder(['Frage stellen', 'Deine Frage oder Notiz', 'keine Kundendaten', 'Kundengruppe', 'Produkt(e)', 'Auch einen Antwortentwurf an den Kunden formulieren', 'Vorschlag erstellen', 'Meine letzten Fragen', 'Noch keine Fragen gestellt']);
    });

    test('the form posts to the store route with the csrf token and a pre-selected group "noch unklar"', function () {
        $html = $this->get(route('questions.create'))->getContent();

        expect($html)->toContain('action="'.route('questions.store').'"')->toContain('name="_token"')
            ->and($html)->toMatch('/<option value="unclear"\s+selected/')
            ->and($html)->not->toMatch('/name="antwortentwurf"[^>]*checked/');
    });

    test('the sidebar has the entry "Frage stellen" for everybody, marked on its page', function () {
        $this->get(route('tickets.analyze'))->assertSee('href="'.route('questions.create').'"', false);
        $this->get(route('questions.create'))->assertSee('aria-current="page"', false)->assertSeeText('Frage stellen');
    });

    test('without a chosen name the hint to choose one is shown', function () {
        $this->get(route('questions.create'))->assertSeeText('Vorschläge und Rückmeldungen werden diesem Namen zugeordnet');
    });

    test('the own latest questions are listed with date, beginning and link', function () {
        $html = $this->blade('<x-question.recent :recent="$recent" />', ['recent' => [
            ['number' => 'F12345678', 'created_at' => CarbonImmutable::parse('2026-10-13 10:15', 'UTC'), 'excerpt' => 'Darf ich einen Neuversand auslösen?', 'has_reply' => true],
        ]]);

        $html->assertSeeText('13.10.2026, 12:15 Uhr')->assertSeeText('Darf ich einen Neuversand auslösen?')->assertSeeText('mit Antwortentwurf')
            ->assertSee('href="'.route('questions.show', ['number' => 'F12345678']).'"', false);
    });
});

describe('result of a question', function () {
    test('without a requested reply the result says so and points to the follow-up, with no editor and no feedback', function () {
        $this->blade('<x-analysis.result :result="$result" number="F12345678" :question="true" />', ['result' => questionResult()])
            ->assertSeeText('Kein Antwortentwurf angefordert')
            ->assertSeeText('Neuversand nur mit Freigabe.')
            ->assertDontSeeText('Noch kein Antwortentwurf – zuerst intern klären')
            ->assertDontSee('aria-label="Antwortentwurf"', false);
    });

    test('a blocked question still asks for the internal clarification first', function () {
        $this->blade('<x-analysis.result :result="$result" number="F12345678" :question="true" />', ['result' => questionResult(['next_step' => 'internal_first'])])
            ->assertSeeText('Noch kein Antwortentwurf – zuerst intern klären')->assertDontSeeText('Kein Antwortentwurf angefordert');
    });

    test('earlier versions link to the question page, not to a ticket', function () {
        $html = $this->blade('<x-analysis.result :result="$result" number="F12345678" :question="true" :history="$history" />', [
            'result' => questionResult(),
            'history' => [
                ['id' => '5f3c2d1e-0000-4000-8000-000000000001', 'removed' => null, 'created_at' => CarbonImmutable::now(), 'staff' => 'Nele', 'group_label' => 'Reseller', 'variant_label' => null, 'assessment' => null, 'confidence' => null, 'test' => false],
                ['id' => '5f3c2d1e-0000-4000-8000-000000000002', 'removed' => null, 'created_at' => CarbonImmutable::now()->subHour(), 'staff' => 'Nele', 'group_label' => 'Reseller', 'variant_label' => null, 'assessment' => null, 'confidence' => null, 'test' => false],
            ],
        ]);

        $html->assertSee(route('questions.show', ['number' => 'F12345678', 'analyse' => '5f3c2d1e-0000-4000-8000-000000000002']), false)->assertDontSee('/tickets/F12345678?', false);
    });

    test('the clarification form of a question posts to the follow-up route, without order fields', function () {
        $result = questionResult(['next_step' => 'internal_first', 'internal_steps' => ['Teamleitung fragen']], ['steps' => ['Teamleitung fragen']]);
        $html = (string) $this->blade('<x-analysis.procedures :result="$result" number="F12345678" :question="true" />', ['result' => $result]);

        expect($html)->toContain('action="'.route('questions.follow-up', ['number' => 'F12345678']).'"')
            ->and($html)->toContain('name="klaerung_analyse"')->not->toContain('name="bestellungen[]"');
    });
});

describe('follow-up', function () {
    test('the latest question offers a follow-up with the choice to get a reply draft', function () {
        $view = $this->blade('<x-question.follow-up :result="$result" number="F12345678" />', ['result' => questionResult()])
            ->assertSeeTextInOrder(['Nachfrage oder Ergänzung', 'keine Kundendaten', 'Antwortentwurf an den Kunden formulieren', 'Neuen Vorschlag erstellen']);
        $html = (string) $view;

        expect($html)->toContain('action="'.route('questions.follow-up', ['number' => 'F12345678']).'"')
            ->and($html)->toContain('name="klaerung_analyse" value="5f3c2d1e-0000-4000-8000-000000000001"')->toContain('name="_token"');
    });

    test('an older version of a question has no follow-up', function () {
        $this->blade('<x-question.follow-up :result="$result" number="F12345678" />', ['result' => questionResult(extra: ['is_latest' => false])])->assertDontSeeText('Nachfrage oder Ergänzung');
    });

    test('the draft box is pre-checked when the question already has a reply draft', function () {
        $html = (string) $this->blade('<x-question.follow-up :result="$result" number="F12345678" />', ['result' => questionResult(extra: ['has_reply' => true])]);

        expect($html)->toMatch('/name="antwortentwurf"[^>]*checked/');
    });
});

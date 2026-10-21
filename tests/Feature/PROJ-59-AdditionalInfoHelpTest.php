<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Prompt;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function ticketPageWith(array $articles): string
{
    fakeZammad($articles);

    return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->getContent();
}

describe('the examples in the field', function () {
    test('before any answer of ours the examples are facts about the case', function () {
        $html = ticketPageWith([zammadArticle(['id' => 1])]);

        expect($html)->toContain('Foto geprüft: Motiv ist verschoben gedruckt')->not->toContain('Es gab schon Kontakt');
    });

    test('after an answer of ours the examples are about the state: decision, state, wish for the reply', function () {
        $html = ticketPageWith([zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'sender' => 'Agent', 'body' => '<p>Wir melden uns.</p>', 'created_at' => '2026-10-02T07:12:00.000Z'])]);

        expect($html)->toContain('Es gab schon Kontakt')->toContain('Entscheidung: Wir erstatten die Preisdifferenz')->toContain('Bitte in der Antwort: für die verspätete Rückmeldung entschuldigen')
            ->not->toContain('Foto geprüft: Motiv ist verschoben gedruckt');
    });

    test('an internal note or an automatic message is no answer of ours', function () {
        $html = ticketPageWith([
            zammadArticle(['id' => 1]),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'internal' => true, 'body' => '<p>Notiz</p>', 'created_at' => '2026-10-02T07:12:00.000Z']),
            zammadArticle(['id' => 3, 'sender' => 'System', 'body' => '<p>Eingangsbestätigung</p>', 'created_at' => '2026-10-02T07:13:00.000Z']),
        ]);

        expect($html)->toContain('Foto geprüft: Motiv ist verschoben gedruckt')->not->toContain('Es gab schon Kontakt');
    });

    test('the hint on how to write it is there in both cases, and the hint line names facts, decisions and wishes', function () {
        foreach ([[zammadArticle(['id' => 1])], [zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'sender' => 'Agent', 'created_at' => '2026-10-02T07:12:00.000Z'])]] as $articles) {
            $html = ticketPageWith($articles);

            expect($html)->toContain('So schreibst du es')->toContain('Entscheidungen')->toContain('Bitte erkläre')->toContain('Fakten, Entscheidungen und Wünsche an die Antwort gelten für die KI als geprüft');
        }
    });

    test('a text that is already in the field stays whatever the examples are', function () {
        fakeZammad([zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'sender' => 'Agent', 'created_at' => '2026-10-02T07:12:00.000Z'])]);
        CaseAgent::fake([[
            'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
            'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
            'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
            'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
        ]]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'kontext' => 'Wir erstatten die Differenz.']);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertSee('Wir erstatten die Differenz.');
        CaseAgent::assertPrompted(fn ($prompt): bool => $prompt->contains('Zusätzliche Informationen vom Mitarbeiter (geprüfte Fakten, Entscheidungen und Wünsche an die Antwort)') && $prompt->contains('Wir erstatten die Differenz.'));
    });
});

describe('the prompt', function () {
    test('it tells facts, decisions and wishes for the reply apart and keeps internals out of the draft', function () {
        $prompt = Prompt::load('analysis');

        expect($prompt->version)->toBe('analysis-2026-10-21.3')
            ->and($prompt->text)->toContain('**Fakten**')->toContain('**Entscheidungen**')->toContain('**Wünsche an die Antwort**')
            ->toContain('ändert nur die Formulierung, nie die Bewertung oder die Befugnis')->toContain('Interna (Namen von Kollegen, interne Kosten und Abläufe) stehen nie im Entwurf')
            ->toContain('Verständnis für die Lage des Kunden');
    });
});

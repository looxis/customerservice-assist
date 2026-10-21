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

function salutationRun(string $customerName): void
{
    fakeZammad([zammadArticle(['id' => 1])], extra: ['zammad.test/api/v1/users/77' => Http::response(['firstname' => $customerName, 'lastname' => '', 'email' => 'erika@example.org'])]);
    CaseAgent::fake([[
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Hallo Emily,'],
    ]]);

    test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->assertRedirect();
}

describe('the salutation', function () {
    test('the name of the customer from the ticket goes to the model in the case section', function () {
        salutationRun('Emily');

        CaseAgent::assertPrompted(fn ($prompt): bool => $prompt->contains('Name des Kunden laut Ticket: Emily'));
    });

    test('an empty name, an address or a very long name is not sent as a name', function () {
        foreach (['', 'kunde@example.org', str_repeat('Langer Name ', 10)] as $name) {
            salutationRun($name);

            CaseAgent::assertNotPrompted(fn ($prompt): bool => $prompt->contains('Name des Kunden laut Ticket'));
        }
    });

    test('the prompt names the section and leaves the form of the greeting to the tone knowledge, the tone knowledge has it', function () {
        $prompt = Prompt::load('analysis');
        $tone = file_get_contents(base_path('knowledge/tone/tone-002-allgemeiner-kundenservice.md'));

        expect($prompt->version)->toBe('analysis-2026-10-21.3')->and($prompt->text)->toContain('Name des Kunden laut Ticket')->toContain('nicht mit „Guten Tag“')
            ->and($tone)->toContain('# Anrede')->toContain('Hallo Emily,')->toContain('Hallo Herr <Nachname>,');
    });
});

describe('the ticket input', function () {
    test('there is no paste button any more, the hint tells to paste with the keyboard', function () {
        $html = $this->get(route('tickets.analyze'))->assertOk()->getContent();

        expect($html)->not->toContain('paste()')->not->toContain('Einfügen')->toContain('mit Strg+V einfügen')->toContain('Ticket laden');
    });
});

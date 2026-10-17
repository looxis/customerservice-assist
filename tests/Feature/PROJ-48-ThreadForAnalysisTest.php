<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Agents\SummaryAgent;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response(thread(threadPattern()))]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function threadAnswer(): array
{
    return [
        'summary' => ['incident' => 'x', 'customer_wish' => 'y'], 'category' => 'product-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antworten.', 'actions' => [], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

/**
 * A thread from letters: C = customer, A = our reply, N = internal note; one message per day.
 */
function thread(string $pattern, ?array &$holder = null): array
{
    $articles = [];

    foreach (str_split($pattern) as $index => $kind) {
        $articles[] = zammadArticle([
            'id' => $index + 1,
            'body' => '<p>'.['C' => 'Kunde', 'A' => 'Antwort', 'N' => 'Notiz'][$kind].' Nummer '.($index + 1).'</p>',
            'created_at' => sprintf('2026-10-%02dT08:00:00.000Z', $index + 1),
            ...($kind === 'C' ? [] : ['sender' => 'Agent', 'from' => 'Nele', 'internal' => $kind === 'N']),
        ]);
    }

    return $articles;
}

/**
 * The thread the faked Zammad answers with; call again to change it within a test.
 */
function threadPattern(?string $pattern = null): string
{
    static $current = 'C';

    return $current = $pattern ?? $current;
}

function fakeThread(string $pattern): void
{
    threadPattern($pattern);
}

function threadAnalyze(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['variante' => 'verlauf', 'kundengruppe' => 'reseller', 'kein_produktbezug' => '1', ...$fields]);
}

describe('our replies after the last customer message', function () {
    test('one request and one reply: the reply stays out and the form says so', function () {
        CaseAgent::fake([threadAnswer()]);
        fakeThread('CA');

        $this->get('/tickets/2137942')->assertOk()->assertSeeText('Unsere Antwort nach der letzten Kundennachricht geht nicht in den Vorschlag ein');
        threadAnalyze()->assertRedirect();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Kunde Nummer 1') && ! $prompt->contains('Antwort Nummer 2'));
    });

    test('request, reply and a new customer message: all three go in and no hint appears', function () {
        CaseAgent::fake([threadAnswer()]);
        fakeThread('CAC');

        $this->get('/tickets/2137942')->assertOk()->assertDontSeeText('geht nicht in den Vorschlag ein');
        threadAnalyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Kunde Nummer 1') && $prompt->contains('Antwort Nummer 2') && $prompt->contains('Kunde Nummer 3'));
    });

    test('only the replies after the last customer message are left out', function () {
        CaseAgent::fake([threadAnswer()]);
        fakeThread('CACA');

        $this->get('/tickets/2137942')->assertSeeText('Unsere Antwort nach der letzten Kundennachricht geht nicht');
        threadAnalyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Antwort Nummer 2') && $prompt->contains('Kunde Nummer 3') && ! $prompt->contains('Antwort Nummer 4'));
    });

    test('several replies are counted and an internal note after the last customer message stays', function () {
        CaseAgent::fake([threadAnswer()]);
        fakeThread('CAAN');

        $this->get('/tickets/2137942')->assertSeeText('Unsere 2 Antworten nach der letzten Kundennachricht gehen nicht in den Vorschlag ein');
        threadAnalyze();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Notiz Nummer 4') && ! $prompt->contains('Antwort Nummer 2') && ! $prompt->contains('Antwort Nummer 3'));
    });

    test('a reply that comes after the proposal does not count as a new message', function () {
        CaseAgent::fake([threadAnswer()]);
        fakeThread('C');
        $location = threadAnalyze()->headers->get('Location');

        fakeThread('CA');

        $this->get($location)->assertOk()->assertDontSeeText('Seit diesem Vorschlag sind neue Nachrichten eingegangen');
    });

    test('a new customer message after the proposal is pointed out', function () {
        CaseAgent::fake([threadAnswer()]);
        fakeThread('C');
        $location = threadAnalyze()->headers->get('Location');

        fakeThread('CAC');

        $this->get($location)->assertOk()->assertSeeText('Seit diesem Vorschlag sind neue Nachrichten eingegangen');
    });
});

describe('the summary offer', function () {
    test('under five messages there is no summary hint at all', function (string $pattern) {
        fakeThread($pattern);

        $this->get('/tickets/2137942')->assertOk()->assertDontSeeText('Zusammenfassung des bisherigen Verlaufs')->assertDontSeeText('Zusammenfassung erstellen');
    })->with(['one message' => 'C', 'request and reply' => 'CA', 'three' => 'CAC', 'four' => 'CACA', 'four with a reply at the end' => 'CACAA']);

    test('from five messages up to the last customer message it is offered with the number', function () {
        fakeThread('CACAC');

        $this->get('/tickets/2137942')->assertOk()->assertSeeText('Zusammenfassung des bisherigen Verlaufs')->assertSeeText('Mindestens 5 Nachrichten im Verlauf (5 vorhanden)')->assertSeeText('Zusammenfassung erstellen');
    });

    test('a reply after the fifth message does not bring the offer forward', function () {
        fakeThread('CACAA');

        $this->get('/tickets/2137942')->assertOk()->assertDontSeeText('Zusammenfassung erstellen');
    });

    test('an existing summary is shown, and later messages make it outdated with a button to renew it', function () {
        SummaryAgent::fake([['facts' => 'Tasse defekt', 'customer_wish' => 'Ersatz', 'measures' => '', 'decisions' => '', 'corrections' => '', 'open_questions' => '']]);
        fakeThread('CACAC');
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']))->assertRedirect();

        $this->get('/tickets/2137942')->assertSeeText('Tasse defekt')->assertDontSeeText('veraltet');

        fakeThread('CACACAC');

        $this->get('/tickets/2137942')->assertSeeText('Tasse defekt')->assertSeeText('Weitere Nachrichten sind vorhanden, die Zusammenfassung ist veraltet.')->assertSeeText('Zusammenfassung neu erstellen');
    });
});

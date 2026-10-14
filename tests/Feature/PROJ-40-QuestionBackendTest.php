<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisOverview;
use App\Analysis\AnalysisStore;
use App\Analysis\AppStats;
use App\Analysis\QuestionNumber;
use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
    ]);
    Cache::flush();

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Neuversand', 'topics' => ['neuversand']], 'Ein Neuversand an Fachhändler braucht keine Rücksprache bis 100 Euro.')]);
    Http::fake();
});

afterEach(fn () => cleanUpKnowledgeBases());

function qAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Frage', 'customer_wish' => 'Auskunft'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Neuversand bis 100 Euro selbst entscheiden.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Regel.', 'knowledge_gaps' => [], 'knowledge_ids' => ['POLICY-001'],
        'confidence' => ['level' => 'HOCH', 'reasons' => ['Regel vorhanden']],
        'next_step' => 'info_only', 'decision' => null, 'internal_steps' => ['Neuversand in EOCS anlegen'], 'reply' => null,
    ], $overrides);
}

function qReply(string $text = 'Guten Tag, wir senden Ihnen die Ware erneut zu.'): array
{
    return ['next_step' => 'reply', 'reply' => ['language' => 'Deutsch', 'text' => $text]];
}

/**
 * @param  array<string, mixed>  $fields
 * @return array{number: string, id: string, response: TestResponse}
 */
function ask(array $fields = [], string $staff = 'Nele'): array
{
    $response = test()->withCookie('staff_name', $staff)->post(route('questions.store'), withProductAnswer(array_merge([
        'text' => 'Darf ich bei einem Fachhändler einen Neuversand ohne Rücksprache auslösen?', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller',
    ], $fields)));

    preg_match('#/frage/(F\d{8})\?analyse=([0-9a-f-]{36})#', (string) $response->headers->get('Location'), $match);

    return ['number' => $match[1] ?? '', 'id' => $match[2] ?? '', 'response' => $response];
}

function followUp(string $number, string $id, array $fields, string $staff = 'Nele')
{
    return test()->withCookie('staff_name', $staff)->post(route('questions.follow-up', ['number' => $number]), ['klaerung_analyse' => $id, ...$fields]);
}

describe('asking', function () {
    test('a question runs the analysis without Zammad and EOCS and shows the proposal under an F number', function () {
        CaseAgent::fake([qAnswer()]);

        $asked = ask(['produkte' => []]);

        expect($asked['number'])->toMatch('/^F\d{8}$/');
        Http::assertNothingSent();
        $stored = app(AnalysisStore::class)->result($asked['id']);
        expect($stored['ticket'])->toBe($asked['number'])->and($stored['question']['text'])->toContain('Neuversand')->and($stored['question']['reply_wanted'])->toBeFalse()
            ->and($stored['inputs']['kundengruppe'])->toBe('reseller')->and($stored['meta']['staff'])->toBe('Nele');

        $this->withCookie('staff_name', 'Nele')->get(route('questions.show', ['number' => $asked['number'], 'analyse' => $asked['id']]))
            ->assertOk()->assertSeeTextInOrder(['Deine Frage', 'Neuversand ohne Rücksprache', 'Vorschlag', 'Neuversand bis 100 Euro selbst entscheiden.', 'Kein Antwortentwurf angefordert', 'Nachfrage oder Ergänzung']);
    });

    test('the prompt names the question, says no reply is wanted, carries the scope and has the extra rules', function () {
        CaseAgent::fake([qAnswer()]);

        ask();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('# Anfrage des Mitarbeiters (kein Ticket)')
            && $prompt->contains('Neuversand ohne Rücksprache')
            && $prompt->contains('Antwortentwurf an den Kunden: nicht gewünscht')
            && $prompt->contains('Reseller')
            && ! $prompt->contains('# Ticket')
            && str_contains((string) $prompt->agent->instructions(), 'Dieser Fall hat kein Ticket'));
        expect(Analysis::query()->sole()->prompt_version)->toContain('+question-');
    });

    test('contact data in the text goes to the provider only as placeholders', function () {
        CaseAgent::fake([qAnswer()]);

        $asked = ask(['text' => 'Kunde max@example.org, Tel. 0151 2345678, wünscht Neuversand.']);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('[E-MAIL_1]') && ! $prompt->contains('max@example.org') && ! $prompt->contains('2345678'));
        expect(app(AnalysisStore::class)->result($asked['id'])['placeholders'])->not->toBe([]);

        $this->withCookie('staff_name', 'Nele')->get(route('questions.show', ['number' => $asked['number']]))->assertSeeText('durch Platzhalter ersetzt');
    });

    test('a wished reply draft is shown, bearbeitbar and can be copied and rated under the F number', function () {
        CaseAgent::fake([qAnswer(qReply())]);

        $asked = ask(['antwortentwurf' => '1']);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Antwortentwurf an den Kunden: gewünscht'));
        $this->withCookie('staff_name', 'Nele')->get(route('questions.show', ['number' => $asked['number']]))->assertSeeText('Antwortentwurf (Deutsch)')->assertSeeText('wir senden Ihnen die Ware erneut zu');

        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => $asked['number'], 'analysis' => $asked['id']]), ['text' => 'Geändert'])->assertOk();
        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.analysis.copied', ['number' => $asked['number'], 'analysis' => $asked['id']]), ['text' => 'Geändert'])->assertOk();
        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.feedback', ['number' => $asked['number'], 'analysis' => $asked['id']]), ['level' => 'slight'])->assertOk();

        expect(Analysis::query()->sole()->feedback_level)->toBe('slight');
    });

    test('an unwanted reply draft of the model is dropped with a note', function () {
        CaseAgent::fake([qAnswer(qReply())]);

        $asked = ask();
        $stored = app(AnalysisStore::class)->result($asked['id']);

        expect($stored['result']['reply'])->toBeNull()->and($stored['result']['next_step'])->toBe('info_only')->and(implode(' ', $stored['notes']))->toContain('kein Antwortentwurf gewünscht');
    });

    test('a wished reply draft that is missing is an unusable result and the model is asked once more', function () {
        CaseAgent::fake([qAnswer(), qAnswer(qReply())]);

        $asked = ask(['antwortentwurf' => '1']);

        expect($asked['id'])->not->toBe('');
        CaseAgent::assertPromptedTimes(2);
    });

    test('a knowledge gap is reported at a question and the redirect goes back to the question', function () {
        CaseAgent::fake([qAnswer()]);
        $asked = ask();

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.gaps.store', ['number' => $asked['number'], 'analysis' => $asked['id']]), ['missing' => 'Regel zur Obergrenze', 'solution' => 'Bis 100 Euro'])
            ->assertRedirect(route('questions.show', ['number' => $asked['number'], 'analyse' => $asked['id']]).'#ergebnis');

        expect(KnowledgeGap::query()->sole()->ticket_number)->toBe($asked['number']);
    });
});

describe('refusals', function () {
    test('an empty, a too long text, an unknown group and an unknown product are refused without a model call', function (array $fields, string $error) {
        CaseAgent::fake([qAnswer()]);

        $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), withProductAnswer(array_merge(['text' => 'Frage', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller'], $fields)))->assertSessionHasErrors($error);
        CaseAgent::assertNeverPrompted();
    })->with([
        'empty' => [['text' => ' '], 'text'],
        'too long' => [['text' => str_repeat('x', 6001)], 'text'],
        'group' => [['kein_produktbezug' => '1', 'kundengruppe' => 'erfunden'], 'kundengruppe'],
        'product' => [['produkte' => ['gibt-es-nicht']], 'produkte.0'],
    ]);

    test('without a chosen name nothing is asked and the input stays', function () {
        CaseAgent::fake([qAnswer()]);

        $this->withCookie('staff_name', '')->post(route('questions.store'), ['text' => 'Frage', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller'])->assertRedirect()->assertSessionHas('error');
        CaseAgent::assertNeverPrompted();
    });

    test('an unreachable provider shows a message with the retry offer and keeps the input', function () {
        CaseAgent::fake(fn () => throw new ConnectionException('down'));

        $response = $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Meine Frage', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller']);

        $response->assertRedirect(route('questions.create'))->assertSessionHas('question_error')->assertSessionHas('question_retry', true);
        $this->followingRedirects()->withCookie('staff_name', 'Nele')->post(route('questions.store'), ['text' => 'Meine Frage', 'kein_produktbezug' => '1', 'kundengruppe' => 'reseller'])->assertSee('Meine Frage');
        expect(Analysis::query()->where('status', 'completed')->count())->toBe(0);
    });

    test('a ticket page and a ticket analysis never work with a question number', function () {
        $this->get('/tickets/F12345678')->assertNotFound();
        $this->withCookie('staff_name', 'Nele')->post('/tickets/F12345678/analyse', ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->assertNotFound();
        Http::assertNothingSent();
    });

    test('questions of others stay closed, an admin may read them, an unknown number is not found', function () {
        CaseAgent::fake([qAnswer()]);
        $asked = ask();

        $this->withCookie('staff_name', 'Cara')->get(route('questions.show', ['number' => $asked['number']]))->assertForbidden();
        $this->withCookie('staff_name', 'Etienne')->get(route('questions.show', ['number' => $asked['number']]))->assertOk();
        $this->withCookie('staff_name', 'Nele')->get(route('questions.show', ['number' => 'F00000000']))->assertNotFound();
        $this->get('/frage/F12')->assertNotFound();
    });
});

describe('following up', function () {
    test('a follow-up keeps the text and the scope, adds the addition as a fact and can ask for the reply draft', function () {
        CaseAgent::fake([qAnswer(), qAnswer(qReply('Guten Tag, die Ware geht heute raus.'))]);
        $asked = ask();

        $second = followUp($asked['number'], $asked['id'], ['klaerung_text' => 'Der Kunde ruft gerade an, Betrag 80 Euro.', 'antwortentwurf' => '1']);
        parse_str((string) parse_url((string) $second->headers->get('Location'), PHP_URL_QUERY), $query);

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Nachfrage des Mitarbeiters → Der Kunde ruft gerade an, Betrag 80 Euro.')
            && $prompt->contains('Neuversand ohne Rücksprache') && $prompt->contains('Antwortentwurf an den Kunden: gewünscht') && $prompt->contains('Reseller'));
        $stored = app(AnalysisStore::class)->result($query['analyse']);
        expect($query['analyse'])->not->toBe($asked['id'])->and($stored['ticket'])->toBe($asked['number'])->and($stored['result']['reply']['text'])->toContain('geht heute raus')
            ->and($stored['decisions'])->toHaveCount(1)->and($stored['question']['reply_wanted'])->toBeTrue();
    });

    test('a follow-up without the field keeps the earlier wish, an unticked box turns it off', function () {
        CaseAgent::fake([qAnswer(qReply()), qAnswer(qReply()), qAnswer()]);
        $asked = ask(['antwortentwurf' => '1']);

        $second = followUp($asked['number'], $asked['id'], ['klaerung_text' => 'Noch eine Info']);
        parse_str((string) parse_url((string) $second->headers->get('Location'), PHP_URL_QUERY), $query);
        expect(app(AnalysisStore::class)->result($query['analyse'])['question']['reply_wanted'])->toBeTrue();

        $third = followUp($asked['number'], $query['analyse'], ['klaerung_text' => 'Doch ohne Entwurf', 'antwortentwurf' => '0']);
        parse_str((string) parse_url((string) $third->headers->get('Location'), PHP_URL_QUERY), $query3);
        expect(app(AnalysisStore::class)->result($query3['analyse'])['question']['reply_wanted'])->toBeFalse();
    });

    test('the clarification of a blocked question works with an option and continues the case', function () {
        $decision = ['question' => 'Besteht Bedarf?', 'why' => 'x', 'options' => [['key' => 'ja', 'label' => 'Ja, Bedarf', 'consequence' => 'a'], ['key' => 'nein', 'label' => 'Nein, kein Bedarf', 'consequence' => 'b']], 'knowledge_ids' => []];
        CaseAgent::fake([qAnswer(['next_step' => 'internal_first', 'decision' => $decision]), qAnswer()]);
        $asked = ask();

        $this->withCookie('staff_name', 'Nele')->get(route('questions.show', ['number' => $asked['number']]))->assertSeeTextInOrder(['Noch kein Antwortentwurf', 'Zuerst zu klären', 'Besteht Bedarf?', 'Ja, Bedarf']);

        followUp($asked['number'], $asked['id'], ['klaerung_option' => 'nein'])->assertRedirect();
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Besteht Bedarf? → Nein, kein Bedarf'));
    });

    test('an unknown option, no content, an older version, a foreign question and a ticket analysis are refused', function () {
        CaseAgent::fake([qAnswer(), qAnswer()]);
        $asked = ask();

        followUp($asked['number'], $asked['id'], ['klaerung_option' => 'erfunden'])->assertSessionHasErrors('klaerung_option');
        followUp($asked['number'], $asked['id'], [])->assertSessionHasErrors('klaerung_text');
        followUp($asked['number'], $asked['id'], ['klaerung_text' => 'x'], 'Cara')->assertSessionHasErrors('klaerung_analyse');
        followUp('F00000001', $asked['id'], ['klaerung_text' => 'x'])->assertSessionHasErrors('klaerung_analyse');

        $newer = followUp($asked['number'], $asked['id'], ['klaerung_text' => 'Neuer Stand']);
        $newer->assertSessionHasNoErrors();
        followUp($asked['number'], $asked['id'], ['klaerung_text' => 'Auf alter Version'])->assertSessionHasErrors('klaerung_analyse');
        CaseAgent::assertPromptedTimes(2);
    });
});

describe('lists and figures', function () {
    test('the page lists only the own questions, newest first, one entry per question, with a text beginning', function () {
        CaseAgent::fake([qAnswer(), qAnswer(), qAnswer(), qAnswer()]);
        $first = ask(['text' => 'Erste Frage zum Neuversand']);
        followUp($first['number'], $first['id'], ['klaerung_text' => 'Ergänzung']);
        ask(['text' => 'Zweite Frage zur Retoure']);
        ask(['text' => 'Frage von Cara'], 'Cara');

        $recent = app(AnalysisStore::class)->recentQuestions('Nele', 10);

        expect($recent)->toHaveCount(2)->and($recent[0]['excerpt'])->toBe('Zweite Frage zur Retoure')->and($recent[1]['excerpt'])->toBe('Erste Frage zum Neuversand')->and($recent[1]['number'])->toBe($first['number']);
        $this->withCookie('staff_name', 'Nele')->get(route('questions.create'))->assertSeeTextInOrder(['Meine letzten Fragen', 'Zweite Frage zur Retoure', 'Erste Frage zum Neuversand'])->assertDontSeeText('Frage von Cara');
    });

    test('the list is limited and shows a note where the content was deleted', function () {
        config(['analysis.question_history' => 1]);
        Analysis::factory()->create(['ticket_number' => 'F11111111', 'staff_name' => 'Nele', 'content' => null]);
        Analysis::factory()->create(['ticket_number' => 'F22222222', 'staff_name' => 'Nele', 'content' => null]);

        expect(app(AnalysisStore::class)->recentQuestions('Nele', (int) config('analysis.question_history')))->toHaveCount(1)
            ->and(app(AnalysisStore::class)->recentQuestions('Nele', 5)[0]['excerpt'])->toBe('Inhalt gelöscht');
    });

    test('questions do not count as processed tickets and are marked and filterable in the overview', function () {
        Analysis::factory()->create(['ticket_number' => '2100001']);
        Analysis::factory()->create(['ticket_number' => 'F12345678', 'staff_name' => 'Nele']);

        expect(app(AppStats::class)->processedTickets())->toBe(1);

        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index'))->assertSeeText('Frage')->assertSee('href="'.route('questions.show', ['number' => 'F12345678']), false)->assertSee('Ticket#2100001', false);
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['quelle' => 'questions']))->assertSee('F12345678')->assertDontSee('Ticket#2100001', false);
        $this->withCookie('staff_name', 'Etienne')->get(route('analysis-overview.index', ['quelle' => 'tickets']))->assertDontSee('F12345678')->assertSee('Ticket#2100001', false);
        expect(AnalysisOverview::SOURCES)->toHaveKeys(['tickets', 'questions']);
    });

    test('question numbers are an F and eight digits and never the number of a ticket', function () {
        $number = QuestionNumber::generate();

        expect($number)->toMatch('/^F\d{8}$/')->and(QuestionNumber::isQuestion($number))->toBeTrue()->and(QuestionNumber::isQuestion('2137942'))->toBeFalse()->and(QuestionNumber::isQuestion('F123'))->toBeFalse();
    });
});

describe('QA probes', function () {
    test('the question text is stored encrypted and shown escaped', function () {
        CaseAgent::fake([qAnswer()]);
        $text = 'Geheimer Satz XYZ <img src=x onerror=alert(1)> bitte beantworten';
        $q = ask(['text' => $text]);

        expect(Analysis::query()->where('uuid', $q['id'])->value('content'))->not->toContain('Geheimer Satz XYZ');
        $html = test()->withCookie('staff_name', 'Nele')->get(route('questions.show', ['number' => $q['number'], 'analyse' => $q['id']]))->getContent();
        expect($html)->not->toContain('<img src=x onerror=alert(1)>');
    });

    test('a ticket number in the text is not looked up in Zammad', function () {
        CaseAgent::fake([qAnswer()]);
        Http::fake();

        ask(['text' => 'Ticket#2139435 betrifft einen Neuversand, was darf ich?']);

        Http::assertNothingSent();
    });
});

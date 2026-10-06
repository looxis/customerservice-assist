<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use App\Knowledge\KnowledgeLibrary;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

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
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'], 'Bestellungen werden nicht zusammengeführt.'),
        'policies/policy-002-b.md' => knowledgeDoc(['id' => 'POLICY-002', 'title' => 'Aktive Regel', 'status' => 'active'], 'Immer freundlich bleiben.'),
    ]);

    $this->articles = [zammadArticle(['id' => 1, 'body' => '<p>Können Sie zwei Bestellungen zusammenführen?</p>'])];
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles)]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * Add messages to the faked ticket thread.
 */
function addArticles(array ...$articles): void
{
    test()->articles = [...test()->articles, ...$articles];
}

function resultAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Zwei Bestellungen', 'customer_wish' => 'Eine Rechnung'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Freundlich absagen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Laut Regel nicht möglich.', 'missing_information' => [], 'knowledge_gaps' => [],
        'knowledge_ids' => ['POLICY-001', 'POLICY-002'],
        'confidence' => ['level' => 'HOCH', 'reasons' => ['Regel vorhanden']],
        'internal_todos' => ['Nichts weiter'],
        'reply' => ['language' => 'Deutsch', 'text' => "Guten Tag,\nleider geht das nicht. Sendungsnummer: [TRACKINGNUMMER]"],
    ], $overrides);
}

function runAnalysis(array $fields = [], string $staff = 'Nele'): string
{
    $location = test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), array_merge([
        'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'kontext' => 'Telefonat geführt',
    ], $fields))->headers->get('Location');

    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return $query['analyse'];
}

function saveReply(string $id, array $data, string $staff = 'Cara'): TestResponse
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), $data);
}

describe('layout', function () {
    test('the result comes before the form, with what to do first, then the reply, then collapsed sections', function () {
        CaseAgent::fake([resultAnswer()]);
        runAnalysis();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Ergebnis der Analyse', 'Was ist zu tun?', 'Freundlich absagen.', 'Du darfst das selbst entscheiden.', 'Antwortentwurf (Deutsch)', 'Kopieren', 'Begründung', 'Quellen (2)', 'Kurzfassung', 'Confidence HOCH – Gründe (1)', 'Interne To-dos (1)', 'Details zur Analyse', 'Analyse', 'Analysiert mit:', 'Verlauf'])
            ->getContent();

        expect($html)->toMatch('/<details id="analyse-details"[^>]*x-data/')
            ->and($html)->not->toMatch('/<details id="analyse-details"[^>]*\sopen\s/');
    });

    test('the collapsed form names group, products and variant and reopens with the inputs of the analysis', function () {
        CaseAgent::fake([resultAnswer()]);
        runAnalysis(['produkte' => [], 'kontext' => 'Telefonat geführt']);

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeText('Analysiert mit: Foto-Fachhändler / Reseller · kein Produktbezug · Ganzer Verlauf')
            ->getContent();

        expect($html)->toContain('>Telefonat geführt</textarea>')->toMatch('/<option value="reseller"\s+selected/');
    });

    test('approval needed is shown in plain words', function () {
        CaseAgent::fake([resultAnswer(['authority' => ['agent_may_decide' => false, 'approval_by' => 'Teamleitung', 'permission_id' => null]])]);
        runAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSeeText('Freigabe nötig durch Teamleitung.');
    });

    test('without an analysis the form is open and there is no result', function () {
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertDontSeeText('Ergebnis der Analyse')
            ->assertDontSee('analyse-details', false);
    });
});

describe('reply draft', function () {
    test('the draft is editable, names open places and offers copy', function () {
        CaseAgent::fake([resultAnswer()]);
        runAnalysis();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();

        expect($html)->toContain('aria-label="Antwortentwurf"')
            ->toContain('Sendungsnummer: [TRACKINGNUMMER]</textarea>')
            ->toContain('noch ausfüllen (in eckigen Klammern)')
            ->toContain('trotzdem kopieren?');
    });

    test('an edit is saved and shown to colleagues with who edited it; the original stays', function () {
        CaseAgent::fake([resultAnswer()]);
        $id = runAnalysis();
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 6)->setTime(15, 30));

        saveReply($id, ['text' => 'Guten Tag, es tut uns leid.'])->assertOk()->assertJson(['saved' => true, 'edited' => 'bearbeitet von Cara am 06.10.2026, 15:30 Uhr']);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeText('bearbeitet von Cara am 06.10.2026, 15:30 Uhr')
            ->assertSee('Guten Tag, es tut uns leid.</textarea>', false);

        expect(app(AnalysisStore::class)->result($id)['result']['reply']['text'])->toStartWith('Guten Tag,');
    });

    test('restoring the original removes the edit', function () {
        CaseAgent::fake([resultAnswer()]);
        $id = runAnalysis();
        saveReply($id, ['text' => 'Geändert']);

        saveReply($id, ['original' => true])->assertOk()->assertJson(['edited' => null]);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertDontSeeText('bearbeitet von')->assertSee('leider geht das nicht.', false);
    });

    test('saving needs a chosen name, a known analysis of this ticket and a limited length', function () {
        CaseAgent::fake([resultAnswer()]);
        $id = runAnalysis();

        $this->defaultCookies = [];
        $this->withCredentials()->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['text' => 'x'])->assertStatus(409);
        $this->withCredentials()->withCookie('staff_name', 'Cara')->putJson(route('tickets.analysis.reply', ['number' => '2137943', 'analysis' => $id]), ['text' => 'x'])->assertNotFound();
        saveReply((string) Str::uuid(), ['text' => 'x'])->assertNotFound();
        saveReply($id, ['text' => str_repeat('x', 20_001)])->assertUnprocessable();
        $this->withCookie('staff_name', 'Cara')->put(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => 'kein-uuid']), ['text' => 'x'])->assertNotFound();
    });

    test('an edited draft is never written to the log', function () {
        CaseAgent::fake([resultAnswer()]);
        $id = runAnalysis();
        Log::spy();

        saveReply($id, ['text' => 'Geheimer Entwurfstext']);

        Log::shouldNotHaveReceived('info', fn (string $message, array $context = []): bool => str_contains(json_encode([$message, $context]), 'Geheimer'));
    });
});

describe('sources', function () {
    test('sources show id, title, type, draft mark and the text as analysed, with a link to the overview', function () {
        CaseAgent::fake([resultAnswer()]);
        runAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Quellen (2)', 'POLICY-001', 'Allgemeine Regel', 'policy', 'Entwurf', 'Bestellungen werden nicht zusammengeführt.', 'In der Knowledge-Übersicht öffnen', 'POLICY-002', 'Aktive Regel'])
            ->assertSeeText('Beruht teilweise auf Entwurfs-Wissen – bitte kritisch prüfen: POLICY-001');
    });

    test('a source changed since the analysis keeps the old text and is marked, a removed one too', function () {
        CaseAgent::fake([resultAnswer()]);
        runAnalysis();

        file_put_contents(config('knowledge.path').'/policies/policy-001-a.md', knowledgeDoc(['title' => 'Allgemeine Regel'], 'Neue Fassung.'));
        unlink(config('knowledge.path').'/policies/policy-002-b.md');
        app(KnowledgeLibrary::class)->refresh();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['POLICY-001', 'Seit der Analyse geändert', 'Bestellungen werden nicht zusammengeführt.', 'POLICY-002', 'nicht mehr vorhanden'])
            ->assertDontSeeText('Neue Fassung.');
    });
});

describe('finding the latest analysis', function () {
    test('the latest analysis appears when the ticket is opened, with who made it', function () {
        CaseAgent::fake([resultAnswer()]);
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 6)->setTime(9, 15));
        runAnalysis();

        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')->assertSeeText('Analyse von Nele, 06.10.2026, 09:15 Uhr')->assertDontSeeText('neue Nachrichten eingegangen');
    });

    test('new messages since the analysis are pointed out with "Neu analysieren"', function () {
        CaseAgent::fake([resultAnswer()]);
        runAnalysis();
        addArticles(zammadArticle(['id' => 2, 'body' => '<p>Und?</p>', 'created_at' => '2026-10-03T07:12:00.000Z']));

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeText('Seit dieser Analyse sind neue Nachrichten eingegangen.')
            ->assertSee('open-analysis-form', false);
    });

    test('a new analysis replaces the shown one, the earlier one stays stored', function () {
        CaseAgent::fake([resultAnswer(), resultAnswer(['recommendation' => 'Zweiter Vorschlag.'])]);
        $first = runAnalysis();
        runAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSeeText('Zweiter Vorschlag.')->assertDontSeeText('Freundlich absagen.');
        expect(app(AnalysisStore::class)->result($first))->not->toBeNull();
    });

    test('an analysis that is no longer available is pointed out', function () {
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.Str::uuid())->assertSeeText('Diese Analyse ist nicht mehr verfügbar.');
    });

    test('a test run is shown only for its cut point', function () {
        CaseAgent::fake([resultAnswer()]);
        addArticles(zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Antwort</p>', 'created_at' => '2026-10-02T07:12:00.000Z']));
        test()->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1');
        runAnalysis(['stand' => '1'], 'Etienne');

        $this->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1')->get('/tickets/2137942?stand=1')->assertSeeText('Testlauf (Stand bis Nachricht vom');
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertDontSeeText('Ergebnis der Analyse');
    });

    test('results from before PROJ-10 without the new fields are still shown', function () {
        $store = app(AnalysisStore::class);
        $id = $store->putResult(['ticket' => '2137942', 'result' => resultAnswer(), 'notes' => [], 'placeholders' => [], 'draft_ids' => ['POLICY-001'],
            'meta' => ['staff' => 'Nele', 'created_at' => now()->toIso8601String(), 'model' => 'gpt-5.5', 'prompt_version' => 'analysis-x', 'knowledge_state' => 'abc', 'knowledge_fingerprints' => ['POLICY-001' => 'x'], 'duration_ms' => 1000]]);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)
            ->assertSeeTextInOrder(['Ergebnis der Analyse', 'Antwortentwurf', 'Quellen (2)', 'POLICY-001', 'Der Text aus der Zeit der Analyse ist nicht gespeichert.']);
    });
});

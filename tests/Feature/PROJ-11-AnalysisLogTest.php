<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\Agents\SummaryAgent;
use App\Models\Analysis;
use App\Models\TicketCaseChoice;
use App\Models\TicketSummary;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);

    $this->articles = [zammadArticle(['id' => 1, 'body' => '<p>Die Tasse ist kaputt. Erreichbar unter erika@example.org</p>'])];
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles)]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function logAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Tasse kaputt', 'customer_wish' => 'Ersatz'],
        'category' => 'complaint', 'case_pattern' => null, 'assessment' => 'unklar',
        'recommendation' => 'Foto anfordern.', 'actions' => ['photo-request'],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Ohne Foto unklar.', 'missing_information' => [], 'knowledge_gaps' => [],
        'knowledge_ids' => ['POLICY-001'], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'internal_todos' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Bitte senden Sie ein Foto an uns, [E-MAIL_1].'],
    ], $overrides);
}

function logAnalysis(string $staff = 'Nele', array $fields = []): string
{
    $location = test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), array_merge([
        'kundengruppe' => 'reseller', 'variante' => 'verlauf', 'kontext' => 'Foto am Telefon besprochen',
    ], $fields))->headers->get('Location');

    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return $query['analyse'];
}

describe('lasting storage', function () {
    test('an analysis is stored with its figures in columns and its content encrypted, including sent text and raw answer', function () {
        CaseAgent::fake([logAnswer()]);
        $id = logAnalysis();

        $row = DB::table('analyses')->where('uuid', $id)->first();

        expect($row)->ticket_number->toBe('2137942')
            ->staff_name->toBe('Nele')
            ->status->toBe('completed')
            ->customer_group->toBe('reseller')
            ->category->toBe('complaint')
            ->assessment->toBe('unklar')
            ->confidence->toBe('MITTEL')
            ->model->toBe('gpt-5.5')
            ->and(json_decode($row->knowledge_ids))->toBe(['POLICY-001'])
            ->and($row->content)->not->toContain('Tasse kaputt')->not->toContain('Telefon');

        $content = Analysis::query()->where('uuid', $id)->first()->content;

        expect($content['sent_input'])->toContain('Die Tasse ist kaputt.')->toContain('[E-MAIL_1]')->not->toContain('erika@example.org')
            ->and($content['raw_response']['recommendation'])->toBe('Foto anfordern.')
            ->and($content['inputs']['kontext'])->toBe('Foto am Telefon besprochen');
    });

    test('a failed analysis is logged without content', function () {
        CaseAgent::fake(fn () => throw new ConnectionException('down'));

        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'variante' => 'verlauf']);

        expect(Analysis::query()->sole())->status->toBe('failed')->error->toBe('Unavailable')->staff_name->toBe('Nele')->content->toBeNull();
    });

    test('analyses, drafts, summaries and choices survive a cleared cache and more than seven days', function () {
        CaseAgent::fake([logAnswer()]);
        $id = logAnalysis();
        test()->withCredentials()->withCookie('staff_name', 'Cara')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['text' => 'Bearbeitet'])->assertOk();

        Cache::flush();
        $this->travel(30)->days();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeText('Ergebnis der Analyse')
            ->assertSee('Bearbeitet</textarea>', false)
            ->assertSeeText('Foto-Fachhändler / Reseller');
    });

    test('a summary is no longer marked as stored temporarily', function () {
        SummaryAgent::fake([['facts' => 'Fakten', 'customer_wish' => '', 'measures' => '', 'decisions' => '', 'corrections' => '', 'open_questions' => '']]);
        $this->articles = [
            zammadArticle(['id' => 1, 'body' => '<p>Erste</p>']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Antwort</p>', 'created_at' => '2026-10-02T07:12:00.000Z']),
            zammadArticle(['id' => 3, 'body' => '<p>Zweite</p>', 'created_at' => '2026-10-03T07:12:00.000Z']),
        ];

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.summary.create', ['number' => '2137942']));

        $this->get('/tickets/2137942')->assertSeeText('Fakten')->assertDontSeeText('vorübergehend gespeichert');
    });
});

describe('earlier analyses', function () {
    test('with several analyses the list names date, name, group, variant, assessment and confidence', function () {
        CaseAgent::fake([logAnswer(), logAnswer(['assessment' => 'berechtigt', 'confidence' => ['level' => 'HOCH', 'reasons' => []]])]);
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 6)->setTime(9, 0));
        logAnalysis('Nele');
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 6)->setTime(10, 0));
        logAnalysis('Cara');

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Frühere Analysen (1)', '06.10.2026, 10:00 Uhr', 'Cara', 'Foto-Fachhändler / Reseller', 'Ganzer Verlauf', 'Berechtigt', 'HOCH', 'angezeigt', '06.10.2026, 09:00 Uhr', 'Nele', 'Unklar', 'MITTEL']);
    });

    test('an older analysis is shown read-only with a way back to the newest, and saving it is refused', function () {
        CaseAgent::fake([logAnswer(), logAnswer(['recommendation' => 'Neuester Vorschlag.'])]);
        $old = logAnalysis();
        $this->travel(1)->minutes();
        logAnalysis();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$old)
            ->assertSeeText('Ältere Analyse vom')
            ->assertSeeText('Zur neuesten')
            ->assertDontSeeText('Original der KI wiederherstellen')
            ->getContent();

        expect($html)->toMatch('/<textarea[^>]*\sreadonly[^>]*aria-label="Antwortentwurf"|<textarea[^>]*aria-label="Antwortentwurf"[^>]*\sreadonly/s');

        test()->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $old]), ['text' => 'x'])->assertStatus(409);
    });

    test('with a single analysis there is no list', function () {
        CaseAgent::fake([logAnswer()]);
        logAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertDontSeeText('Frühere Analysen');
    });

    test('test runs do not appear among the analyses of the real ticket', function () {
        CaseAgent::fake([logAnswer(), logAnswer()]);
        logAnalysis();
        $this->withCookie('test_mode', '1');
        logAnalysis('Etienne', ['stand' => '1']);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertDontSeeText('Frühere Analysen')->assertDontSeeText('Testlauf');
    });
});

describe('log for admins', function () {
    test('admins see the sent text, the raw answer and the metadata; others do not', function () {
        CaseAgent::fake([logAnswer()]);
        logAnalysis();

        $this->withCookie('staff_name', 'Etienne')->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Protokoll (nur Admins)', 'An die KI gesendet', 'Die Tasse ist kaputt.', 'Antwort der KI (unverändert)', 'Foto anfordern.', 'Metadaten', 'prompt_version']);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertDontSeeText('Protokoll (nur Admins)')
            ->assertSeeText('Details zur Analyse');
    });
});

describe('retention and deletion', function () {
    test('after 12 months content is emptied, figures stay, summaries and choices go', function () {
        CaseAgent::fake([logAnswer(), logAnswer()]);
        $id = logAnalysis();
        TicketSummary::query()->create(['scope_key' => '2137942', 'ticket_number' => '2137942', 'content' => ['text' => 'x']]);

        $this->travel(12)->months();
        $this->travel(1)->days();
        logAnalysis();
        $this->artisan('analysis:purge')->expectsOutputToContain('Analysen bereinigt: 1')->assertSuccessful();

        $row = Analysis::query()->where('uuid', $id)->first();
        expect($row->content)->toBeNull()
            ->and($row->content_purged_at)->not->toBeNull()
            ->and($row->confidence)->toBe('MITTEL')
            ->and(TicketSummary::query()->count())->toBe(0)
            ->and(Analysis::query()->whereNotNull('content')->count())->toBe(1)
            ->and(TicketCaseChoice::query()->count())->toBe(1);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSeeText('Inhalte nach 12 Monaten gelöscht');
    });

    test('the purge runs daily', function () {
        $events = collect(app(Schedule::class)->events())->map->command;

        expect($events->contains(fn (?string $command): bool => str_contains((string) $command, 'analysis:purge')))->toBeTrue();
    });

    test('an admin deletes all analyses of a ticket after confirmation; a note stays', function () {
        CaseAgent::fake([logAnswer()]);
        logAnalysis();
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 7)->setTime(8, 30));

        $html = $this->withCookie('staff_name', 'Etienne')->get('/tickets/2137942')->assertSeeText('Alle Analysen dieses Tickets löschen')->getContent();
        expect($html)->toContain('endgültig löschen?');

        $this->withCookie('staff_name', 'Etienne')->delete(route('tickets.analyses.destroy', ['number' => '2137942']))->assertRedirect(route('tickets.show', ['number' => '2137942']));

        expect(Analysis::query()->sole())->content->toBeNull()->content_deleted_by->toBe('Etienne')->confidence->toBe('MITTEL')
            ->and(TicketCaseChoice::query()->count())->toBe(0);

        $this->withCookie('staff_name', 'Etienne')->get('/tickets/2137942')
            ->assertSeeText('Inhalte gelöscht von Etienne am 07.10.2026, 08:30 Uhr.')
            ->assertDontSeeText('Ergebnis der Analyse');
    });

    test('others cannot delete and see no button', function () {
        CaseAgent::fake([logAnswer()]);
        logAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertDontSeeText('Alle Analysen dieses Tickets löschen');
        $this->withCookie('staff_name', 'Nele')->delete(route('tickets.analyses.destroy', ['number' => '2137942']))->assertForbidden();

        expect(Analysis::query()->sole()->content)->not->toBeNull();
    });
});

describe('qa additions', function () {
    test('failed analyses do not appear among earlier analyses', function () {
        CaseAgent::fake([logAnswer()]);
        logAnalysis();
        CaseAgent::fake(fn () => throw new ConnectionException('down'));
        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'variante' => 'verlauf']);

        expect(Analysis::query()->count())->toBe(2);
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertDontSeeText('Frühere Analysen');
    });

    test('deleting also removes summaries of test runs; a new analysis afterwards replaces the note', function () {
        CaseAgent::fake([logAnswer(), logAnswer(['recommendation' => 'Nach dem Löschen.'])]);
        logAnalysis();
        TicketSummary::query()->create(['scope_key' => '2137942.stand-1', 'ticket_number' => '2137942', 'content' => ['text' => 'x']]);

        $this->withCookie('staff_name', 'Etienne')->delete(route('tickets.analyses.destroy', ['number' => '2137942']));
        expect(TicketSummary::query()->count())->toBe(0);

        $this->travel(1)->minutes();
        logAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')
            ->assertSeeText('Nach dem Löschen.')
            ->assertDontSeeText('Inhalte gelöscht von')
            ->assertSeeTextInOrder(['Frühere Analysen (1)', 'Inhalte gelöscht']);
    });

    test('a purged or deleted analysis cannot be opened or edited', function () {
        CaseAgent::fake([logAnswer()]);
        $id = logAnalysis();
        $this->withCookie('staff_name', 'Etienne')->delete(route('tickets.analyses.destroy', ['number' => '2137942']));

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertSeeText('Diese Analyse ist nicht mehr verfügbar.');
        test()->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['text' => 'x'])->assertNotFound();
    });

    test('the log for admins escapes its content', function () {
        CaseAgent::fake([logAnswer(['recommendation' => '<script>alert(1)</script>'])]);
        logAnalysis();

        expect($this->withCookie('staff_name', 'Etienne')->get('/tickets/2137942')->getContent())->not->toContain('<script>alert(1)</script>');
    });
});

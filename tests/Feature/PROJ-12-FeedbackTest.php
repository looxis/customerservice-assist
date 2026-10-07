<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use App\Models\Analysis;
use App\Models\KnowledgeGap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.admins' => ['Etienne'],
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    $this->articles = [zammadArticle(['id' => 1, 'body' => '<p>Können zwei Bestellungen auf eine Rechnung?</p>'])];
    fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => fn () => Http::response($this->articles)]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function feedbackAnswer(array $overrides = []): array
{
    return array_replace([
        'summary' => ['incident' => 'Zwei Bestellungen', 'customer_wish' => 'Eine Rechnung'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Nachfragen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Keine Regel.', 'missing_information' => [],
        'knowledge_gaps' => [['topic' => 'Bestellungen zusammenführen', 'question' => 'Geht eine gemeinsame Rechnung?']],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'internal_todos' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag, wir prüfen das.'],
    ], $overrides);
}

function feedbackAnalysis(array $fields = [], string $staff = 'Nele'): string
{
    CaseAgent::fake([feedbackAnswer()]);
    $location = test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), array_merge(['kundengruppe' => 'reseller', 'variante' => 'verlauf'], $fields))->headers->get('Location');
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return $query['analyse'];
}

function rate(string $id, array $data, string $staff = 'Nele'): TestResponse
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->putJson(route('tickets.analysis.feedback', ['number' => '2137942', 'analysis' => $id]), $data);
}

describe('feedback', function () {
    test('the result asks after copying with the four levels and a suggestion from the change', function () {
        feedbackAnalysis();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();

        expect($html)->toContain('Wie brauchbar war der Vorschlag?')
            ->toContain('x-on:reply-copied.window')
            ->toContain('unverändert nutzbar')->toContain('leicht angepasst')->toContain('stark angepasst')->toContain('verworfen')
            ->toContain('<= 0.2 ? \'slight\' : \'major\'')
            ->toContain('Vorschlag bewerten');
    });

    test('a level is saved with name, time, suggestion and comment; a new rating replaces it', function () {
        $id = feedbackAnalysis();
        $this->travelTo(now()->setTimezone('Europe/Berlin')->setDate(2026, 10, 7)->setTime(11, 0));

        rate($id, ['level' => 'slight', 'suggested' => 'slight', 'comment' => 'Anrede fehlte'])->assertOk()
            ->assertJson(['level' => 'slight', 'label' => 'leicht angepasst', 'by' => 'Bewertet von Nele am 07.10.2026, 11:00 Uhr']);

        rate($id, ['level' => 'discarded', 'suggested' => 'major'], 'Cara')->assertOk();

        $row = Analysis::query()->where('uuid', $id)->first();
        expect($row)->feedback_level->toBe('discarded')->feedback_suggested->toBe('major')->feedback_by->toBe('Cara')->feedback_comment->toBeNull()
            ->and(DB::table('analyses')->where('uuid', $id)->value('feedback_comment'))->toBeNull();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSee('Bewertet von Cara am', false);
    });

    test('the comment is stored encrypted', function () {
        $id = feedbackAnalysis();
        rate($id, ['level' => 'major', 'comment' => 'Ton zu trocken'])->assertOk();

        expect(DB::table('analyses')->where('uuid', $id)->value('feedback_comment'))->not->toContain('Ton zu trocken')
            ->and(Analysis::query()->where('uuid', $id)->first()->feedback_comment)->toBe('Ton zu trocken');
    });

    test('invalid levels, too long comments, unknown analyses and missing names are refused', function () {
        $id = feedbackAnalysis();

        rate($id, ['level' => 'super'])->assertUnprocessable();
        rate($id, ['level' => 'slight', 'comment' => str_repeat('x', 2001)])->assertUnprocessable();
        rate((string) Str::uuid(), ['level' => 'slight'])->assertNotFound();
        $this->defaultCookies = [];
        $this->withCredentials()->putJson(route('tickets.analysis.feedback', ['number' => '2137942', 'analysis' => $id]), ['level' => 'slight'])->assertStatus(409);
    });

    test('older analyses can be rated and the list of earlier analyses shows the level', function () {
        $old = feedbackAnalysis();
        $this->travel(1)->minutes();
        feedbackAnalysis();

        rate($old, ['level' => 'unchanged'])->assertOk();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSeeTextInOrder(['Frühere Analysen (1)', 'unverändert nutzbar']);
    });
});

describe('reporting a knowledge gap', function () {
    test('a gap named by the ai has a report button that prefills the form', function () {
        feedbackAnalysis();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();

        expect($html)->toContain('Lücke melden')->toContain('report-gap')->toContain('Bestellungen zusammenführen: Geht eine gemeinsame Rechnung?')
            ->toContain('Bitte keine Kundendaten eintragen')
            ->toContain('So lösen wir das / so habe ich entschieden (optional)');
    });

    test('a report is stored with ticket, analysis, group, products and name, encrypted; the button turns into "bereits gemeldet"', function () {
        $id = feedbackAnalysis(['produkte' => []]);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), [
            'missing' => 'Regel zum Zusammenführen', 'solution' => 'Geht nicht, Kunde bestellt neu.', 'comment' => 'Häufig', 'topic' => 'Bestellungen zusammenführen',
        ])->assertRedirect();

        $gap = KnowledgeGap::query()->sole();
        expect($gap)->ticket_number->toBe('2137942')->staff_name->toBe('Nele')->customer_group->toBe('reseller')->status->toBe('open')->test_run->toBeFalse()
            ->and($gap->content)->toBe(['missing' => 'Regel zum Zusammenführen', 'solution' => 'Geht nicht, Kunde bestellt neu.', 'comment' => 'Häufig'])
            ->and(DB::table('knowledge_gaps')->value('content'))->not->toContain('Zusammenführen');

        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942?analyse='.$id)
            ->assertSeeText('bereits gemeldet von Nele');
    });

    test('a report without "Was fehlt?" is refused at the field with the input kept', function () {
        $id = feedbackAnalysis();

        $this->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), ['missing' => '', 'solution' => 'Etwas'])
            ->assertSessionHasErrors(['missing' => 'Bitte angeben, was fehlt.'])
            ->assertSessionHasInput('solution', 'Etwas');

        expect(KnowledgeGap::query()->count())->toBe(0);
    });

    test('after reporting a thank-you appears', function () {
        $id = feedbackAnalysis();

        $this->withCookie('staff_name', 'Nele')->followingRedirects()->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), ['missing' => 'Etwas'])
            ->assertSeeText('Wissenslücke gemeldet – danke.');
    });

    test('reports from test runs are marked', function () {
        $this->withCookie('test_mode', '1');
        $id = feedbackAnalysis(['stand' => '1'], 'Etienne');

        $this->withCookie('staff_name', 'Etienne')->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), ['missing' => 'Etwas']);

        expect(KnowledgeGap::query()->sole()->test_run)->toBeTrue();
    });
});

describe('list of gaps for admins', function () {
    test('admins see the sidebar entry with the open count; others neither entry nor page', function () {
        KnowledgeGap::factory()->count(2)->create();

        $this->withCookie('staff_name', 'Etienne')->get('/')->assertSeeTextInOrder(['Wissenslücken', '2']);
        $this->withCookie('staff_name', 'Nele')->get('/')->assertDontSeeText('Wissenslücken');
        $this->withCookie('staff_name', 'Nele')->get(route('knowledge-gaps.index'))->assertForbidden();
    });

    test('open reports are listed newest first with all details and a text block for the ai chat', function () {
        $id = feedbackAnalysis();
        $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), ['missing' => 'Regel zum Zusammenführen', 'solution' => 'Geht nicht.']);

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))
            ->assertSeeTextInOrder(['Offen (1)', 'Nele', 'Ticket#2137942', 'Foto-Fachhändler / Reseller', 'kein Produktbezug', 'Was fehlt?', 'Regel zum Zusammenführen', 'So lösen wir das', 'Geht nicht.', 'Für den KI-Chat kopieren'])
            ->assertSee('Wissenslücke aus der Customer Service Assist App – bitte daraus ein Knowledge-Dokument nach dem Authoring Guide erstellen.', false)
            ->assertSee('Ticket: 2137942', false);
    });

    test('an admin marks a report done with the knowledge id, discards one with a reason, and reopens', function () {
        [$done, $discarded] = KnowledgeGap::factory()->count(2)->create();

        $this->withCookie('staff_name', 'Etienne')->patch(route('knowledge-gaps.update', ['gap' => $done->id]), ['status' => 'done', 'knowledge_id' => 'POLICY-016'])->assertRedirect(route('knowledge-gaps.index'));
        $this->withCookie('staff_name', 'Etienne')->patch(route('knowledge-gaps.update', ['gap' => $discarded->id]), ['status' => 'discarded', 'reason' => 'Doppelmeldung']);

        expect($done->fresh())->status->toBe('done')->knowledge_id->toBe('POLICY-016')->resolved_by->toBe('Etienne')
            ->and($discarded->fresh()->content['reason'])->toBe('Doppelmeldung');

        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index', ['status' => 'done']))->assertSeeTextInOrder(['Erledigt (1)', 'Erledigt von Etienne am', 'POLICY-016']);
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->assertSeeText('Keine offenen Wissenslücken.');

        $this->withCookie('staff_name', 'Etienne')->patch(route('knowledge-gaps.update', ['gap' => $done->id]), ['status' => 'open']);
        expect($done->fresh()->status)->toBe('open');
    });

    test('others cannot change reports', function () {
        $gap = KnowledgeGap::factory()->create();

        $this->withCookie('staff_name', 'Nele')->patch(route('knowledge-gaps.update', ['gap' => $gap->id]), ['status' => 'done'])->assertForbidden();
        expect($gap->fresh()->status)->toBe('open');
    });

    test('the success figure counts usable ratings of real analyses of the last 28 days', function () {
        Analysis::factory()->create(['feedback_level' => 'unchanged']);
        Analysis::factory()->create(['feedback_level' => 'slight']);
        Analysis::factory()->create(['feedback_level' => 'major']);
        Analysis::factory()->create(['feedback_level' => 'discarded', 'test_until' => now()]);
        Analysis::factory()->create(['feedback_level' => 'discarded', 'created_at' => now()->subDays(40)]);
        Analysis::factory()->create();

        expect(app(AnalysisStore::class)->successRate())->toBe(['share' => 67, 'rated' => 3]);
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->assertSeeText('67 %')->assertSeeText('3 bewertete Analysen');
    });

    test('without ratings the figure says so', function () {
        $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->assertSeeText('wurde noch keine Analyse bewertet');
    });
});

describe('retention', function () {
    test('feedback comments go with the analysis content; done reports are emptied 12 months after closing, open ones stay', function () {
        $id = feedbackAnalysis();
        rate($id, ['level' => 'major', 'comment' => 'Kommentar']);
        $open = KnowledgeGap::factory()->create();
        $done = KnowledgeGap::factory()->create(['status' => 'done', 'resolved_at' => now()]);

        $this->travel(13)->months();
        $this->artisan('analysis:purge')->assertSuccessful();

        $row = Analysis::query()->where('uuid', $id)->first();
        expect($row->feedback_comment)->toBeNull()->and($row->feedback_level)->toBe('major')
            ->and($open->fresh()->content)->not->toBeNull()
            ->and($done->fresh()->content)->toBeNull();
    });
});

describe('about page', function () {
    test('step 8 is no longer marked as in progress', function () {
        $this->get(route('about'))->assertSeeText('Nach dem Kopieren fragt die App kurz, wie brauchbar der Vorschlag war')->assertDontSee('about-wip', false);
    });
});

describe('qa additions', function () {
    test('texts of reports are escaped on the admin page and in the chat block', function () {
        KnowledgeGap::factory()->create(['content' => ['missing' => '<script>alert(1)</script>', 'solution' => '<img src=x onerror=alert(1)>', 'comment' => null]]);

        $html = $this->withCookie('staff_name', 'Etienne')->get(route('knowledge-gaps.index'))->getContent();

        expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<img src=x');
    });

    test('a gap topic from the ai with quotes does not break the report button', function () {
        CaseAgent::fake([feedbackAnswer(['knowledge_gaps' => [['topic' => 'Kunde sagt "geht nicht" \' </script>', 'question' => 'x']]])]);
        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'reseller', 'variante' => 'verlauf']);

        expect($this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent())->not->toContain('</script>\'')->toContain('Lücke melden');
    });

    test('reporting or rating a deleted analysis is refused, existing figures stay', function () {
        $id = feedbackAnalysis();
        rate($id, ['level' => 'slight']);
        $this->withCookie('staff_name', 'Etienne')->delete(route('tickets.analyses.destroy', ['number' => '2137942']));

        $this->withCookie('staff_name', 'Nele')->followingRedirects()->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), ['missing' => 'x'])->assertOk();
        expect(KnowledgeGap::query()->count())->toBe(0);
        rate($id, ['level' => 'major'])->assertNotFound();
        expect(Analysis::query()->where('uuid', $id)->value('feedback_level'))->toBe('slight');
    });

    test('feedback and reports need a chosen name', function () {
        $id = feedbackAnalysis();
        $this->defaultCookies = [];

        $this->post(route('tickets.analysis.gaps.store', ['number' => '2137942', 'analysis' => $id]), ['missing' => 'x'])->assertRedirect();
        expect(KnowledgeGap::query()->count())->toBe(0);
    });

    test('the purge log entry carries counts only', function () {
        Log::spy();

        $this->artisan('analysis:purge');

        Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context): bool => $message === 'Analysis content purged' && array_keys($context) === ['analyses', 'gaps', 'summaries', 'choices', 'customers']);
    });
});

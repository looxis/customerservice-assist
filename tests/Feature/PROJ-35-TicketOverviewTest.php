<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\AnalysisStore;
use App\Models\Analysis;
use App\Models\MessageTranslation;
use App\Models\TicketVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

afterEach(function () {
    cleanUpKnowledgeBases();
    TicketVisit::flushEventListeners();
    TicketVisit::clearBootedModels();
});

function overviewAnalysis(string $staff = 'Nele'): string
{
    CaseAgent::fake([[
        'summary' => ['incident' => 'Zwei Bestellungen', 'customer_wish' => 'Eine Rechnung'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Nachfragen.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Keine Regel.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'NIEDRIG', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag, wir prüfen das.'],
    ]]);
    $location = test()->withCookie('staff_name', $staff)->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf'])->headers->get('Location');
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return $query['analyse'];
}

function copied(string $id, ?string $text, string $staff = 'Nele', string $number = '2137942'): TestResponse
{
    return test()->withCredentials()->withCookie('staff_name', $staff)->postJson(route('tickets.analysis.copied', ['number' => $number, 'analysis' => $id]), ['text' => $text]);
}

function overviewRow(string $number, string $stage, array $analysis = []): void
{
    TicketVisit::factory()->create(['ticket_number' => $number]);

    if ($stage === 'translated') {
        MessageTranslation::factory()->create(['ticket_number' => $number]);
    }

    if (in_array($stage, ['analyzed', 'edited', 'copied-unchanged', 'copied-changed'], true)) {
        Analysis::factory()->create(['ticket_number' => $number, ...$analysis]);
    }
}

describe('recording visits', function () {
    test('opening a ticket records it with customer, subject and name; opening it again counts', function () {
        $this->travelTo(now()->setDate(2026, 10, 12)->setTime(9, 0));
        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk();

        $visit = TicketVisit::query()->sole();
        expect($visit)->ticket_number->toBe('2137942')->opened_count->toBe(1)->first_opened_by->toBe('Nele')->last_opened_by->toBe('Nele')
            ->and($visit->title)->not->toBeEmpty()
            ->and(DB::table('ticket_visits')->value('title'))->not->toBe($visit->title)
            ->and(DB::table('ticket_visits')->value('customer_name'))->not->toBe($visit->customer_name);

        $this->travel(2)->hours();
        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')->assertOk();

        expect(TicketVisit::query()->sole())->opened_count->toBe(2)->first_opened_by->toBe('Nele')->last_opened_by->toBe('Cara');
    });

    test('a rewound test-run view records nothing, a view of the whole thread does', function () {
        $this->articles = [
            zammadArticle(['id' => 1, 'body' => '<p>Frage</p>', 'created_at' => '2026-10-01T07:12:00.000Z']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Kundenservice', 'body' => '<p>Antwort</p>', 'created_at' => '2026-10-01T09:00:00.000Z']),
        ];

        $this->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1')->get('/tickets/2137942?stand=1')->assertSeeText('Testlauf:');
        expect(TicketVisit::query()->count())->toBe(0);

        $this->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1')->get('/tickets/2137942')->assertOk();
        expect(TicketVisit::query()->count())->toBe(1);
    });

    test('two first visits at the same moment both count and the page is shown', function () {
        TicketVisit::factory()->create(['ticket_number' => '2137942', 'opened_count' => 1]);
        $hidden = false;
        TicketVisit::addGlobalScope('first-lookup-misses', function (Builder $query) use (&$hidden): void {
            if (! $hidden) {
                $hidden = true;
                $query->whereRaw('1 = 0');
            }
        });

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk();

        expect(TicketVisit::query()->sole())->opened_count->toBe(2)->last_opened_by->toBe('Nele');
    });

    test('a failure while noting the visit is reported but does not stop the ticket from being shown', function () {
        Exceptions::fake();
        TicketVisit::creating(fn () => throw new RuntimeException('Datenbank weg'));

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertSee('Zammad');

        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Datenbank weg');
        expect(TicketVisit::query()->count())->toBe(0);
    });

    test('a failed load records nothing', function () {
        Http::fake(['zammad.test/api/v1/tickets/search*' => Http::response([], 500)]);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2999999');

        expect(TicketVisit::query()->count())->toBe(0);
    });

    test('a visit without a chosen name is recorded with an empty name', function () {
        $this->get('/tickets/2137942')->assertOk();

        expect(TicketVisit::query()->sole()->last_opened_by)->toBeNull();
    });
});

describe('recording copies', function () {
    test('copying the original is stored as unchanged, a changed text as changed', function () {
        $id = overviewAnalysis();

        copied($id, 'Guten Tag, wir prüfen das.')->assertOk();
        expect(Analysis::query()->where('uuid', $id)->first())->reply_copied_changed->toBeFalse()->reply_copied_by->toBe('Nele')->reply_copied_at->not->toBeNull();

        copied($id, 'Guten Tag, anders.', 'Cara')->assertOk();
        expect(Analysis::query()->where('uuid', $id)->first())->reply_copied_changed->toBeTrue()->reply_copied_by->toBe('Cara');
    });

    test('an older analysis, a wrong ticket and an unknown analysis are refused', function () {
        $old = overviewAnalysis();
        $this->travel(1)->minutes();
        overviewAnalysis();

        copied($old, 'x')->assertStatus(409);
        copied($old, 'x', number: '2999999')->assertNotFound();
        copied((string) Str::uuid(), 'x')->assertNotFound();
        expect(Analysis::query()->whereNotNull('reply_copied_at')->count())->toBe(0);
    });

    test('a missing name blocks the copy note', function () {
        $id = overviewAnalysis();
        $this->defaultCookies = [];

        $this->withCredentials()->postJson(route('tickets.analysis.copied', ['number' => '2137942', 'analysis' => $id]), ['text' => 'x'])->assertStatus(409);
    });

    test('editing the reply notes the time, restoring the original takes it back', function () {
        $id = overviewAnalysis();

        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['text' => 'Neu'])->assertOk();
        expect(Analysis::query()->where('uuid', $id)->value('reply_edited_at'))->not->toBeNull();

        $this->withCredentials()->withCookie('staff_name', 'Nele')->putJson(route('tickets.analysis.reply', ['number' => '2137942', 'analysis' => $id]), ['original' => true])->assertOk();
        expect(Analysis::query()->where('uuid', $id)->value('reply_edited_at'))->toBeNull();
    });

    test('the copy button reports the copy to the server', function () {
        overviewAnalysis();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertSee('noteCopy', false)->assertSee('/kopiert', false);
    });
});

describe('the overview page', function () {
    test('it is for admins only, in the navigation and as a page', function () {
        $this->withCookie('staff_name', 'Nele')->get(route('ticket-overview.index'))->assertForbidden();
        $this->get(route('ticket-overview.index'))->assertForbidden();
        $this->withCookie('staff_name', 'Nele')->get(route('tickets.analyze'))->assertDontSee('Ticket-Übersicht');

        $this->withCookie('staff_name', 'Etienne')->get(route('tickets.analyze'))->assertSee('Ticket-Übersicht');
        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))->assertOk()->assertSeeText('Noch keine Tickets abgerufen.');
    });

    test('the tickets are sorted by the last visit with number, customer and subject first', function () {
        TicketVisit::factory()->create(['ticket_number' => '2100001', 'customer_name' => 'Anna Alt', 'title' => 'Alter Fall', 'last_opened_at' => now()->subDays(2)]);
        TicketVisit::factory()->create(['ticket_number' => '2100002', 'customer_name' => 'Bert Neu', 'title' => 'Neuer Fall', 'last_opened_at' => now()->subHour(), 'last_opened_by' => 'Cara', 'opened_count' => 3]);

        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))
            ->assertSeeTextInOrder(['Ticket#2100002', 'Bert Neu', 'Neuer Fall', 'Cara', 'Ticket#2100001', 'Anna Alt', 'Alter Fall']);
    });

    test('the stage is the highest one that applies', function () {
        overviewRow('2100001', 'opened');
        overviewRow('2100002', 'translated');
        overviewRow('2100003', 'analyzed');
        overviewRow('2100004', 'edited', ['reply_edited_at' => now()]);
        overviewRow('2100005', 'copied-unchanged', ['reply_copied_at' => now(), 'reply_copied_changed' => false]);
        overviewRow('2100006', 'copied-changed', ['reply_copied_at' => now(), 'reply_copied_changed' => true, 'reply_edited_at' => now()]);

        $counts = $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))
            ->assertSeeText('Alle (6)')->assertSeeText('Nur abgerufen (1)')->assertSeeText('Übersetzt (1)')->assertSeeText('Vorschlag erstellt (1)')
            ->assertSeeText('Bearbeitet, nicht kopiert (1)')->assertSeeText('Kopiert (unverändert) (1)')->assertSeeText('Kopiert (angepasst) (1)');

        foreach (['opened' => '2100001', 'translated' => '2100002', 'analyzed' => '2100003', 'edited' => '2100004', 'copied-unchanged' => '2100005', 'copied-changed' => '2100006'] as $stage => $number) {
            $page = $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index', ['stand' => $stage]))->assertSeeText("Ticket#{$number}");
            expect(substr_count($page->getContent(), 'Ticket#21'))->toBe(1);
        }
    });

    test('german messages do not count as translated and test-run analyses are ignored', function () {
        TicketVisit::factory()->create(['ticket_number' => '2100001']);
        MessageTranslation::factory()->create(['ticket_number' => '2100001', 'status' => 'german']);
        TicketVisit::factory()->create(['ticket_number' => '2100002']);
        Analysis::factory()->create(['ticket_number' => '2100002', 'scope_key' => '2100002.stand-5', 'test_until' => now(), 'reply_copied_at' => now(), 'reply_copied_changed' => false]);

        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))->assertSeeText('Nur abgerufen (2)');
    });

    test('the newest analysis decides and the number of analyses and the feedback are shown', function () {
        TicketVisit::factory()->create(['ticket_number' => '2100001']);
        Analysis::factory()->create(['ticket_number' => '2100001', 'reply_copied_at' => now(), 'reply_copied_changed' => true]);
        Analysis::factory()->create(['ticket_number' => '2100001', 'feedback_level' => 'slight']);

        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))
            ->assertSeeText('Vorschlag erstellt (1)')->assertSeeText('2 Vorschläge')->assertSeeText('Feedback: leicht angepasst');
    });

    test('the list is paged by fifty and filtered by stage', function () {
        TicketVisit::factory()->count(55)->create();

        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))->assertSeeText('Alle (55)');
        $first = $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))->getContent();
        $second = $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index', ['page' => 2]))->getContent();

        expect(substr_count($first, 'Ticket#21'))->toBe(50)->and(substr_count($second, 'Ticket#21'))->toBe(5);
        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index', ['stand' => 'copied-changed']))->assertSeeText('Kein Ticket in diesem Stand.');
    });

    test('customer data in the list is escaped', function () {
        TicketVisit::factory()->create(['customer_name' => '<script>alert(1)</script>', 'title' => '<img src=x onerror=alert(1)>']);

        $this->withCookie('staff_name', 'Etienne')->get(route('ticket-overview.index'))->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x', false);
    });
});

describe('retention and backfill', function () {
    test('purging drops visits older than twelve months and keeps recent ones', function () {
        TicketVisit::factory()->create(['ticket_number' => '2100001', 'last_opened_at' => now()->subMonths(13)]);
        TicketVisit::factory()->create(['ticket_number' => '2100002', 'last_opened_at' => now()->subMonths(11)]);

        $counts = app(AnalysisStore::class)->purge();

        expect($counts['visits'])->toBe(1)->and(TicketVisit::query()->pluck('ticket_number')->all())->toBe(['2100002']);
        $this->artisan('analysis:purge')->expectsOutputToContain('abgerufene Tickets gelöscht: 0')->assertSuccessful();
    });
});

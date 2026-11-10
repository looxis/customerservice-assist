<?php

use App\Analysis\ProcessingState;
use App\Models\TrackedTicket;
use App\Models\ZammadSyncRun;
use App\Staff\StaffDirectory;
use App\Zammad\SyncStatus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'test-token']);
});

function ticketListNumbers(mixed $response): array
{
    return collect($response->viewData('ticketRows')->items())->pluck('number')->all();
}

describe('the start page stays what it was', function () {
    test('it shows the lookup above the list, with Zammad unreachable', function () {
        Http::preventStrayRequests();
        TrackedTicket::factory()->create(['title' => 'Tasse kaputt']);

        $this->get('/')->assertOk()->assertViewIs('tickets.analyze')->assertSeeInOrder([route('tickets.lookup'), 'Tasse kaputt'], false);
    });

    test('the manual lookup still opens a ticket', function () {
        $this->get(route('tickets.lookup', ['ticket' => 'Ticket#2137942']))->assertRedirect(route('tickets.show', '2137942'));
    });
});

describe('views', function () {
    beforeEach(function () {
        TrackedTicket::factory()->create(['number' => '1', 'owner_zammad_id' => null]);
        TrackedTicket::factory()->ownedBy(99, 'Nele Gorka')->create(['number' => '2']);
        TrackedTicket::factory()->ownedBy(5, 'Johannes Renaud')->create(['number' => '3']);
        TrackedTicket::factory()->closed()->create(['number' => '4']);
        TrackedTicket::factory()->closed()->ownedBy(99, 'Nele Gorka')->create(['number' => '5', 'state' => 'merged']);
    });

    test('the default view shows open tickets without owner', function () {
        expect(ticketListNumbers($this->get('/')))->toBe(['1']);
    });

    test('„Meine“ shows the open tickets of the chosen name by its Zammad ID', function () {
        expect(ticketListNumbers($this->withCookie('staff_name', 'Nele')->get('/?ansicht=meine')))->toBe(['2']);
    });

    test('„Alle offenen“ and „Geschlossen“', function () {
        expect(ticketListNumbers($this->get('/?ansicht=offen')))->toEqualCanonicalizing(['1', '2', '3']);
        expect(ticketListNumbers($this->get('/?ansicht=geschlossen')))->toEqualCanonicalizing(['4', '5']);
    });

    test('waiting tickets count as open', function () {
        TrackedTicket::factory()->create(['number' => '6', 'state' => 'pending reminder']);

        expect(ticketListNumbers($this->get('/?ansicht=offen')))->toContain('6');
    });
});

describe('„Meine“ goes by the owner ID only', function () {
    test('the same name with another ID does not appear', function () {
        TrackedTicket::factory()->ownedBy(99, 'Nele Gorka')->create(['number' => '10']);
        TrackedTicket::factory()->ownedBy(12345, 'Nele Gorka')->create(['number' => '11']);

        expect(ticketListNumbers($this->withCookie('staff_name', 'Nele')->get('/?ansicht=meine')))->toBe(['10']);
    });

    test('a changed name with the same ID still appears', function () {
        TrackedTicket::factory()->ownedBy(99, 'Nele Neuname')->create(['number' => '10']);

        expect(ticketListNumbers($this->withCookie('staff_name', 'Nele')->get('/?ansicht=meine')))->toBe(['10']);
    });

    test('without a chosen name or a mapping there is a hint instead of a list', function () {
        TrackedTicket::factory()->ownedBy(99, 'Nele Gorka')->create(['number' => '10']);
        config(['staff.zammad_user_ids.Nele' => null]);
        app()->forgetInstance(StaffDirectory::class);

        $this->get('/?ansicht=meine')->assertOk()->assertSee('kein Zammad-Benutzer hinterlegt');
        $this->withCookie('staff_name', 'Nele')->get('/?ansicht=meine')->assertOk()->assertSee('kein Zammad-Benutzer hinterlegt')->assertDontSee(route('tickets.show', '10'), false);
    });
});

describe('filters', function () {
    beforeEach(function () {
        TrackedTicket::factory()->create(['number' => '1', 'state' => 'new', 'category' => 'complaint', 'channel' => 'amazon', 'processing_state' => ProcessingState::Analyzed]);
        TrackedTicket::factory()->create(['number' => '2', 'state' => 'open', 'category' => 'complaint']);
        TrackedTicket::factory()->create(['number' => '3', 'state' => 'open']);
        TrackedTicket::factory()->ownedBy(5, 'Johannes Renaud')->create(['number' => '4', 'state' => 'open']);
    });

    test('each filter works and they combine', function () {
        expect(ticketListNumbers($this->get('/?status=new')))->toBe(['1']);
        expect(ticketListNumbers($this->get('/?kanal=amazon')))->toBe(['1']);
        expect(ticketListNumbers($this->get('/?csa=analyzed')))->toBe(['1']);
        expect(ticketListNumbers($this->get('/?klassifikation=complaint')))->toEqualCanonicalizing(['1', '2']);
        expect(ticketListNumbers($this->get('/?klassifikation=complaint&status=open')))->toBe(['2']);
        expect(ticketListNumbers($this->get('/?ansicht=offen&mitarbeiter=5')))->toBe(['4']);
    });

    test('not classified and unknown are filters of their own', function () {
        expect(ticketListNumbers($this->get('/?klassifikation=keine')))->toBe(['3']);
        expect(ticketListNumbers($this->get('/?kanal=keine')))->toEqualCanonicalizing(['2', '3']);
    });

    test('a chosen employee replaces the owner of the view', function () {
        expect(ticketListNumbers($this->get('/?mitarbeiter=5')))->toBe(['4']);
    });

    test('paging keeps view, filter and sorting', function () {
        TrackedTicket::factory()->count(30)->create(['state' => 'pending reminder']);

        $this->get('/?ansicht=offen&status=pending+reminder&sort=erstellt&richtung=asc')->assertOk()->assertSee('ansicht=offen', false)->assertSee('status=pending%20reminder', false)->assertSee('sort=erstellt', false)->assertSee('page=2', false);
    });

    test('a hand-changed address shows the list instead of an error', function (string $query) {
        $this->get("/?{$query}")->assertOk();
    })->with([
        'ansicht=unbekannt', 'status=quatsch', 'sort=title', 'richtung=seitwaerts', 'page=abc', 'page=99999',
        'status[]=open', 'mitarbeiter=1%20OR%201=1', 'csa=irgendwas', 'sync=alles',
    ]);

    test('a page beyond the last shows the last page', function () {
        $this->get('/?page=999')->assertOk()->assertSee(route('tickets.show', '3'), false);
    });
});

describe('sorting', function () {
    test('sorts by the fixed columns, newest activity first by default', function () {
        TrackedTicket::factory()->create(['number' => '1', 'zammad_id' => 1, 'state' => 'open', 'last_activity_at' => now()->subHour(), 'zammad_created_at' => now()->subDays(1)]);
        TrackedTicket::factory()->create(['number' => '2', 'zammad_id' => 2, 'state' => 'new', 'last_activity_at' => now(), 'zammad_created_at' => now()->subDays(2)]);

        expect(ticketListNumbers($this->get('/')))->toBe(['2', '1']);
        expect(ticketListNumbers($this->get('/?sort=aktivitaet&richtung=asc')))->toBe(['1', '2']);
        expect(ticketListNumbers($this->get('/?sort=nummer')))->toBe(['2', '1']);
        expect(ticketListNumbers($this->get('/?sort=status&richtung=asc')))->toBe(['2', '1']);
        expect(ticketListNumbers($this->get('/?sort=erstellt')))->toBe(['1', '2']);
    });

    test('an unknown column falls back to the default', function () {
        TrackedTicket::factory()->create(['number' => '1', 'last_activity_at' => now()->subHour()]);
        TrackedTicket::factory()->create(['number' => '2', 'last_activity_at' => now()]);

        expect(ticketListNumbers($this->get('/?sort=title')))->toBe(['2', '1']);
    });
});

describe('a row', function () {
    test('links to the existing ticket page and shows the columns', function () {
        TrackedTicket::factory()->ownedBy(99, 'Nele Gorka')->create(['number' => '2140500', 'title' => 'Tasse kaputt', 'state' => 'pending reminder']);

        $this->get('/?ansicht=offen')->assertOk()
            ->assertSee(route('tickets.show', '2140500'), false)
            ->assertSee('Tasse kaputt')->assertSee('Warten auf Erinnerung')->assertSee('Nele Gorka')
            ->assertSee('<span class="sr-only">Noch nicht eingeordnet</span>', false)->assertSee('Noch nicht analysiert');
    });

    test('shows the plain ticket number, without the prefix', function () {
        TrackedTicket::factory()->create(['number' => '2140500']);

        $this->get('/')->assertOk()->assertSee('>2140500</a>', false)->assertDontSee('Ticket#2140500');
    });

    test('a classified ticket shows its category instead of the icon', function () {
        TrackedTicket::factory()->create(['category' => 'adressaenderung', 'category_confirmed' => true]);

        $this->get('/')->assertOk()->assertSee('Adressänderung')->assertDontSee('<span class="sr-only">Noch nicht eingeordnet</span>', false)
            ->assertDontSee('title="Vorschlag der KI, noch nicht bestätigt"', false);
    });

    test('a category the model proposed and nobody confirmed yet carries "KI" (PROJ-80)', function () {
        TrackedTicket::factory()->create(['category' => 'adressaenderung', 'category_confirmed' => false]);

        $this->get('/')->assertOk()->assertSee('Adressänderung')->assertSee('title="Vorschlag der KI, noch nicht bestätigt"', false);
    });

    test('escapes the subject', function () {
        TrackedTicket::factory()->create(['title' => '<script>alert(1)</script>']);

        $this->get('/')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    });

    test('the subject is not readable in the database', function () {
        TrackedTicket::factory()->create(['title' => 'Tasse kaputt']);

        expect(DB::table('tracked_tickets')->value('title'))->not->toContain('Tasse');
    });
});

describe('sync state', function () {
    test('unreadable tickets are hidden from all views, counted, and shown on request', function (SyncStatus $status) {
        TrackedTicket::factory()->create(['number' => '1', 'sync_status' => $status, 'sync_problem_since' => now()]);
        TrackedTicket::factory()->closed()->create(['number' => '2', 'sync_status' => $status, 'sync_problem_since' => now()]);

        foreach (['ohne-zuweisung', 'offen', 'geschlossen'] as $view) {
            expect(ticketListNumbers($this->get("/?ansicht={$view}")))->toBe([]);
        }

        $this->get('/')->assertSee('2 Tickets in Zammad nicht lesbar');
        expect(ticketListNumbers($this->get('/?sync=problem')))->toEqualCanonicalizing(['1', '2']);
        $this->get('/?sync=problem')->assertSee('In Zammad nicht lesbar');
    })->with([SyncStatus::NotFound, SyncStatus::AccessDenied]);

    test('a failed sync stays visible with a hint', function () {
        TrackedTicket::factory()->create(['number' => '1', 'sync_status' => SyncStatus::SyncFailed, 'sync_problem_since' => now()]);

        $this->get('/')->assertSee(route('tickets.show', '1'), false)->assertSee('Stand evtl. veraltet');
    });

    test('says when no sync has run yet', function () {
        $this->get('/')->assertSee('Noch kein Abgleich mit Zammad gelaufen')->assertSee('Noch keine Tickets erfasst');
    });

    test('shows how current the list is, without a warning while it is fresh', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now(), 'covered_until' => now()->subMinutes(2), 'status' => 'succeeded', 'finished_at' => now()]);

        $this->get('/')->assertSee('Stand: vor 2 Minuten')->assertDontSee('Der Abgleich mit Zammad hängt');
    });

    test('warns when the marker is too old', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now(), 'covered_until' => now()->subMinutes(16)]);

        $this->get('/')->assertSee('Der Abgleich mit Zammad hängt');
    });

    test('warns when the last complete comparison failed, until the next one works', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now(), 'covered_until' => now()]);
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECONCILE, 'started_at' => now()->subHour(), 'finished_at' => now()->subHour(), 'status' => 'failed']);

        $this->get('/')->assertSee('Der Abgleich mit Zammad hängt');

        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECONCILE, 'started_at' => now(), 'finished_at' => now(), 'status' => 'succeeded']);

        $this->get('/')->assertDontSee('Der Abgleich mit Zammad hängt');
    });
});

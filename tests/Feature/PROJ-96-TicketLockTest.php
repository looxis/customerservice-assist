<?php

use App\Http\Middleware\EnsureTicketNotLocked;
use App\Models\TicketLock;
use App\Models\TrackedTicket;
use App\Tickets\TicketLocks;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
    fakeZammad([zammadArticle()]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(function () {
    cleanUpKnowledgeBases();
});

function openTicketAs(?string $name, string $number = '2137942')
{
    $request = $name === null ? test() : test()->withCookie('staff_name', $name);

    return $request->get(route('tickets.show', ['number' => $number]));
}

function lockTicket(string $name, string $number = '2137942', int $minutesAgo = 0): void
{
    TicketLock::query()->create(['ticket_number' => $number, 'staff_name' => $name, 'last_activity_at' => now()->subMinutes($minutesAgo)]);
}

describe('taking the lock', function () {
    test('opening a free ticket locks it for the opener', function () {
        openTicketAs('Nele')->assertOk()->assertViewHas('lockedBy', null);

        expect(TicketLock::query()->where('ticket_number', '2137942')->value('staff_name'))->toBe('Nele');
    });

    test('without a chosen name opening sets no lock', function () {
        openTicketAs(null)->assertOk();

        expect(TicketLock::query()->count())->toBe(0);
    });

    test('a second name sees the ticket light red and read-only', function () {
        lockTicket('Nele');

        openTicketAs('Cara')->assertOk()
            ->assertViewHas('lockedBy', 'Nele')
            ->assertSee('bg-danger-500/10', false)
            ->assertSee('bearbeitet dieses Ticket gerade', false)
            ->assertSee('inert', false)
            ->assertDontSee(route('tickets.analysis.run', ['number' => '2137942']), false);

        expect(TicketLock::query()->value('staff_name'))->toBe('Nele');
    });

    test('the same name is never locked out', function () {
        lockTicket('Nele');

        openTicketAs('Nele')->assertOk()->assertViewHas('lockedBy', null)->assertDontSee('bearbeitet dieses Ticket gerade', false);
    });

    test('five minutes without activity free the ticket', function () {
        lockTicket('Nele', minutesAgo: 6);

        openTicketAs('Cara')->assertOk()->assertViewHas('lockedBy', null);

        expect(TicketLock::query()->value('staff_name'))->toBe('Cara');
    });

    test('the lock is still active just before five minutes', function () {
        lockTicket('Nele', minutesAgo: 4);

        openTicketAs('Cara')->assertViewHas('lockedBy', 'Nele');
    });
});

describe('heartbeat and release', function () {
    test('the heartbeat with claim extends the own lock', function () {
        lockTicket('Nele', minutesAgo: 4);
        $this->travel(2)->minutes();

        $this->withCredentials()->withCookie('staff_name', 'Nele')->postJson(route('tickets.lock.heartbeat', ['number' => '2137942']), ['claim' => true])
            ->assertOk()->assertJson(['locked_by' => null]);

        expect(app(TicketLocks::class)->holder('2137942', 'Cara')?->staff_name)->toBe('Nele');
    });

    test('the heartbeat with claim reports the holder when someone else has the ticket', function () {
        lockTicket('Nele');

        $this->withCredentials()->withCookie('staff_name', 'Cara')->postJson(route('tickets.lock.heartbeat', ['number' => '2137942']), ['claim' => true])
            ->assertOk()->assertJson(['locked_by' => 'Nele']);

        expect(TicketLock::query()->value('staff_name'))->toBe('Nele');
    });

    test('the status question without claim takes no lock', function () {
        $this->withCredentials()->withCookie('staff_name', 'Cara')->postJson(route('tickets.lock.heartbeat', ['number' => '2137942']), ['claim' => false])
            ->assertOk()->assertJson(['locked_by' => null]);

        expect(TicketLock::query()->count())->toBe(0);
    });

    test('the release frees only the own lock', function () {
        lockTicket('Nele');

        $this->withCookie('staff_name', 'Cara')->post(route('tickets.lock.release', ['number' => '2137942']))->assertNoContent();
        expect(TicketLock::query()->count())->toBe(1);

        $this->withCookie('staff_name', 'Nele')->post(route('tickets.lock.release', ['number' => '2137942']))->assertNoContent();
        expect(TicketLock::query()->count())->toBe(0);
    });
});

describe('changes to a locked ticket', function () {
    test('a change sent as JSON is turned away with 409', function (string $route, string $method, array $parameters) {
        lockTicket('Nele');

        $this->withCredentials()->withCookie('staff_name', 'Cara')->json($method, route($route, ['number' => '2137942', ...$parameters]))
            ->assertStatus(409)->assertJson(['message' => EnsureTicketNotLocked::message('Nele'), 'locked_by' => 'Nele']);
    })->with([
        'analyse' => ['tickets.analysis.run', 'POST', []],
        'schliessen' => ['tickets.close', 'POST', []],
        'zuweisen' => ['tickets.assign', 'POST', []],
        'direkt antworten' => ['tickets.manual-reply', 'POST', []],
        'klassifikation' => ['tickets.label', 'POST', []],
        'todos' => ['tickets.todos.store', 'POST', []],
        'senden' => ['tickets.analysis.send', 'POST', ['analysis' => '0b9c4c8e-5d7a-4c1e-9b8a-1a2b3c4d5e6f']],
        'feedback' => ['tickets.analysis.feedback', 'PUT', ['analysis' => '0b9c4c8e-5d7a-4c1e-9b8a-1a2b3c4d5e6f']],
        'entwurf' => ['tickets.analysis.reply', 'PUT', ['analysis' => '0b9c4c8e-5d7a-4c1e-9b8a-1a2b3c4d5e6f']],
    ]);

    test('a form sent the usual way goes back to the ticket with the message', function () {
        lockTicket('Nele');

        $this->withCookie('staff_name', 'Cara')->post(route('tickets.close', ['number' => '2137942']))
            ->assertRedirect(route('tickets.show', ['number' => '2137942']))
            ->assertSessionHas('error', EnsureTicketNotLocked::message('Nele'));
    });

    test('the middleware lets the holder and expired locks through', function () {
        lockTicket('Nele');
        expect(app(TicketLocks::class)->holder('2137942', 'Nele'))->toBeNull();

        TicketLock::query()->update(['last_activity_at' => now()->subMinutes(6)]);
        expect(app(TicketLocks::class)->holder('2137942', 'Cara'))->toBeNull();
    });
});

describe('start page', function () {
    test('tickets someone else works on are greyed out with the name', function () {
        Http::preventStrayRequests();
        TrackedTicket::factory()->create(['number' => '111']);
        TrackedTicket::factory()->create(['number' => '222']);
        TrackedTicket::factory()->create(['number' => '333']);
        lockTicket('Nele', '111');
        lockTicket('Cara', '222');
        lockTicket('Thomas', '333', minutesAgo: 6);

        $response = $this->withCookie('staff_name', 'Cara')->get('/')->assertOk();

        expect($response->viewData('lockedTickets'))->toBe(['111' => 'Nele']);
        $response->assertSee('in Bearbeitung: Nele')->assertDontSee('in Bearbeitung: Cara')->assertDontSee('in Bearbeitung: Thomas')
            ->assertSee('opacity-50 grayscale', false);
    });
});

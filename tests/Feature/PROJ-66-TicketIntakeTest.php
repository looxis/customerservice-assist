<?php

use App\Analysis\AnalysisStore;
use App\Analysis\ProcessingState;
use App\Analysis\TicketProcessingState;
use App\Jobs\SyncZammadTicket;
use App\Models\Analysis;
use App\Models\TrackedTicket;
use App\Models\ZammadSyncRun;
use App\Models\ZammadWebhookDelivery;
use App\Zammad\SyncStatus;
use App\Zammad\TicketSynchronizer;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'services.zammad.url' => 'https://zammad.test',
        'services.zammad.token' => 'test-token',
        'services.zammad.webhook_secret' => 'geheim',
        'services.zammad.sync_lock_wait' => 0,
    ]);
});

/**
 * @return array<string, mixed>
 */
function intakeTicket(array $overrides = []): array
{
    return array_merge([
        'id' => 500,
        'number' => '2140500',
        'title' => 'Tasse kaputt',
        'state' => 'open',
        'group' => 'allg. Kunden',
        'owner_id' => 1,
        'customer_id' => 77,
        'organization_id' => null,
        'created_at' => '2026-10-30T08:00:00.000Z',
        'updated_at' => '2026-10-30T09:00:00.500Z',
        'last_contact_at' => '2026-10-30T08:30:00.000Z',
        'last_contact_customer_at' => '2026-10-30T08:30:00.000Z',
        'pending_time' => null,
    ], $overrides);
}

/**
 * Fake Zammad: details by ID, searches from the given result pages.
 *
 * @param  array<int, array<string, mixed>|int>  $tickets  Ticket data, or the HTTP status a read of that ID answers with.
 * @param  list<array<string, mixed>|int>  $searches  One page per search call, last one repeating; a number is an HTTP status.
 */
function fakeIntake(array $tickets = [], array $searches = [], array $articles = []): void
{
    $articles = $articles ?: [zammadArticle(['id' => 9001])];

    Http::swap(new Factory(app('events')));
    Http::preventStrayRequests();
    Http::fake(function (Request $request) use ($tickets, &$searches, $articles) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($path === '/api/v1/tickets/search') {
            $page = count($searches) > 1 ? array_shift($searches) : ($searches[0] ?? ['records' => [], 'total_count' => 0]);

            return is_int($page) ? Http::response([], $page) : Http::response($page);
        }

        if (preg_match('#^/api/v1/tickets/(\d+)$#', $path, $match) === 1) {
            $ticket = $tickets[(int) $match[1]] ?? 404;

            return is_int($ticket) ? Http::response([], $ticket) : Http::response($ticket);
        }

        if (str_starts_with($path, '/api/v1/ticket_articles/by_ticket/')) {
            return Http::response($articles);
        }

        if (str_starts_with($path, '/api/v1/users/')) {
            return Http::response(['firstname' => 'Nele', 'lastname' => 'Gorka', 'email' => 'erika@example.org']);
        }

        return Http::response([], 500);
    });
}

function intakeSync(int $id = 500, ?string $listed = null, bool $readArticles = false): void
{
    app(TicketSynchronizer::class)->sync($id, $listed, $readArticles);
}

/**
 * @return list<array{id: int, updated_at: string}>
 */
function intakeRecord(int $id, string $updatedAt = '2026-10-30T09:00:00.500Z'): array
{
    return ['id' => $id, 'updated_at' => $updatedAt];
}

function intakeWebhook(array $payload = ['ticket_id' => 500], array $headers = []): mixed
{
    $body = json_encode($payload);

    return test()->call('POST', '/api/webhooks/zammad', [], [], [], array_merge([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $body, 'geheim'),
        'HTTP_X_ZAMMAD_DELIVERY' => 'delivery-1',
        'HTTP_X_ZAMMAD_TRIGGER' => 'CSA Sync',
    ], $headers), $body);
}

describe('webhook signature', function () {
    beforeEach(fn () => Queue::fake());

    test('a valid sha1 signature over the raw body is accepted', function () {
        intakeWebhook()->assertStatus(202);

        Queue::assertPushed(SyncZammadTicket::class, fn (SyncZammadTicket $job): bool => $job->zammadId === 500);
        expect(ZammadWebhookDelivery::query()->where('delivery_id', 'delivery-1')->value('zammad_ticket_id'))->toBe(500);
    });

    test('a call as Zammad really sends it is accepted: custom payload with the ID as text, JSON with charset', function () {
        $body = '{"ticket_id":"500"}';

        test()->call('POST', '/api/webhooks/zammad', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $body, 'geheim'),
            'HTTP_X_ZAMMAD_DELIVERY' => 'job-id',
            'HTTP_X_ZAMMAD_TRIGGER' => 'CSA Sync',
        ], $body)->assertStatus(202);

        Queue::assertPushed(SyncZammadTicket::class, fn (SyncZammadTicket $job): bool => $job->zammadId === 500);
    });

    test('requests without a valid signature are refused without job or log', function (array $headers) {
        intakeWebhook(headers: $headers)->assertForbidden();

        Queue::assertNothingPushed();
        expect(ZammadWebhookDelivery::query()->count())->toBe(0);
    })->with([
        'missing' => [['HTTP_X_HUB_SIGNATURE' => '']],
        'no algorithm prefix' => [['HTTP_X_HUB_SIGNATURE' => hash_hmac('sha1', '{"ticket_id":500}', 'geheim')]],
        'other algorithm' => [['HTTP_X_HUB_SIGNATURE' => 'sha256='.hash_hmac('sha256', '{"ticket_id":500}', 'geheim')]],
        'wrong value' => [['HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', '{"ticket_id":500}', 'falsch')]],
    ]);

    test('the signature covers the unchanged raw body', function () {
        $signed = hash_hmac('sha1', '{"ticket_id":500}', 'geheim');

        test()->call('POST', '/api/webhooks/zammad', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE' => "sha1={$signed}",
        ], '{ "ticket_id": 500 }')->assertForbidden();

        Queue::assertNothingPushed();
    });

    test('without a configured secret everything is refused', function () {
        config(['services.zammad.webhook_secret' => null]);

        test()->call('POST', '/api/webhooks/zammad', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', '{"ticket_id":500}', ''),
        ], '{"ticket_id":500}')->assertForbidden();

        Queue::assertNothingPushed();
    });

    test('a signed call without a ticket ID is invalid', function () {
        intakeWebhook(['foo' => 'bar'])->assertUnprocessable();

        Queue::assertNothingPushed();
    });
});

describe('webhook handling', function () {
    beforeEach(fn () => Queue::fake());

    test('a repeated delivery starts no second job', function () {
        intakeWebhook()->assertStatus(202);
        intakeWebhook()->assertOk();

        Queue::assertPushed(SyncZammadTicket::class, 1);
        expect(ZammadWebhookDelivery::query()->count())->toBe(1);
    });

    test('the handler itself calls nothing in Zammad', function () {
        Http::fake();

        intakeWebhook()->assertStatus(202);

        Http::assertNothingSent();
    });

    test('the job marks the deliveries of its ticket as processed', function () {
        fakeIntake([500 => intakeTicket()]);
        intakeWebhook();

        (new SyncZammadTicket(500))->handle(app(TicketSynchronizer::class));

        expect(ZammadWebhookDelivery::query()->first()->status)->toBe(ZammadWebhookDelivery::PROCESSED);
    });
});

describe('synchronizing a ticket', function () {
    test('a new ticket is stored without customer content', function () {
        fakeIntake([500 => intakeTicket(['organization_id' => 12])]);

        intakeSync();

        $ticket = TrackedTicket::query()->where('zammad_id', 500)->sole();

        expect($ticket->number)->toBe('2140500')
            ->and($ticket->title)->toBe('Tasse kaputt')
            ->and($ticket->state)->toBe('open')
            ->and($ticket->is_closed)->toBeFalse()
            ->and($ticket->group_name)->toBe('allg. Kunden')
            ->and($ticket->organization_zammad_id)->toBe(12)
            ->and($ticket->last_customer_article_id)->toBe(9001)
            ->and($ticket->sync_status)->toBe(SyncStatus::Synced)
            ->and($ticket->processing_state)->toBe(ProcessingState::NotAnalyzed)
            ->and($ticket->zammad_updated_at->format('Y-m-d H:i:s.v'))->toBe('2026-10-30 09:00:00.500');

        expect(Schema::hasColumn('tracked_tickets', 'customer_name'))->toBeFalse();
        expect(Schema::hasColumn('tracked_tickets', 'body'))->toBeFalse();
    });

    test('the subject is encrypted in the database', function () {
        fakeIntake([500 => intakeTicket()]);

        intakeSync();

        expect(DB::table('tracked_tickets')->value('title'))->not->toContain('Tasse');
    });

    test('owner 1 means unassigned, a real owner is stored with ID and name', function () {
        fakeIntake([500 => intakeTicket()]);
        intakeSync();
        expect(TrackedTicket::query()->first()->owner_zammad_id)->toBeNull();

        fakeIntake([500 => intakeTicket(['owner_id' => 99, 'updated_at' => '2026-10-30T10:00:00.000Z'])]);
        intakeSync();

        $ticket = TrackedTicket::query()->first();
        expect($ticket->owner_zammad_id)->toBe(99)->and($ticket->owner_name)->toBe('Nele Gorka');
    });

    test('state and assignment follow Zammad', function () {
        fakeIntake([500 => intakeTicket()]);
        intakeSync();

        fakeIntake([500 => intakeTicket(['state' => 'closed', 'updated_at' => '2026-10-30T10:00:00.000Z'])]);
        intakeSync();

        expect(TrackedTicket::query()->first()->is_closed)->toBeTrue();
    });

    test('a closed ticket opened again is marked as reopened', function () {
        fakeIntake([500 => intakeTicket(['state' => 'closed'])]);
        intakeSync();
        expect(TrackedTicket::query()->first()->reopened_at)->toBeNull();

        fakeIntake([500 => intakeTicket(['updated_at' => '2026-10-30T10:00:00.000Z'])]);
        intakeSync();

        $ticket = TrackedTicket::query()->first();
        expect($ticket->is_closed)->toBeFalse()->and($ticket->reopened_at)->not->toBeNull();
    });

    test('older data never overwrites newer data', function () {
        fakeIntake([500 => intakeTicket(['state' => 'closed', 'updated_at' => '2026-10-30T12:00:00.000Z'])]);
        intakeSync();

        fakeIntake([500 => intakeTicket(['state' => 'open', 'updated_at' => '2026-10-30T11:00:00.000Z'])]);
        intakeSync();

        expect(TrackedTicket::query()->first()->state)->toBe('closed');
    });

    test('excluded groups stay out', function () {
        config(['services.zammad.sync_excluded_groups' => 'WISMO, Spam']);
        app()->forgetInstance(ZammadClient::class);
        fakeIntake([500 => intakeTicket(['group' => 'WISMO'])]);

        intakeSync();

        expect(TrackedTicket::query()->count())->toBe(0);
    });

    test('the sync only ever reads from Zammad', function () {
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent');
        $this->artisan('zammad:reconcile-open --all');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET');
        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
    });
});

describe('read problems', function () {
    test('403 and 404 keep the row with all its data and need no retry', function (int $status, SyncStatus $expected) {
        $ticket = TrackedTicket::factory()->create(['zammad_id' => 500, 'title' => 'Alt', 'category' => 'complaint', 'processing_state' => ProcessingState::Analyzed]);
        fakeIntake([500 => $status]);

        SyncZammadTicket::dispatchSync(500);

        $ticket->refresh();
        expect($ticket->sync_status)->toBe($expected)
            ->and($ticket->sync_problem_since)->not->toBeNull()
            ->and($ticket->title)->toBe('Alt')
            ->and($ticket->category)->toBe('complaint')
            ->and($ticket->processing_state)->toBe(ProcessingState::Analyzed);
    })->with([
        '403' => [403, SyncStatus::AccessDenied],
        '404' => [404, SyncStatus::NotFound],
    ]);

    test('a later success makes the ticket readable again', function () {
        $ticket = TrackedTicket::factory()->create(['zammad_id' => 500, 'sync_status' => SyncStatus::AccessDenied, 'sync_problem_since' => now()->subDay()]);
        fakeIntake([500 => intakeTicket()]);

        intakeSync();

        $ticket->refresh();
        expect($ticket->sync_status)->toBe(SyncStatus::Synced)->and($ticket->sync_problem_since)->toBeNull();
    });

    test('a temporary error sets sync_failed when the job gives up, and is retried', function () {
        $ticket = TrackedTicket::factory()->create(['zammad_id' => 500]);
        fakeIntake([500 => 503]);

        expect(fn () => (new SyncZammadTicket(500))->handle(app(TicketSynchronizer::class)))->toThrow(ZammadException::class);
        expect($ticket->refresh()->sync_status)->toBe(SyncStatus::Synced);

        (new SyncZammadTicket(500))->failed(new ZammadException(ZammadProblem::Unavailable, 503));

        expect($ticket->refresh()->sync_status)->toBe(SyncStatus::SyncFailed);
    });
});

describe('the progress marker', function () {
    beforeEach(function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-31 12:00:00'));
    });

    test('moves to the end of a complete window', function () {
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        expect(ZammadSyncRun::marker()->toDateTimeString())->toBe('2026-10-31 12:00:00');
        expect(TrackedTicket::query()->count())->toBe(1);
    });

    test('moves past tickets stored as 403 or 404', function (int $status) {
        TrackedTicket::factory()->create(['zammad_id' => 500]);
        fakeIntake([500 => $status], [['records' => [intakeRecord(500, '2026-10-31T11:59:00.000Z')], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        expect(ZammadSyncRun::marker())->not->toBeNull();
    })->with([403, 404]);

    test('stays when the status of an unreadable ticket could not be stored', function () {
        $row = Mockery::mock(TicketSynchronizer::class);
        $row->shouldReceive('sync')->andThrow(new RuntimeException('database gone'));
        app()->instance(TicketSynchronizer::class, $row);
        fakeIntake([], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(ZammadSyncRun::marker())->toBeNull();
    });

    test('stays on a temporary problem with one ticket', function (int $status) {
        fakeIntake([500 => $status, 501 => intakeTicket(['id' => 501, 'number' => '2140501'])], [['records' => [intakeRecord(500), intakeRecord(501)], 'total_count' => 2]]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(ZammadSyncRun::marker())->toBeNull();
        expect(ZammadSyncRun::query()->first()->tickets_failed)->toBe(1);
    })->with([500, 503, 429]);

    test('still processes the other tickets of the window after a failure', function () {
        fakeIntake([500 => 503, 501 => intakeTicket(['id' => 501, 'number' => '2140501'])], [['records' => [intakeRecord(500), intakeRecord(501)], 'total_count' => 2]]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(TrackedTicket::query()->where('zammad_id', 501)->exists())->toBeTrue();
    });

    test('stays while a ticket is locked by another path', function () {
        $lock = Cache::lock('zammad-sync.500', 60);
        $lock->get();
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(ZammadSyncRun::marker())->toBeNull();
        $lock->release();
    });

    test('stays when a page of the list fails', function () {
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 2], 503]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(ZammadSyncRun::marker())->toBeNull();
    });

    test('stays when the list ends before its total', function () {
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 2], ['records' => [], 'total_count' => 2]]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(ZammadSyncRun::marker())->toBeNull();
        expect(ZammadSyncRun::query()->first()->error_code)->toBe('list_incomplete');
    });

    test('a long outage is caught up window by window', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now()->subDays(3), 'covered_until' => now()->subDays(3)]);
        fakeIntake([], [['records' => [], 'total_count' => 0]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        expect(ZammadSyncRun::marker()->toDateTimeString())->toBe('2026-10-31 12:00:00');
        Http::assertSentCount(4);
    });

    test('an overfull window is cut in half', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now()->subMinutes(61), 'covered_until' => now()->subMinute()]);
        fakeIntake([], [['records' => [], 'total_count' => ZammadClient::SEARCH_RESULT_LIMIT + 1], ['records' => [], 'total_count' => 0]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        expect(ZammadSyncRun::marker()->toDateTimeString())->toBe('2026-10-31 12:00:00');
        Http::assertSentCount(3);
    });

    test('a failure in the second window keeps the progress of the first', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now()->subDays(2), 'covered_until' => now()->subHours(36)]);
        fakeIntake([], [['records' => [], 'total_count' => 0], 503]);

        $this->artisan('zammad:sync-recent')->assertFailed();

        expect(ZammadSyncRun::marker()->toDateTimeString())->toBe(now()->subHours(36)->subHour()->addHours(24)->toDateTimeString());
    });

    test('resumes where it stopped', function () {
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now()->subMinutes(10), 'covered_until' => now()->subMinutes(10)]);
        fakeIntake();

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => str_contains(urldecode($request->url()), 'updated_at:[2026-10-31T10:50:00Z TO 2026-10-31T12:00:00Z]'));
    });
});

describe('loading only what is needed', function () {
    test('an unchanged ticket costs no detail call, also in the overlap', function () {
        TrackedTicket::factory()->create(['zammad_id' => 500, 'zammad_updated_at' => '2026-10-30 09:00:00.500']);
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        Http::assertNotSent(fn (Request $request): bool => ! str_contains($request->url(), 'tickets/search'));
    });

    test('a change within the same second still counts as a change', function () {
        TrackedTicket::factory()->create(['zammad_id' => 500, 'zammad_updated_at' => '2026-10-30 09:00:00.100']);
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/tickets/500'));
    });

    test('a detail read without new contact loads no articles', function () {
        TrackedTicket::factory()->create([
            'zammad_id' => 500,
            'zammad_updated_at' => '2026-10-30 08:00:00',
            'last_activity_at' => '2026-10-30 08:30:00',
            'last_contact_customer_at' => '2026-10-30 08:30:00',
            'last_customer_article_id' => 9001,
        ]);
        fakeIntake([500 => intakeTicket(['state' => 'pending reminder'])]);

        intakeSync();

        expect(TrackedTicket::query()->first()->state)->toBe('pending reminder');
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_articles'));
    });

    test('a new ticket loads details and articles', function () {
        fakeIntake([500 => intakeTicket()]);

        intakeSync();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_articles'));
    });

    test('--all reads the articles of every open ticket', function () {
        TrackedTicket::factory()->create([
            'zammad_id' => 500, 'zammad_updated_at' => '2026-10-30 09:00:00.500', 'last_activity_at' => '2026-10-30 08:30:00',
            'last_contact_customer_at' => '2026-10-30 08:30:00', 'last_customer_article_id' => 9001,
        ]);
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:reconcile-open')->assertSuccessful();
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_articles'));

        $this->artisan('zammad:reconcile-open --all')->assertSuccessful();
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'ticket_articles'));
    });
});

describe('parallel runs', function () {
    test('a second bulk run ends at once without a failed run', function () {
        $lock = Cache::lock('zammad-bulk-sync', 60);
        $lock->get();
        fakeIntake();

        $this->artisan('zammad:reconcile-open')->assertSuccessful();
        $this->artisan('zammad:import-tickets')->assertSuccessful();

        expect(ZammadSyncRun::query()->count())->toBe(0);
        $lock->release();
    });

    test('the recent sync keeps running during a bulk run', function () {
        $lock = Cache::lock('zammad-bulk-sync', 60);
        $lock->get();
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:sync-recent')->assertSuccessful();

        expect(TrackedTicket::query()->count())->toBe(1);
        $lock->release();
    });

    test('two paths on the same ticket cost one Zammad read', function () {
        fakeIntake([500 => intakeTicket()]);
        intakeSync(listed: '2026-10-30T09:00:00.500Z');
        $reads = Http::recorded(fn (Request $request): bool => str_contains($request->url(), '/tickets/500'))->count();

        intakeSync(listed: '2026-10-30T09:00:00.500Z');

        expect(Http::recorded(fn (Request $request): bool => str_contains($request->url(), '/tickets/500'))->count())->toBe($reads);
    });
});

describe('the complete comparison of open tickets', function () {
    test('finds a missing ticket, however old', function () {
        fakeIntake([500 => intakeTicket(['updated_at' => '2020-01-01T00:00:00.000Z'])], [['records' => [intakeRecord(500, '2020-01-01T00:00:00.000Z')], 'total_count' => 1]]);

        $this->artisan('zammad:reconcile-open')->assertSuccessful();

        expect(TrackedTicket::query()->where('zammad_id', 500)->exists())->toBeTrue();
    });

    test('updates a known ticket with another change time', function () {
        TrackedTicket::factory()->create(['zammad_id' => 500, 'state' => 'new', 'zammad_updated_at' => '2026-10-01 00:00:00']);
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:reconcile-open')->assertSuccessful();

        expect(TrackedTicket::query()->first()->state)->toBe('open');
    });

    test('re-reads an open ticket that is no longer listed', function () {
        TrackedTicket::factory()->create(['zammad_id' => 500, 'zammad_updated_at' => '2026-10-01 00:00:00']);
        fakeIntake([500 => intakeTicket(['state' => 'closed'])], [['records' => [], 'total_count' => 0]]);

        $this->artisan('zammad:reconcile-open')->assertSuccessful();

        expect(TrackedTicket::query()->first()->is_closed)->toBeTrue();
    });

    test('checks tickets with a read problem again', function () {
        TrackedTicket::factory()->closed()->create(['zammad_id' => 500, 'sync_status' => SyncStatus::AccessDenied, 'sync_problem_since' => now()]);
        fakeIntake([500 => intakeTicket(['state' => 'closed'])], [['records' => [], 'total_count' => 0]]);

        $this->artisan('zammad:reconcile-open')->assertSuccessful();

        expect(TrackedTicket::query()->first()->sync_status)->toBe(SyncStatus::Synced);
    });

    test('reads all pages', function () {
        fakeIntake([500 => intakeTicket(), 501 => intakeTicket(['id' => 501, 'number' => '2140501'])], [['records' => [intakeRecord(500)], 'total_count' => 2], ['records' => [intakeRecord(501)], 'total_count' => 2]]);

        $this->artisan('zammad:reconcile-open')->assertSuccessful();

        expect(TrackedTicket::query()->count())->toBe(2);
    });

    test('ends without touching local tickets on an incomplete list', function () {
        TrackedTicket::factory()->create(['zammad_id' => 500, 'state' => 'new']);
        fakeIntake([500 => intakeTicket()], [['records' => [], 'total_count' => 1]]);

        $this->artisan('zammad:reconcile-open')->assertFailed();

        expect(TrackedTicket::query()->first()->state)->toBe('new');
        expect(ZammadSyncRun::query()->first()->error_code)->toBe('list_incomplete');
    });

    test('ends when more tickets are open than the search can deliver', function () {
        fakeIntake([], [['records' => [], 'total_count' => ZammadClient::SEARCH_RESULT_LIMIT + 1]]);

        $this->artisan('zammad:reconcile-open')->assertFailed();

        expect(ZammadSyncRun::query()->first()->error_code)->toBe('too_many_open');
    });

    test('the import takes over open tickets without any webhook and can be repeated', function () {
        fakeIntake([500 => intakeTicket()], [['records' => [intakeRecord(500)], 'total_count' => 1]]);

        $this->artisan('zammad:import-tickets --dry-run')->expectsOutputToContain('Offene Tickets in Zammad: 1')->assertSuccessful();
        expect(TrackedTicket::query()->count())->toBe(0);

        $this->artisan('zammad:import-tickets')->assertSuccessful();
        $this->artisan('zammad:import-tickets')->assertSuccessful();

        expect(TrackedTicket::query()->count())->toBe(1);
        expect(ZammadWebhookDelivery::query()->count())->toBe(0);
    });
});

describe('the processing state belongs to one customer message', function () {
    beforeEach(function () {
        $this->store = app(AnalysisStore::class);
    });

    /**
     * Store a finished analysis the way the analyzer does.
     */
    function intakeAnalysis(mixed $store, ?int $article, ?string $testUntil = null, string $number = '2140500'): void
    {
        $store->putResult(['ticket' => $number, 'scope' => $number, 'result' => [], 'meta' => ['staff' => 'Nele', 'based_on_article_id' => $article, 'test_until' => $testUntil]]);
    }

    test('a late analysis of an older message leaves the state alone, its result is still stored', function () {
        fakeIntake([500 => intakeTicket()], articles: [zammadArticle(['id' => 9001])]);
        intakeSync();

        fakeIntake([500 => intakeTicket(['updated_at' => '2026-10-30T10:00:00.000Z', 'last_contact_at' => '2026-10-30T10:00:00.000Z', 'last_contact_customer_at' => '2026-10-30T10:00:00.000Z'])], articles: [zammadArticle(['id' => 9001]), zammadArticle(['id' => 9002, 'created_at' => '2026-10-30T10:00:00.000Z'])]);
        intakeSync();

        intakeAnalysis($this->store, 9001);

        $ticket = TrackedTicket::query()->first();
        expect($ticket->last_customer_article_id)->toBe(9002)
            ->and($ticket->processing_state)->toBe(ProcessingState::NotAnalyzed)
            ->and($ticket->processed_article_id)->toBeNull();
        expect(Analysis::query()->where('based_on_article_id', 9001)->where('status', 'completed')->exists())->toBeTrue();
    });

    test('an analysis of the current message sets the state', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9001]);

        intakeAnalysis($this->store, 9001);

        $ticket = TrackedTicket::query()->first();
        expect($ticket->processing_state)->toBe(ProcessingState::Analyzed)->and($ticket->processed_article_id)->toBe(9001);
    });

    test('an older analysis after a finished analysis of the current message changes nothing', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9002]);

        intakeAnalysis($this->store, 9002);
        intakeAnalysis($this->store, 9001);

        $ticket = TrackedTicket::query()->first();
        expect($ticket->processing_state)->toBe(ProcessingState::Analyzed)->and($ticket->processed_article_id)->toBe(9002);
    });

    test('a late failure of an older message changes nothing', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9002]);

        $this->store->putFailure('2140500', '2140500', 'Nele', 'model', 'Timeout', 10, 9001);

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::NotAnalyzed);
    });

    test('a failure of the current message shows as failed', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9001]);

        $this->store->putFailure('2140500', '2140500', 'Nele', 'model', 'Timeout', 10, 9001);

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Failed);
    });

    test('a failed repeat does not overwrite a finished analysis of the same message', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9001]);

        intakeAnalysis($this->store, 9001);
        $this->store->putFailure('2140500', '2140500', 'Nele', 'model', 'Timeout', 10, 9001);

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Analyzed);
    });

    test('an analysis finished before the sync sees the message counts once the sync does', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'zammad_id' => 500, 'last_customer_article_id' => 9001, 'zammad_updated_at' => '2026-10-30 08:00:00', 'last_activity_at' => '2026-10-30 07:00:00']);

        intakeAnalysis($this->store, 9002);
        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::NotAnalyzed);

        fakeIntake([500 => intakeTicket()], articles: [zammadArticle(['id' => 9002])]);
        intakeSync();

        $ticket = TrackedTicket::query()->first();
        expect($ticket->last_customer_article_id)->toBe(9002)
            ->and($ticket->processing_state)->toBe(ProcessingState::Analyzed)
            ->and($ticket->processed_article_id)->toBe(9002);
    });

    test('a new customer message resets the state', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'zammad_id' => 500, 'last_customer_article_id' => 9001, 'processing_state' => ProcessingState::Analyzed, 'processed_article_id' => 9001, 'zammad_updated_at' => '2026-10-30 08:00:00', 'last_activity_at' => '2026-10-30 07:00:00']);
        fakeIntake([500 => intakeTicket()], articles: [zammadArticle(['id' => 9001]), zammadArticle(['id' => 9002])]);

        intakeSync();

        $ticket = TrackedTicket::query()->first();
        expect($ticket->processing_state)->toBe(ProcessingState::NotAnalyzed)->and($ticket->processed_article_id)->toBe(9001);
    });

    test('an agent reply does not reset the state', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'zammad_id' => 500, 'last_customer_article_id' => 9001, 'processing_state' => ProcessingState::Analyzed, 'processed_article_id' => 9001, 'zammad_updated_at' => '2026-10-30 08:00:00', 'last_activity_at' => '2026-10-30 07:00:00']);
        fakeIntake([500 => intakeTicket()], articles: [zammadArticle(['id' => 9001]), zammadArticle(['id' => 9002, 'sender' => 'Agent', 'created_by_id' => 3])]);

        intakeSync();

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Analyzed);
    });

    test('when the newest message disappears in Zammad, the analyses of the earlier one decide', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'zammad_id' => 500, 'last_customer_article_id' => 9002, 'zammad_updated_at' => '2026-10-30 08:00:00']);
        Analysis::factory()->create(['ticket_number' => '2140500', 'scope_key' => '2140500', 'status' => 'completed', 'based_on_article_id' => 9001]);
        fakeIntake([500 => intakeTicket()], articles: [zammadArticle(['id' => 9001])]);

        intakeSync(readArticles: true);

        $ticket = TrackedTicket::query()->first();
        expect($ticket->last_customer_article_id)->toBe(9001)->and($ticket->processing_state)->toBe(ProcessingState::Analyzed);
    });

    test('deleting the proposals of a ticket sets it back to not analysed, a new analysis counts again', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9001]);
        intakeAnalysis($this->store, 9001);
        $this->store->putFailure('2140500', '2140500', 'Nele', 'model', 'Timeout', 10, 9001);
        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Analyzed);

        $this->travel(1)->minutes();
        $this->store->deleteTicket('2140500', 'Etienne');

        $ticket = TrackedTicket::query()->first();
        expect($ticket->processing_state)->toBe(ProcessingState::NotAnalyzed)->and($ticket->processed_article_id)->toBeNull();

        $this->travel(1)->minutes();
        intakeAnalysis($this->store, 9001);

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Analyzed);
    });

    test('test runs and questions never change the state', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => 9001]);

        intakeAnalysis($this->store, 9001, '2026-10-30T08:00:00+00:00');
        $this->store->putResult(['ticket' => '2140500', 'scope' => '2140500.stand-9001', 'result' => [], 'meta' => ['staff' => 'Nele', 'based_on_article_id' => 9001]]);
        intakeAnalysis($this->store, 9001, null, 'F00000001');

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::NotAnalyzed);
    });

    test('a ticket without a customer message matches an analysis without one', function () {
        TrackedTicket::factory()->create(['number' => '2140500', 'last_customer_article_id' => null]);

        intakeAnalysis($this->store, null);

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Analyzed);
    });

    test('an analysis of an untracked ticket works as before', function () {
        intakeAnalysis($this->store, 9001);

        expect(Analysis::query()->count())->toBe(1);
    });

    test('a failing state update never stops an analysis', function () {
        $broken = Mockery::mock(TicketProcessingState::class);
        $broken->shouldReceive('recordAnalysis')->andThrow(new RuntimeException('database gone'));
        app()->instance(TicketProcessingState::class, $broken);

        intakeAnalysis($this->store, 9001);

        expect(Analysis::query()->where('based_on_article_id', 9001)->exists())->toBeTrue();
    });

    test('analyses from before this feature count by time when a ticket first arrives', function () {
        Analysis::factory()->create(['ticket_number' => '2140500', 'scope_key' => '2140500', 'status' => 'completed', 'based_on_article_id' => null, 'created_at' => '2026-10-30 09:00:00']);
        fakeIntake([500 => intakeTicket()], articles: [zammadArticle(['id' => 9001, 'created_at' => '2026-10-30T08:30:00.000Z'])]);

        intakeSync();

        expect(TrackedTicket::query()->first()->processing_state)->toBe(ProcessingState::Analyzed);
    });
});

describe('the same message on both ways', function () {
    test('analysis and sync agree on the current customer message', function (array $articles, int $expected) {
        fakeZammad($articles, ['id' => 500, 'number' => '2140500', 'customer_id' => 77]);
        Http::fake(['zammad.test/api/v1/tickets/search*' => Http::response([intakeTicket()])]);

        $fromTicket = app(ZammadClient::class)->ticket('2140500')->lastCustomerArticleId();
        $fromSync = app(ZammadClient::class)->lastCustomerArticle(500, 77)?->id;

        expect($fromTicket)->toBe($expected)->and($fromSync)->toBe($expected);
    })->with([
        'plain' => [[zammadArticle(['id' => 1]), zammadArticle(['id' => 2])], 2],
        'internal note last' => [[zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'internal' => true, 'sender' => 'Agent'])], 1],
        'agent reply last' => [[zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'sender' => 'Agent', 'created_by_id' => 3])], 1],
        'agent mail written by the customer' => [[zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'sender' => 'Agent', 'created_by_id' => 77, 'from' => 'erika@example.org'])], 2],
    ]);
});

describe('purging', function () {
    test('removes only what is past its period', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-31 12:00:00'));

        $oldClosed = TrackedTicket::factory()->closed()->create(['zammad_updated_at' => now()->subDays(366)]);
        $newClosed = TrackedTicket::factory()->closed()->create(['zammad_updated_at' => now()->subDays(364)]);
        $oldOpen = TrackedTicket::factory()->create(['zammad_updated_at' => now()->subDays(800)]);
        $goneLong = TrackedTicket::factory()->create(['sync_status' => SyncStatus::NotFound, 'sync_problem_since' => now()->subDays(366)]);
        $goneNew = TrackedTicket::factory()->create(['sync_status' => SyncStatus::AccessDenied, 'sync_problem_since' => now()->subDays(10)]);

        $this->artisan('tickets:purge')->assertSuccessful();

        expect(TrackedTicket::query()->pluck('id')->sort()->values()->all())->toBe(collect([$newClosed->id, $oldOpen->id, $goneNew->id])->sort()->values()->all());
    });

    test('keeps the run holding the marker, however old', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-31 12:00:00'));
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now()->subDays(100), 'covered_until' => now()->subDays(100)]);
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECENT, 'started_at' => now()->subDays(200), 'covered_until' => now()->subDays(200)]);
        ZammadSyncRun::query()->create(['type' => ZammadSyncRun::RECONCILE, 'started_at' => now()->subDays(40)]);
        ZammadWebhookDelivery::query()->create(['delivery_id' => 'alt', 'zammad_ticket_id' => 1, 'received_at' => now()->subDays(40)]);

        $this->artisan('tickets:purge')->assertSuccessful();

        expect(ZammadSyncRun::query()->count())->toBe(1);
        expect(ZammadSyncRun::marker()->toDateTimeString())->toBe(now()->subDays(100)->toDateTimeString());
        expect(ZammadWebhookDelivery::query()->count())->toBe(0);
    });
});

test('a crashed sync blocks the next runs for an hour at most', function () {
    $events = collect(app(Schedule::class)->events())->filter(fn ($event): bool => str_contains((string) $event->command, 'zammad:'));

    expect($events)->toHaveCount(3);
    $events->each(fn ($event) => expect($event->withoutOverlapping)->toBeTrue()->and($event->expiresAt)->toBeLessThanOrEqual(60));
});

test('all sync commands are scheduled', function () {
    $commands = collect(app(Schedule::class)->events())->map->command->implode(' ');

    expect($commands)->toContain('zammad:sync-recent')->toContain('zammad:reconcile-open')->toContain('zammad:reconcile-open --all')->toContain('tickets:purge');
});

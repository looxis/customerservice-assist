<?php

use App\Models\TicketTodo;
use App\Models\TodoAutoCheck;
use App\Todo\TodoStatus;
use App\Todo\TrackingNotifier;
use App\Todo\TrackingSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const TRACK_TEMPLATE = "Guten Tag Erika Beispiel,\n\nIhre Reklamation R1-7KA-0QH8-6AF8 wurde verschickt. Sendungsnummer: [Sendungsnummer] ([Carrier])";

/**
 * @param  array<string, mixed>  $overrides
 */
function trackingOrder(array $overrides = []): array
{
    $order = array_replace_recursive([
        'id' => 810001, 'external_order_id' => 'R1-7KA-0QH8-6AF8', 'origin_order_id' => 700001, 'status_name' => 'Verschickt',
        'client' => ['name' => 'LOOXIS'], 'shipping' => [], 'billing' => [], 'invoice' => [],
        'shipments' => ['data' => [['carrier' => ['name' => 'DHL'], 'tracking_no' => '00340434161094042557', 'created_at' => '12.11.2026 09:00', 'delivered' => null]]],
        'order_items' => ['data' => []],
    ], $overrides);

    return isset($overrides['shipments']) ? [...$order, 'shipments' => $overrides['shipments']] : $order;
}

function trackingTodo(array $overrides = []): TicketTodo
{
    return TicketTodo::factory()->create(array_merge([
        'zammad_id' => 51234, 'ticket_number' => '2137942', 'description' => 'Sendungsnummer nachreichen', 'kind' => 'waiting', 'waiting_for' => 'Sendungsnummer',
        'notify_customer' => true, 'notify_hint' => TRACK_TEMPLATE, 'external_ref' => 'R1-7KA-0QH8-6AF8', 'created_by' => 'Nele',
        'auto_send' => true, 'auto_state' => 'watching', 'customer_language' => 'Deutsch', 'created_at' => '2026-11-11 08:00:00',
    ], $overrides));
}

/**
 * @param  list<array<string, mixed>>  $orders
 */
function fakeTrackingServices(array $orders, int $eocsStatus = 200): void
{
    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'staff.zammad_tokens' => ['Nele' => 'nele-secret-token'],
    ]);

    $articles = [zammadArticle(['id' => 1, 'message_id' => '<m1@example.org>', 'subject' => 'Tasse kaputt', 'created_at' => '2026-11-10T09:00:00.000Z'])];

    fakeZammad($articles, extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 501, 'firstname' => 'Nele', 'lastname' => 'Gorka', 'login' => 'nele']),
        'zammad.test/api/v1/tickets/51234' => function (Request $request) {
            return Http::response(['id' => 51234, 'number' => '2137942']);
        },
        'eocs.test/api/v1/orders*' => function (Request $request) use ($orders, $eocsStatus) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => array_values(array_filter($orders, fn (array $order): bool => $order['external_order_id'] === $number)), 'meta' => []], $eocsStatus);
        },
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function trackingPuts(): array
{
    return Http::recorded(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234'))->map(fn (array $pair): array => $pair[0]->data())->values()->all();
}

function runTracking(string $moment = '2026-11-12 16:16:00'): array
{
    return app(TrackingNotifier::class)->run(CarbonImmutable::parse($moment, 'Europe/Berlin'));
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 16:16:00', 'Europe/Berlin'));
});

describe('the schedule', function () {
    function scheduleTodo(string $created): TicketTodo
    {
        return TicketTodo::factory()->make(['created_at' => CarbonImmutable::parse($created, 'Europe/Berlin')->utc()]);
    }

    function dueSlots(TicketTodo $todo, string $day): array
    {
        return array_values(array_filter(TrackingSchedule::SLOTS, fn (string $slot): bool => TrackingSchedule::isDue($todo, CarbonImmutable::parse("{$day} {$slot}", 'Europe/Berlin'), $slot)));
    }

    test('a ToDo created before 14:00 is checked at 16:07 and 18:34 that day, then at 11:26 and 16:16', function () {
        $todo = scheduleTodo('2026-11-11 09:30');

        expect(dueSlots($todo, '2026-11-11'))->toBe(['16:07', '18:34'])
            ->and(dueSlots($todo, '2026-11-12'))->toBe(['11:26', '16:16']);
    });

    test('a ToDo created between 14:00 and 16:00 is checked at 18:34 only that day', function () {
        expect(dueSlots(scheduleTodo('2026-11-11 15:00'), '2026-11-11'))->toBe(['18:34']);
    });

    test('a ToDo created after 16:00 waits for the next day', function () {
        $todo = scheduleTodo('2026-11-11 16:30');

        expect(dueSlots($todo, '2026-11-11'))->toBe([])->and(dueSlots($todo, '2026-11-12'))->toBe(['11:26', '16:16']);
    });

    test('Sundays are never checked and a ToDo of a Sunday starts on Monday', function () {
        $todo = scheduleTodo('2026-11-08 10:00');

        expect(dueSlots($todo, '2026-11-08'))->toBe([])->and(dueSlots($todo, '2026-11-09'))->toBe(['11:26', '16:16']);
    });

    test('the check ends after five days without Sundays', function () {
        $todo = scheduleTodo('2026-11-09 10:00');

        expect(dueSlots($todo, '2026-11-14'))->toBe(['11:26', '16:16'])
            ->and(dueSlots($todo, '2026-11-16'))->toBe([])
            ->and(TrackingSchedule::expired($todo, CarbonImmutable::parse('2026-11-14 11:26', 'Europe/Berlin')))->toBeFalse()
            ->and(TrackingSchedule::expired($todo, CarbonImmutable::parse('2026-11-16 11:26', 'Europe/Berlin')))->toBeTrue();
    });

    test('a run belongs to the slot it starts in, with a short tolerance', function () {
        expect(TrackingSchedule::slotAt(CarbonImmutable::parse('2026-11-12 16:16', 'Europe/Berlin')))->toBe('16:16')
            ->and(TrackingSchedule::slotAt(CarbonImmutable::parse('2026-11-12 16:20', 'Europe/Berlin')))->toBe('16:16')
            ->and(TrackingSchedule::slotAt(CarbonImmutable::parse('2026-11-12 14:00', 'Europe/Berlin')))->toBeNull()
            ->and(TrackingSchedule::slotAt(CarbonImmutable::parse('2026-11-15 11:26', 'Europe/Berlin')))->toBeNull();
    });

    test('the list names the next check', function () {
        $todo = scheduleTodo('2026-11-11 09:30');

        expect(TrackingSchedule::label($todo, CarbonImmutable::parse('2026-11-11 10:00', 'Europe/Berlin')))->toBe('heute 16:07')
            ->and(TrackingSchedule::label($todo, CarbonImmutable::parse('2026-11-11 19:00', 'Europe/Berlin')))->toBe('morgen 11:26');
    });
});

describe('the automatic reply', function () {
    test('sends the reply with the tracking number, adds a note, closes the ticket and finishes the ToDo', function () {
        fakeTrackingServices([trackingOrder()]);
        $todo = trackingTodo();

        expect(runTracking())->toBe(['checked' => 1, 'sent' => 1]);

        $reply = collect(trackingPuts())->first(fn (array $put): bool => ($put['article']['internal'] ?? true) === false);
        $note = collect(trackingPuts())->first(fn (array $put): bool => ($put['article']['internal'] ?? false) === true);
        $todo->refresh();

        expect($reply['state'])->toBe('closed')->and($reply['article']['to'])->toBe('erika@example.org')
            ->and($reply['article']['body'])->toContain('Sendungsnummer: 00340434161094042557 (DHL)')->toContain('Mit freundlichen Grüßen')->toContain('Nele Gorka')
            ->and($note['article']['body'])->toContain('Automatisch gesendet')->toContain('00340434161094042557')
            ->and($todo->status)->toBe(TodoStatus::Done)->and($todo->completed_by)->toBe('Automatik')->and($todo->auto_state)->toBe('sent')
            ->and($todo->auto_tracking)->toBe('00340434161094042557')->and($todo->auto_text)->toContain('Sendungsnummer');
        expect(TodoAutoCheck::query()->where('ticket_todo_id', $todo->id)->pluck('result')->all())->toBe(['sent']);
    });

    test('names every tracking number and carrier once when there are several shipments', function () {
        fakeTrackingServices([trackingOrder(['shipments' => ['data' => [
            ['carrier' => ['name' => 'DHL'], 'tracking_no' => 'AAA111', 'created_at' => '12.11.2026 09:00', 'delivered' => null],
            ['carrier' => ['name' => 'DHL'], 'tracking_no' => 'BBB222', 'created_at' => '12.11.2026 09:00', 'delivered' => null],
            ['carrier' => ['name' => 'DHL'], 'tracking_no' => 'AAA111', 'created_at' => '12.11.2026 09:00', 'delivered' => null],
        ]]])]);
        $todo = trackingTodo();

        runTracking();

        expect($todo->refresh()->auto_tracking)->toBe('AAA111, BBB222')->and($todo->auto_text)->toContain('Sendungsnummer: AAA111, BBB222 (DHL)');
    });

    test('does nothing but log while there is no tracking number', function () {
        fakeTrackingServices([trackingOrder(['shipments' => ['data' => []]])]);
        $todo = trackingTodo();

        expect(runTracking())->toBe(['checked' => 1, 'sent' => 0])->and(trackingPuts())->toBe([]);
        expect($todo->refresh()->auto_state)->toBe('watching')->and($todo->status)->toBe(TodoStatus::Open)->and($todo->auto_checks)->toBe(1)
            ->and(TodoAutoCheck::query()->first()->result)->toBe('none');
    });

    test('uses the newest claim when only the order is named at the ToDo', function () {
        fakeTrackingServices([
            trackingOrder(['id' => 700001, 'external_order_id' => '7KA-0QH8-6AF8', 'origin_order_id' => null, 'shipments' => ['data' => [['carrier' => ['name' => 'DHL'], 'tracking_no' => 'ORIGINAL1', 'delivered' => null]]]]),
            trackingOrder(['shipments' => ['data' => [['carrier' => ['name' => 'UPS'], 'tracking_no' => 'CLAIM1', 'created_at' => '12.11.2026 09:00', 'delivered' => null]]]]),
        ]);
        $todo = trackingTodo(['external_ref' => '7KA-0QH8-6AF8', 'notify_hint' => "Guten Tag Erika Beispiel,\n\nNummer: [Sendungsnummer] ([Carrier])"]);

        runTracking();

        expect($todo->refresh()->auto_tracking)->toBe('CLAIM1')->and($todo->auto_text)->toContain('Nummer: CLAIM1 (UPS)')->not->toContain('ORIGINAL1');
    });

    test('asks for a look instead of sending when the customer does not write German or the language is unknown', function (?string $language) {
        fakeTrackingServices([trackingOrder()]);
        $todo = trackingTodo(['customer_language' => $language]);

        runTracking();

        expect(trackingPuts())->toBe([]);
        expect($todo->refresh()->auto_state)->toBe('review')->and($todo->auto_reason)->toContain('nicht auf Deutsch')->and($todo->status)->toBe(TodoStatus::Open)
            ->and($todo->notify_hint)->toContain('00340434161094042557');
    })->with(['English', null]);

    test('asks for a look when a tracking number has no carrier or the text has an open placeholder or the employee has no token', function (array $order, array $todoFields, string $reason) {
        fakeTrackingServices([trackingOrder($order)]);
        $todo = trackingTodo($todoFields);

        runTracking();

        expect(trackingPuts())->toBe([]);
        expect($todo->refresh()->auto_state)->toBe('review')->and($todo->auto_reason)->toContain($reason);
    })->with([
        'no carrier' => [['shipments' => ['data' => [['carrier' => null, 'tracking_no' => 'AAA111', 'delivered' => null]]]], [], 'ohne Versanddienstleister'],
        'open placeholder' => [[], ['notify_hint' => 'Guten Tag, Sendungsnummer [Sendungsnummer], Betrag [Betrag]'], 'Platzhalter'],
        'empty text' => [[], ['notify_hint' => null], 'Kein Text'],
        'unknown employee' => [[], ['created_by' => 'Fremder'], 'Kein Zugang'],
    ]);

    test('sends even when the customer wrote again since the ToDo was created', function () {
        fakeTrackingServices([trackingOrder()]);
        $todo = trackingTodo(['created_at' => '2026-11-09 08:00:00']);

        expect(runTracking())->toBe(['checked' => 1, 'sent' => 1])->and($todo->refresh()->auto_state)->toBe('sent');
    });

    test('counts technical errors and asks for a look after the third in a row', function () {
        fakeTrackingServices([trackingOrder()], eocsStatus: 500);
        $todo = trackingTodo();

        runTracking('2026-11-12 11:26:00');
        runTracking('2026-11-12 16:16:00');
        expect($todo->refresh()->auto_errors)->toBe(2)->and($todo->auto_state)->toBe('watching');

        runTracking('2026-11-13 11:26:00');
        expect($todo->refresh()->auto_state)->toBe('review')->and($todo->auto_reason)->toContain('Technischer Fehler')->and(trackingPuts())->toBe([]);
    });

    test('never sends twice for the same ToDo', function () {
        fakeTrackingServices([trackingOrder()]);
        trackingTodo();

        runTracking();
        runTracking();

        expect(collect(trackingPuts())->filter(fn (array $put): bool => ($put['article']['internal'] ?? true) === false))->toHaveCount(1);
    });

    test('leaves ToDos alone that are off, finished by hand, or not due at this time', function () {
        fakeTrackingServices([trackingOrder()]);
        trackingTodo(['auto_send' => false, 'auto_state' => null]);
        trackingTodo(['status' => TodoStatus::Done]);
        trackingTodo(['created_at' => '2026-11-12 10:00:00']);

        expect(runTracking('2026-11-12 11:26:00'))->toBe(['checked' => 0, 'sent' => 0])->and(trackingPuts())->toBe([]);
    });

    test('ends the automatic check after five days and says so', function () {
        fakeTrackingServices([trackingOrder(['shipments' => ['data' => []]])]);
        $todo = trackingTodo(['created_at' => '2026-11-05 08:00:00']);

        runTracking('2026-11-12 11:26:00');

        expect($todo->refresh()->auto_state)->toBe('expired')->and($todo->status)->toBe(TodoStatus::Open);
    });

    test('finishes a sending that was started when the reply is already in Zammad, else goes back to watching', function () {
        fakeTrackingServices([trackingOrder()]);
        $stuck = trackingTodo(['auto_state' => 'sending', 'auto_sending_at' => '2026-11-12 14:00:00', 'auto_tracking' => 'AAA111', 'auto_text' => 'Text']);

        // The reply from the employee is not in the thread: the ToDo goes back to watching (and is then checked as usual).
        runTracking();

        expect($stuck->refresh()->auto_state)->toBeIn(['watching', 'sent']);
    });

    test('the command runs the check for a given slot', function () {
        fakeTrackingServices([trackingOrder()]);
        $todo = trackingTodo();

        $this->artisan('todos:check-tracking', ['--slot' => '16:16'])->assertSuccessful();

        expect($todo->refresh()->auto_state)->toBe('sent');
    });
});

describe('creating the ToDo', function () {
    test('the list shows what the automatic check did', function () {
        $this->withoutVite();
        $todo = trackingTodo(['auto_state' => 'review', 'auto_reason' => 'Sendungsnummer ohne Versanddienstleister.']);
        TodoAutoCheck::query()->create(['ticket_todo_id' => $todo->id, 'checked_at' => now(), 'result' => 'review', 'note' => 'Sendungsnummer ohne Versanddienstleister.']);

        $this->withCookie('staff_name', 'Nele')->get(route('todos.index'))->assertSeeText('Bitte prüfen: Sendungsnummer ohne Versanddienstleister.');
    });
});

describe('the history in the list', function () {
    test('a sent ToDo shows the checks, the numbers and the text that went out', function () {
        $this->withoutVite();
        fakeTrackingServices([trackingOrder()]);
        trackingTodo();
        runTracking();

        $this->withCookie('staff_name', 'Nele')->get(route('todos.index'))
            ->assertSeeText('Automatisch gesendet')->assertSeeText('00340434161094042557')->assertSeeText('Verlauf der automatischen Prüfung')->assertSeeText('Gesendeter Text')
            ->assertSeeText('Erledigt von Automatik');
    });
});

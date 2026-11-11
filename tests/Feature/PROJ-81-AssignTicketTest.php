<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config([
        'services.zammad.url' => 'https://zammad.test',
        'services.zammad.token' => 'tech-token',
        'staff.zammad_tokens' => ['Nele' => 'nele-token', 'Cara' => null],
        'staff.zammad_user_ids' => ['Nele' => 99, 'Etienne' => 3],
    ]);
});

/**
 * @param  array<string, mixed>  $ticket
 */
function assignableTicket(array $ticket = [], int $putStatus = 200, int $owner = 1): void
{
    fakeZammad(ticket: $ticket, extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 99, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
        'zammad.test/api/v1/users/*' => Http::response(['id' => 99, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
        'zammad.test/api/v1/tickets/51234*' => fn (Request $request) => $request->method() === 'PUT'
            ? Http::response(['id' => 51234], $putStatus)
            : Http::response(['id' => 51234, 'number' => '2137942', 'title' => 'Frage', 'state' => 'open', 'owner_id' => $owner, 'group' => 'allg. Kunden',
                'customer_id' => 77, 'created_at' => '2026-11-02T08:00:00.000Z', 'updated_at' => '2026-11-02T09:00:00.000Z', 'last_contact_at' => '2026-11-02T08:00:00.000Z']),
    ]);
}

function assignTicket(array $fields = [], string $staff = 'Nele'): mixed
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.assign', ['number' => '2137942']), $fields);
}

function unassignTicket(array $fields = [], string $staff = 'Nele'): mixed
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.unassign', ['number' => '2137942']), $fields);
}

describe('the buttons', function () {
    test('an open ticket shows "Zuweisen" with one dialog', function () {
        assignableTicket();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSeeText('Zuweisen')->assertSeeText('niemand')
            ->getContent();

        expect(substr_count($html, 'x-on:assign-ticket.window'))->toBe(1);
    });

    test('an assigned ticket shows the assignee and the button to take it back', function () {
        assignableTicket(owner: 3);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSeeText('Zugewiesen an')->assertSeeText('Etienne')->assertSeeText('Zuweisung zurücknehmen');
    });

    test('no buttons without a personal token', function () {
        assignableTicket();

        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')->assertOk()->assertDontSee('x-on:assign-ticket.window', false);
    });

    test('no buttons for a closed ticket', function () {
        assignableTicket(['state' => 'closed']);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertDontSee('x-on:assign-ticket.window', false);
    });
});

describe('assigning', function () {
    test('sets the owner in the name of the employee and a note in the same call', function () {
        assignableTicket();

        assignTicket(['mitarbeiter' => 'Etienne', 'notiz' => 'Bitte Foto prüfen'])
            ->assertRedirect(route('tickets.show', ['number' => '2137942']))->assertSessionHas('success', 'Ticket#2137942 wurde Etienne zugewiesen.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234')
            && $request['owner_id'] === 3 && $request['article']['internal'] === true && $request['article']['body'] === 'Bitte Foto prüfen'
            && $request->hasHeader('Authorization', 'Token token=nele-token'));
    });

    test('works without a note and for oneself', function () {
        assignableTicket();

        assignTicket(['mitarbeiter' => 'Nele'])->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request->data() === ['owner_id' => 99]);
    });

    test('rejects a name that is not listed or has no Zammad user', function () {
        assignableTicket();

        assignTicket(['mitarbeiter' => 'Fremder'])->assertSessionHasErrors('mitarbeiter');
        assignTicket(['mitarbeiter' => 'Cara'])->assertSessionHasErrors('mitarbeiter');
        assignTicket([])->assertSessionHasErrors('mitarbeiter');

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    });

    test('is forbidden without a token and for a rewound ticket', function () {
        assignableTicket();

        assignTicket(['mitarbeiter' => 'Nele'], 'Cara')->assertForbidden();
        assignTicket(['mitarbeiter' => 'Nele', 'stand' => '1'])->assertForbidden();
    });

    test('does not change a closed ticket', function () {
        assignableTicket(['state' => 'closed']);

        assignTicket(['mitarbeiter' => 'Nele'])->assertSessionHas('error');

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    });

    test('shows a clear message when Zammad refuses', function () {
        assignableTicket(putStatus: 422);

        assignTicket(['mitarbeiter' => 'Nele'])->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Die Zuweisung wurde nicht geändert'));
    });
});

describe('taking the assignment back', function () {
    test('clears the owner, keeps the status and stores an optional note', function () {
        assignableTicket(owner: 3);

        unassignTicket(['notiz' => 'Bin im Urlaub'])->assertSessionHas('success', 'Die Zuweisung von Ticket#2137942 wurde zurückgenommen.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request['owner_id'] === 1
            && ! isset($request['state']) && $request['article']['body'] === 'Bin im Urlaub');
    });

    test('works without a note and is forbidden without a token', function () {
        assignableTicket(owner: 3);

        unassignTicket()->assertSessionHas('success');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request->data() === ['owner_id' => 1]);

        unassignTicket([], 'Cara')->assertForbidden();
    });
});

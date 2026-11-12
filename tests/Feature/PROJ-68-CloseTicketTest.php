<?php

use App\Models\TrackedTicket;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    config([
        'services.zammad.url' => 'https://zammad.test',
        'services.zammad.token' => 'tech-token',
        'staff.zammad_tokens' => ['Nele' => 'nele-token', 'Cara' => null],
    ]);
});

/**
 * Zammad with one open ticket that can be changed by PUT.
 *
 * @param  array<string, mixed>  $ticket
 */
function closableTicket(array $ticket = [], int $putStatus = 200, array $articles = []): void
{
    fakeZammad($articles, ticket: $ticket, extra: [
        'zammad.test/api/v1/users/me' => Http::response(['id' => 99, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
        'zammad.test/api/v1/users/99' => Http::response(['id' => 99, 'firstname' => 'Nele', 'lastname' => 'Gorka']),
        'zammad.test/api/v1/tickets/51234*' => fn (Request $request) => $request->method() === 'PUT'
            ? Http::response(['id' => 51234], $putStatus)
            : Http::response(['id' => 51234, 'number' => '2137942', 'title' => 'Doppelt', 'state' => 'closed', 'owner_id' => 99, 'group' => 'allg. Kunden',
                'customer_id' => 77, 'created_at' => '2026-11-02T08:00:00.000Z', 'updated_at' => '2026-11-02T09:00:00.000Z', 'last_contact_at' => '2026-11-02T08:00:00.000Z']),
    ]);
}

function closeTicket(array $fields = [], string $staff = 'Nele'): mixed
{
    return test()->withCookie('staff_name', $staff)->post(route('tickets.close', ['number' => '2137942']), $fields);
}

describe('the buttons', function () {
    test('an open ticket shows the button in the header and next to "Vorschlag erstellen", with one dialog', function () {
        closableTicket();

        $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSeeTextInOrder(['In Zammad öffnen', 'Aktualisieren', 'Ohne Antwort schließen', 'Vorschlag erstellen', 'oder', 'Ticket ohne Antwort schließen'])
            ->assertSeeText('Ticket#2137942 ohne Antwort schließen?')
            ->getContent();

        expect(substr_count($html, 'x-on:close-ticket.window'))->toBe(1)
            ->and($html)->toContain('action="'.route('tickets.close', ['number' => '2137942']).'"');
    });

    test('no button without a personal token', function () {
        closableTicket();

        $this->withCookie('staff_name', 'Cara')->get('/tickets/2137942')->assertOk()->assertDontSeeText('Ohne Antwort schließen');
    });

    test('no button for a closed ticket', function () {
        closableTicket(['state' => 'closed']);

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->assertDontSeeText('Ohne Antwort schließen');
    });
});

describe('closing', function () {
    test('closes the ticket in Zammad in the name of the employee, without a note, and goes back to the list', function () {
        closableTicket();

        closeTicket()->assertRedirect(route('tickets.analyze'))->assertSessionHas('success', 'Ticket#2137942 wurde ohne Antwort geschlossen.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), 'tickets/51234')
            && $request->data() === ['state' => 'closed'] && $request->hasHeader('Authorization', 'Token token=nele-token'));
    });

    test('after an Amazon notice it goes on to the "Fall lösen" page of that message', function () {
        $link = 'https://sellercentral.amazon.de/nms/redirect/abc?nt=X&sk=Y&n=1&u=aHR0cHM6';
        closableTicket(articles: [zammadArticle(['body' => '<p>Vielen lieben Dank</p><p><a href="'.e($link).'">Fall lösen</a></p>'])]);

        closeTicket()->assertRedirect($link)->assertSessionHas('success', 'Ticket#2137942 wurde ohne Antwort geschlossen.');
    });

    test('without a "Fall lösen" link it goes back to the list', function () {
        closableTicket(articles: [zammadArticle(['body' => '<p>Danke</p><p><a href="https://evil.example/x">Fall lösen</a></p>'])]);

        closeTicket()->assertRedirect(route('tickets.analyze'));
    });

    test('a reason becomes an internal note in the same call', function () {
        closableTicket();

        closeTicket(['grund' => 'Doppelt, beantwortet in Ticket 2140187'])->assertRedirect(route('tickets.analyze'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request['state'] === 'closed'
            && $request['article']['type'] === 'note' && $request['article']['internal'] === true
            && $request['article']['body'] === 'Doppelt, beantwortet in Ticket 2140187');
    });

    test('the ticket list shows the closed state at once', function () {
        closableTicket();
        TrackedTicket::factory()->create(['zammad_id' => 51234, 'number' => '2137942', 'zammad_updated_at' => '2026-11-02 08:00:00']);

        closeTicket();

        expect(TrackedTicket::query()->sole()->is_closed)->toBeTrue();
    });

    test('an already closed ticket is not changed', function () {
        closableTicket(['state' => 'closed']);

        closeTicket()->assertRedirect(route('tickets.analyze'))->assertSessionHas('success', 'Ticket#2137942 war bereits geschlossen.');

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    });

    test('a refusal of Zammad is shown on the ticket page and nothing is claimed', function (int $status, string $message) {
        closableTicket(putStatus: $status);

        closeTicket()->assertRedirect(route('tickets.show', ['number' => '2137942']))->assertSessionHas('error', fn (string $text): bool => str_contains($text, $message));
    })->with([
        'no rights' => [403, 'Das Ticket wurde nicht geschlossen.'],
        'timeout or server error' => [502, 'Es ist unklar, ob das Ticket geschlossen wurde'],
    ]);

    test('without a personal token or in a rewound view nothing is closed', function (string $staff, array $fields) {
        closableTicket();

        closeTicket($fields, $staff)->assertForbidden();

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    })->with([
        'no token' => ['Cara', []],
        'rewound view' => ['Nele', ['stand' => '900']],
    ]);

    test('without a chosen name nothing is closed', function () {
        closableTicket();

        $this->post(route('tickets.close', ['number' => '2137942']))->assertRedirect();

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    });

    test('a GET does not close anything', function () {
        closableTicket();

        $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942/schliessen')->assertStatus(405);
    });
});

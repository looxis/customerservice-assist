<?php

use App\Http\Requests\TicketLookupRequest;
use App\Zammad\MessageBody;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->withoutVite();

    config(['services.zammad' => ['url' => 'https://zammad.test', 'token' => 'geheim-123', 'timeout' => 10, 'timezone' => 'Europe/Berlin']]);
});

/**
 * @return array<string, mixed>
 */
function zammadArticle(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'sender' => 'Customer',
        'type' => 'email',
        'internal' => false,
        'from' => 'Erika Beispiel <erika@example.org>',
        'content_type' => 'text/html',
        'body' => '<p>Das Motiv erscheint nicht.</p>',
        'created_at' => '2026-10-01T07:12:00.000Z',
        'attachments' => [],
    ], $overrides);
}

/**
 * Fake Zammad with one ticket.
 *
 * @param  list<array<string, mixed>>  $articles
 */
function fakeZammad(array $articles = [], array $ticket = [], array $extra = []): void
{
    $ticket = array_merge([
        'id' => 51234,
        'number' => '2137942',
        'title' => 'Zaubertasse – Motiv erscheint nicht',
        'state' => 'open',
        'group' => 'Kundenservice',
        'customer_id' => 77,
        'created_at' => '2026-10-01T07:12:00.000Z',
    ], $ticket);

    Http::preventStrayRequests();
    Http::fake(array_merge([
        'zammad.test/api/v1/tickets/search*' => Http::response([$ticket]),
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => Http::response($articles === [] ? [zammadArticle()] : $articles),
        'zammad.test/api/v1/users/77' => Http::response(['firstname' => 'Erika', 'lastname' => 'Beispiel', 'email' => 'erika@example.org']),
    ], $extra));
}

describe('input', function () {
    test('the start page has the central input with paste and load buttons', function () {
        $this->get('/')
            ->assertOk()
            ->assertSee('name="ticket"', false)
            ->assertSee('autofocus', false)
            ->assertSeeInOrder(['Einfügen', 'Ticket laden']);
    });

    test('every form of the copied number leads to the ticket address', function (string $input) {
        $this->get(route('tickets.lookup', ['ticket' => $input]))
            ->assertRedirect(route('tickets.show', ['number' => '2137942']));
    })->with([
        'zammad copy button' => ['Ticket#2137942'],
        'lower case with space' => ['ticket# 2137942'],
        'hash' => ['#2137942'],
        'bare number' => ['2137942'],
        'surrounding whitespace and newline' => ["  Ticket#2137942\n"],
    ]);

    test('an input without exactly one ticket number goes back with the message and asks nothing', function (mixed $input) {
        Http::preventStrayRequests();

        $this->from('/')
            ->get(route('tickets.lookup', ['ticket' => $input]))
            ->assertRedirect(route('tickets.analyze'))
            ->assertSessionHasErrors(['ticket' => TicketLookupRequest::MESSAGE]);

        Http::assertNothingSent();
    })->with(['empty' => [''], 'letters' => ['abc'], 'two numbers' => ['2137942 2137943'], 'too long' => [str_repeat('1', 21)], 'array' => [['2137942']], 'script' => ['<script>1</script>']]);

    test('the message appears at the field after the redirect', function () {
        $this->followingRedirects()
            ->get(route('tickets.lookup', ['ticket' => 'abc']))
            ->assertSeeText(TicketLookupRequest::MESSAGE);
    });

    test('the ticket address only accepts digits', function () {
        $this->get('/tickets/abc')->assertNotFound();
        $this->get('/tickets/'.str_repeat('1', 21))->assertNotFound();
    });
});

describe('loading', function () {
    test('the ticket page asks zammad with the app token and shows the ticket', function () {
        fakeZammad();

        $this->get('/tickets/2137942')
            ->assertOk()
            ->assertSee('<title>Ticket#2137942 – ', false)
            ->assertSeeText('Zaubertasse – Motiv erscheint nicht')
            ->assertSeeText('Das Motiv erscheint nicht.');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://zammad.test/api/v1/tickets/search')
            && $request['query'] === 'number:2137942'
            && $request->hasHeader('Authorization', 'Token token=geheim-123'));
    });

    test('every call asks zammad again, nothing is cached', function () {
        fakeZammad();

        $this->get('/tickets/2137942')->assertOk();
        $this->get('/tickets/2137942')->assertOk();

        Http::assertSentCount(6);
    });

    test('the token never reaches the page', function () {
        fakeZammad();

        $this->get('/tickets/2137942')->assertDontSee('geheim-123');
    });

    test('the page offers refresh and opening the ticket in zammad in a new tab', function () {
        fakeZammad();

        $html = $this->get('/tickets/2137942')->assertSeeText('Aktualisieren')->getContent();

        expect($html)->toMatch('/<a href="https:\/\/zammad\.test\/#ticket\/zoom\/51234"[^>]*target="_blank"[^>]*>\s*In Zammad öffnen/')
            ->toContain('href="'.route('tickets.show', ['number' => '2137942']).'"');
    });

    test('a search hit with another number does not count', function () {
        fakeZammad(ticket: ['number' => '21379420']);

        $this->get('/tickets/2137942')->assertNotFound()->assertSeeText('Ticket#2137942 wurde in Zammad nicht gefunden.');
    });
});

describe('header', function () {
    test('it shows number, title, state, group, customer, dates and counts', function () {
        fakeZammad([
            zammadArticle(['created_at' => '2026-10-01T07:12:00.000Z', 'attachments' => [['filename' => 'foto.jpg', 'size' => '2048', 'preferences' => ['Content-Type' => 'image/jpeg']]]]),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Nele', 'created_at' => '2026-10-02T08:30:00.000Z']),
        ]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder([
            'Ticket#2137942', 'Zaubertasse – Motiv erscheint nicht', 'Offen',
            'Kunde', 'Erika Beispiel', 'erika@example.org',
            'Gruppe', 'Kundenservice',
            'Nachrichten', '2', '1 Anhang – in Zammad ansehen',
            'Erstellt', '01.10.2026, 09:12 Uhr',
            'Letzte Nachricht', '02.10.2026, 10:30 Uhr',
        ]);
    });

    test('a closed ticket is marked and still shown', function () {
        fakeZammad(ticket: ['state' => 'closed']);

        $this->get('/tickets/2137942')->assertOk()->assertSeeText('Geschlossen')->assertSeeText('Das Motiv erscheint nicht.');
    });

    test('a merged ticket links to the target ticket in the app', function () {
        fakeZammad(ticket: ['state' => 'merged'], extra: [
            'zammad.test/api/v1/links*' => Http::response(['links' => [['link_type' => 'parent', 'link_object' => 'Ticket', 'link_object_value' => 60001]], 'assets' => ['Ticket' => ['60001' => ['number' => '2140001']]]]),
        ]);

        $this->get('/tickets/2137942')
            ->assertSeeText('zusammengeführt')
            ->assertSee('href="'.route('tickets.show', ['number' => '2140001']).'"', false);
    });
});

describe('thread', function () {
    test('messages are chronological and the three kinds are labelled', function () {
        fakeZammad([
            zammadArticle(['id' => 3, 'sender' => 'Agent', 'from' => 'Nele', 'body' => '<p>Bitte Foto schicken.</p>', 'created_at' => '2026-10-02T08:00:00.000Z']),
            zammadArticle(['id' => 1, 'body' => '<p>Erste Kundenmail.</p>', 'created_at' => '2026-10-01T07:00:00.000Z']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'internal' => true, 'type' => 'note', 'from' => 'Cara', 'body' => '<p>Amazon-Bestellung.</p>', 'created_at' => '2026-10-01T09:00:00.000Z']),
        ]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder([
            'Vom Kunden', 'Erika Beispiel', '01.10.2026, 09:00 Uhr', 'Erste Kundenmail.',
            'Intern', 'Cara', 'Notiz', 'Amazon-Bestellung.',
            'Von uns', 'Nele', 'Neueste Nachricht', 'Bitte Foto schicken.',
        ]);
    });

    test('the newest message is highlighted and scrolled to', function () {
        fakeZammad([zammadArticle(['id' => 1]), zammadArticle(['id' => 2, 'body' => '<p>Neu</p>', 'created_at' => '2026-10-03T07:00:00.000Z'])]);

        $html = $this->get('/tickets/2137942')->getContent();

        expect(substr_count($html, 'id="neueste-nachricht"'))->toBe(1)
            ->and($html)->toMatch('/id="neueste-nachricht".*?Neu<\/p>/s');
    });

    test('system messages count as ours and are marked automatic', function () {
        fakeZammad([zammadArticle(['sender' => 'System', 'from' => 'Kundenservice'])]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder(['Von uns', 'automatisch']);
    });

    test('a quoted earlier mail is collapsed', function (string $contentType, string $body, string $main, string $quoted) {
        fakeZammad([zammadArticle(['content_type' => $contentType, 'body' => $body])]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder([$main, 'Zitat anzeigen', $quoted]);
    })->with([
        'german header' => ['text/html', '<p>Danke!</p><p>Am 03.10.2026 um 10:00 schrieb Kundenservice:</p><blockquote><p>Alte Mail</p></blockquote>', 'Danke!', 'Alte Mail'],
        'english header' => ['text/html', '<div><p>Thanks!</p><div>On Fri, Oct 3, 2026 at 10:00 Kundenservice wrote:</div><blockquote>Old mail</blockquote></div>', 'Thanks!', 'Old mail'],
        'outlook header' => ['text/html', '<p>Hallo</p><hr><p><b>Von:</b> Kundenservice <b>Gesendet:</b> Freitag</p><p>Alt</p>', 'Hallo', 'Alt'],
        'gmail marker' => ['text/html', '<div>Neu<div class="gmail_quote">Am Freitag schrieb X: alt</div></div>', 'Neu', 'alt'],
        'plain text with >' => ['text/plain', "Danke!\n\n> Alte Mail\n> Zeile 2", 'Danke!', 'Alte Mail'],
        'plain text german header' => ['text/plain', "Danke!\n\nAm 03.10.2026 schrieb Kundenservice:\nAlt", 'Danke!', 'Alt'],
    ]);

    test('a mail that consists only of a quote is shown in full', function () {
        fakeZammad([zammadArticle(['body' => '<blockquote><p>Nur weitergeleitet</p></blockquote>'])]);

        $this->get('/tickets/2137942')->assertSeeText('Nur weitergeleitet')->assertDontSeeText('Zitat anzeigen');
    });

    test('above ten messages the first and the last five stay open, the rest is collapsed', function () {
        $articles = [];
        for ($i = 1; $i <= 12; $i++) {
            $articles[] = zammadArticle(['id' => $i, 'body' => "<p>Nachricht {$i}</p>", 'created_at' => sprintf('2026-10-%02dT08:00:00.000Z', $i)]);
        }
        fakeZammad($articles);

        $html = $this->get('/tickets/2137942')->getContent();
        preg_match('/<details class="group">.*?<\/details>/s', $html, $collapsed);

        expect($collapsed[0])->toContain('6 weitere Nachrichten anzeigen')
            ->toContain('Nachricht 2<')->toContain('Nachricht 7<')
            ->not->toContain('Nachricht 1<')->not->toContain('Nachricht 8<')
            ->and($html)->toMatch('/Nachricht 1<.*<details class="group">.*Nachricht 8<.*Nachricht 12</s');
    });

    test('ten messages are all open', function () {
        fakeZammad(array_map(fn (int $i): array => zammadArticle(['id' => $i, 'created_at' => sprintf('2026-10-%02dT08:00:00.000Z', $i)]), range(1, 10)));

        $this->get('/tickets/2137942')->assertDontSeeText('weitere Nachrichten anzeigen');
    });

    test('a message without text says so', function () {
        fakeZammad([zammadArticle(['body' => '', 'attachments' => [['filename' => 'foto.jpg', 'size' => 10, 'preferences' => ['Content-Type' => 'image/jpeg']]]])]);

        $this->get('/tickets/2137942')->assertSeeText('(kein Text, nur Anhang)');
    });

    test('a ticket without messages says so', function () {
        fakeZammad(extra: ['zammad.test/api/v1/ticket_articles/by_ticket/*' => Http::response([])]);

        $this->get('/tickets/2137942')
            ->assertOk()
            ->assertSeeText('Zaubertasse – Motiv erscheint nicht')
            ->assertSeeText('Dieses Ticket enthält noch keine Nachrichten.');
    });

    test('umlauts and special characters survive', function () {
        fakeZammad([zammadArticle(['content_type' => 'text/plain', 'body' => 'Grüße & Dank – „Tasse“ für 9,99 €'])]);

        $this->get('/tickets/2137942')->assertSeeText('Grüße & Dank – „Tasse“ für 9,99 €');
    });
});

describe('cleaning of mail html', function () {
    test('scripts, images, styles and event handlers are removed, text structure and links stay', function () {
        $parsed = app(MessageBody::class)->parse(
            '<html><head><style>p{color:red}</style><script>alert(1)</script></head><body>'
            .'<p style="color:red" onclick="alert(2)">Hallo <b>Welt</b></p><img src="https://tracker.example/pixel.gif">'
            .'<ul><li>Eins</li></ul><a href="https://looxis.de">Shop</a> <a href="javascript:alert(3)">böse</a>'
            .'<iframe src="https://evil.example"></iframe><font face="Comic Sans">Schrift</font></body></html>',
            'text/html',
        );

        expect((string) $parsed['body'])
            ->not->toContain('<script')->not->toContain('alert')->not->toContain('<img')->not->toContain('style=')
            ->not->toContain('onclick')->not->toContain('<iframe')->not->toContain('<font')->not->toContain('javascript:')
            ->toContain('<p>Hallo <b>Welt</b></p>')
            ->toContain('<li>Eins</li>')
            ->toContain('href="https://looxis.de"')
            ->toContain('target="_blank"')
            ->toContain('Schrift');
    });

    test('long mails are not cut off', function () {
        $long = str_repeat('Lorem ipsum dolor sit amet. ', 2000);

        expect(strlen((string) app(MessageBody::class)->parse("<p>{$long}</p>", 'text/html')['body']))->toBeGreaterThan(50_000);
    });

    test('plain text is escaped and keeps its line breaks', function () {
        $parsed = app(MessageBody::class)->parse("Zeile 1\nZeile <2>\n\nAbsatz", 'text/plain');

        expect((string) $parsed['body'])->toBe("<p>Zeile 1<br />\nZeile &lt;2&gt;</p><p>Absatz</p>");
    });
});

describe('attachments', function () {
    test('attachments are listed with name, kind and size, embedded images are left out', function () {
        fakeZammad([zammadArticle(['attachments' => [
            ['filename' => 'tasse.jpg', 'size' => '2480311', 'preferences' => ['Content-Type' => 'image/jpeg']],
            ['filename' => 'rechnung.pdf', 'size' => '51200', 'preferences' => ['Content-Type' => 'application/pdf']],
            ['filename' => 'logo.png', 'size' => '1200', 'preferences' => ['Content-Type' => 'image/png', 'Content-ID' => 'logo@mail', 'Content-Disposition' => 'inline']],
        ]])]);

        $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['tasse.jpg', 'Bild, 2,4 MB', 'rechnung.pdf', 'PDF, 50 KB'])
            ->assertDontSeeText('logo.png')
            ->assertSeeText('2 Anhänge – in Zammad ansehen');
    });
});

describe('errors', function () {
    test('zammad problems show a clear message with the right status and keep the input', function (Closure $fake, int $status, string $message, bool $retry) {
        Http::preventStrayRequests();
        $fake();

        $response = $this->get('/tickets/2137942')
            ->assertStatus($status)
            ->assertSeeText($message)
            ->assertSee('value="Ticket#2137942"', false);

        $retry ? $response->assertSeeText('Erneut versuchen') : $response->assertDontSeeText('Erneut versuchen');
    })->with([
        'not found' => [fn () => Http::fake(['*' => Http::response([])]), 404, 'Ticket#2137942 wurde in Zammad nicht gefunden. Bitte die Nummer prüfen.', false],
        'no access' => [fn () => Http::fake(['*' => Http::response(['error' => 'Not authorized'], 403)]), 403, 'Auf Ticket#2137942 hat die App in Zammad keinen Zugriff.', false],
        'invalid token' => [fn () => Http::fake(['*' => Http::response(['error' => 'Invalid token'], 401)]), 503, 'Die Verbindung zu Zammad ist nicht eingerichtet oder ungültig.', false],
        'server error' => [fn () => Http::fake(['*' => Http::response('Bad gateway', 502)]), 503, 'Zammad ist gerade nicht erreichbar. Bitte in einer Minute erneut versuchen.', true],
        'timeout' => [fn () => Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out')), 503, 'Zammad ist gerade nicht erreichbar.', true],
        'not json' => [fn () => Http::fake(['*' => Http::response('<html>Wartung</html>')]), 503, 'Zammad ist gerade nicht erreichbar.', true],
    ]);

    test('missing configuration is reported without asking zammad', function () {
        Http::preventStrayRequests();
        config(['services.zammad.token' => null]);

        $this->get('/tickets/2137942')->assertStatus(503)->assertSeeText('Die Verbindung zu Zammad ist nicht eingerichtet oder ungültig.');

        Http::assertNothingSent();
    });

    test('error pages show no technical details, addresses or tokens', function () {
        Http::fake(['*' => Http::response('SQLSTATE secret stack trace', 500)]);

        $this->get('/tickets/2137942')
            ->assertDontSee('zammad.test')
            ->assertDontSee('geheim-123')
            ->assertDontSee('SQLSTATE');
    });

    test('the log names number and problem but no ticket content', function () {
        Log::spy();
        Http::fake(['*' => Http::response(['error' => 'x'], 403)]);

        $this->get('/tickets/2137942');

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context === ['ticket' => '2137942', 'problem' => 'forbidden', 'status' => 403]);
    });
});

describe('without a chosen name', function () {
    test('loading and viewing work and the name hint stays', function () {
        fakeZammad();

        $this->get('/tickets/2137942')->assertOk()->assertSeeText('Bitte wähle zuerst deinen Namen.');
    });
});

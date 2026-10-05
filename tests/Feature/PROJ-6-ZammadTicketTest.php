<?php

use App\Http\Requests\TicketLookupRequest;
use App\Zammad\MessageBody;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->withoutVite();

    config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'geheim-123', 'services.zammad.timeout' => 10]);
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

    test('customer messages sit left with an orange edge, ours and internal notes right, all four fifths wide', function () {
        fakeZammad([
            zammadArticle(['id' => 1]),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Nele', 'created_at' => '2026-10-02T07:00:00.000Z']),
            zammadArticle(['id' => 3, 'sender' => 'Agent', 'internal' => true, 'from' => 'Cara', 'created_at' => '2026-10-03T07:00:00.000Z']),
        ]);

        preg_match_all('/<article\b[^>]*class="([^"]*)"/s', $this->get('/tickets/2137942')->getContent(), $classes);

        expect($classes[1])->toHaveCount(3)
            ->and($classes[1][0])->toContain('md:w-4/5')->toContain('border-l-brand')->toContain('md:mr-auto')
            ->and($classes[1][1])->toContain('md:w-4/5')->toContain('border-l-slate-400')->toContain('md:ml-auto')
            ->and($classes[1][2])->toContain('md:w-4/5')->toContain('border-l-warning-500')->toContain('md:ml-auto');
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
        'dutch reply header' => ['text/html', '<div>Bedankt!</div><div>Op 12 jun 2026 om 08:55 heeft Kundenservice &lt;service@example.org&gt; het volgende geschreven:</div><blockquote type="cite">Oud bericht</blockquote>', 'Bedankt!', 'Oud bericht'],
        'dutch forward' => ['text/html', '<div>Hoi.<div>Groet<div><br>Begin doorgestuurd bericht:<br><blockquote type="cite"><div><b>Van:</b> Klant</div>Oud</blockquote></div></div></div>', 'Hoi.', 'Oud'],
        'dutch outlook header' => ['text/html', '<p>Hallo</p><p><b>Van:</b> Kundenservice<br><b>Verzonden:</b> vrijdag</p><p>Oud</p>', 'Hallo', 'Oud'],
        'apple mail german forward' => ['text/html', '<div>Siehe unten.</div><div>Anfang der weitergeleiteten Nachricht:</div><blockquote type="cite">Alt</blockquote>', 'Siehe unten.', 'Alt'],
        'zammad marker deep inside' => ['text/html', '<div><div>Neu<br><span class="js-signatureMarker"></span><div>Kundenservice</div><div>Alt</div></div></div>', 'Neu', 'Alt'],
        'quote nested two levels deep' => ['text/html', '<div>Hi.<div>Text<div>Gruß<blockquote type="cite">Alt</blockquote></div></div></div>', 'Gruß', 'Alt'],
    ]);

    test('everything after the quote start is collapsed, even outside the enclosing element', function () {
        $parsed = app(MessageBody::class)->parse('<div>Neu<div>Am 01.10.2026 schrieb X:<blockquote>Alt</blockquote></div>Danach</div><p>Rest</p>', 'text/html');

        expect(strip_tags((string) $parsed['body']))->toBe('Neu')
            ->and(strip_tags((string) $parsed['quote']))->toContain('Alt')->toContain('Danach')->toContain('Rest');
    });

    test('a reply written below the quote shows the new text and collapses the quote at the start', function () {
        fakeZammad([zammadArticle(['body' => '<div>Op 12 jun 2026, om 08:55 heeft Kundenservice het volgende geschreven:<br><span class="js-signatureMarker"></span><blockquote type="cite"><div>Alte Antwort</div></blockquote></div><div><br></div><div>Goedemiddag</div><div>Nieuwe tekst</div>'])]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder(['Goedemiddag', 'Nieuwe tekst', 'Zitat anzeigen', 'Alte Antwort']);
    });

    test('lines written as div elements stay separate lines', function () {
        expect((string) app(MessageBody::class)->parse('<div>Zeile 1</div><div>Zeile 2</div>', 'text/html')['body'])
            ->toBe('<div>Zeile 1</div><div>Zeile 2</div>');
    });

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

describe('our signature', function () {
    test('the signature zammad marks in our messages is hidden', function () {
        fakeZammad([zammadArticle(['sender' => 'Agent', 'from' => 'Nele', 'body' => '<p>Gerne geschehen.</p><p>Best regards</p><div data-signature="true" data-signature-id="1">Nele<br>LOOXIS GmbH<br>Musterstraße 1</div>'])]);

        $this->get('/tickets/2137942')
            ->assertSeeText('Gerne geschehen.')
            ->assertDontSeeText('LOOXIS GmbH')
            ->assertDontSeeText('Musterstraße 1');
    });

    test('a configured text signature at the end of our message is hidden, regardless of case and line breaks', function () {
        config(['services.zammad.signatures' => ["Freundliche Grüße\nLOOXIS Kundenservice"]]);
        fakeZammad([zammadArticle(['sender' => 'Agent', 'type' => 'amazon', 'content_type' => 'text/plain', 'from' => 'Kundenservice', 'body' => "Ihre Erstattung ist veranlasst.\n\nfreundliche Grüße\nLOOXIS   Kundenservice\n"])]);

        $this->get('/tickets/2137942')
            ->assertSeeText('Ihre Erstattung ist veranlasst.')
            ->assertDontSeeText('LOOXIS Kundenservice');
    });

    test('the signature is only removed at the end, not in the middle of a text', function () {
        $body = new MessageBody(["Freundliche Grüße\nLOOXIS Kundenservice"]);

        expect(strip_tags((string) $body->parse('<p>Freundliche Grüße LOOXIS Kundenservice sind wir.</p><p>Weiter</p>', 'text/html', ours: true)['body']))
            ->toContain('Freundliche Grüße LOOXIS Kundenservice sind wir.');
    });

    test('a message consisting only of the signature keeps it', function () {
        $body = new MessageBody(['LOOXIS Kundenservice']);

        expect(strip_tags((string) $body->parse('LOOXIS Kundenservice', 'text/plain', ours: true)['body']))->toBe('LOOXIS Kundenservice');
    });

    test('customer messages keep their text and signature', function () {
        fakeZammad([zammadArticle(['body' => '<p>Danke</p><div data-signature="true">Erika Beispiel<br>Musterweg 2</div>'])]);

        $this->get('/tickets/2137942')->assertSeeText('Musterweg 2');
    });
});

describe('boilerplate', function () {
    test('amazon\'s notice and everything after it is hidden, the customer text stays', function () {
        $html = '<table><tr><td>Du hast eine Nachricht erhalten.</td></tr><tr><td><pre>Ciao, il pacco è pronto.</pre></td></tr>'
            .'<tr><td><a href="https://sellercentral.amazon.it/x">Fall lösen</a></td></tr>'
            .'<tr><td><p>Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten. Bitte beachten Sie …</p></td></tr>'
            .'<tr><td>Copyright Amazon</td></tr></table><p>Amazon Services Europe</p>';

        fakeZammad([zammadArticle(['body' => $html])]);

        $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Du hast eine Nachricht erhalten.', 'Ciao, il pacco è pronto.', 'Fall lösen'])
            ->assertDontSeeText('Dieser Service wird')
            ->assertDontSeeText('Copyright Amazon')
            ->assertDontSeeText('Amazon Services Europe');
    });

    test('the footer text is found regardless of case and line breaks, also in plain text', function () {
        $body = new MessageBody(footers: ['Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten']);

        expect(strip_tags((string) $body->parse("<div>Frage</div><div>dieser Service wird\nausschließlich für die Kommunikation mit Käufern angeboten.</div>", 'text/html')['body']))->toBe('Frage')
            ->and(strip_tags((string) $body->parse("Frage\nDieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten.\nRest", 'text/plain')['body']))->toBe('Frage');
    });

    test('the footer texts come from the configuration', function () {
        config(['services.zammad.footers' => ['Diese E-Mail wurde automatisch erstellt']]);

        expect(strip_tags((string) app(MessageBody::class)->parse('<p>Text</p><p>Diese E-Mail wurde automatisch erstellt.</p><p>Rest</p>', 'text/html')['body']))->toBe('Text');
    });

    test('long lines in pre blocks are kept for wrapping, not cut', function () {
        $line = str_repeat('parola ', 300);

        expect((string) app(MessageBody::class)->parse("<pre>{$line}</pre>", 'text/html')['body'])->toContain(trim($line));
    });
});

/**
 * A fictitious Amazon buyer-message notice in Amazon's table layout.
 */
function amazonNotice(string $message, string $order = '402-0000000-0000001', array $products = [['B000TEST01', 'Zaubertasse schwarz']]): string
{
    $rows = implode('', array_map(fn (array $product, int $index): string => '<tr><td> '.($index + 1).' </td><td> '.$product[0].' </td><td> '.$product[1].' </td></tr>', $products, array_keys($products)));

    return '<center><table><tr><td><table><tr><td>'
        .'<p>Du hast eine Nachricht erhalten.</p>'
        .'<p>Bestellnummer '.$order.':</p>'
        .'<table><tbody><tr><td># </td><td>ASIN </td><td>Produktname </td></tr>'.$rows.'</tbody></table>'
        .'<h4><strong>Nachricht:</strong></h4>'
        .'<table><tr><th><pre>'.$message.'</pre></th></tr></table>'
        .'<table><tr><td><a href="https://sellercentral.amazon.it/nms/redirect/x">Fall lösen</a></td></tr></table>'
        .'<p>Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten.</p>'
        .'</td></tr></table></td></tr></table></center>';
}

describe('amazon notice', function () {
    test('the repeated header is removed from each amazon message, only the buyer\'s words remain', function () {
        fakeZammad([zammadArticle(['body' => amazonNotice('Il pacco è pronto?')])]);

        $html = $this->get('/tickets/2137942')->getContent();
        preg_match('/<div class="mail-text">(.*?)<\/div>\s*<\/div>/s', $html, $text);

        expect(strip_tags($text[1]))->toContain('Il pacco è pronto?')->toContain('Fall lösen')
            ->not->toContain('Du hast eine Nachricht erhalten')
            ->not->toContain('Bestellnummer')
            ->not->toContain('ASIN')
            ->not->toContain('Nachricht:');
    });

    test('the order is shown once in the header with known fields, unknown ones stay empty', function () {
        fakeZammad([
            zammadArticle(['id' => 1, 'body' => amazonNotice('Erste Frage', products: [['B000TEST01', 'Zaubertasse schwarz'], ['B000TEST02', 'Fototasse weiß']])]),
            zammadArticle(['id' => 2, 'body' => amazonNotice('Zweite Frage'), 'created_at' => '2026-10-02T07:00:00.000Z']),
        ]);

        $html = $this->get('/tickets/2137942')
            ->assertSeeTextInOrder([
                'Bestellung', 'aus Amazon-Nachricht',
                'Bestellnummer', '402-0000000-0000001', 'Rechnungsnummer', '–',
                'Produkt', 'ASIN', 'SKU', 'Anzahl',
                'Zaubertasse schwarz', 'B000TEST01', '–', '–',
                'Fototasse weiß', 'B000TEST02',
                'Erste Frage', 'Zweite Frage',
            ])
            ->getContent();

        expect(substr_count($html, 'aria-label="Bestellung 402-0000000-0000001"'))->toBe(1)
            ->and(substr_count($html, '>Zaubertasse schwarz<'))->toBe(1);
    });

    test('different orders in one ticket are shown separately', function () {
        fakeZammad([
            zammadArticle(['id' => 1, 'body' => amazonNotice('A', '402-0000000-0000001')]),
            zammadArticle(['id' => 2, 'body' => amazonNotice('B', '302-0000000-0000002'), 'created_at' => '2026-10-02T07:00:00.000Z']),
        ]);

        $this->get('/tickets/2137942')->assertSeeTextInOrder(['402-0000000-0000001', '302-0000000-0000002']);
    });

    test('a ticket without amazon notice has no order block', function () {
        fakeZammad([zammadArticle(['body' => '<p>Meine Bestellnummer ist 402-0000000-0000001.</p>'])]);

        $this->get('/tickets/2137942')
            ->assertDontSee('aria-label="Bestellung', false)
            ->assertSeeText('Meine Bestellnummer ist 402-0000000-0000001.');
    });

    test('a customer text that merely mentions "Nachricht:" is kept', function () {
        fakeZammad([zammadArticle(['body' => amazonNotice('Nachricht: bitte schnell antworten')])]);

        $this->get('/tickets/2137942')->assertSeeText('Nachricht: bitte schnell antworten');
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
            ->toContain('Schrift');
    });

    test('links are replaced, a describing link text stays in front', function (string $html, string $expected) {
        $body = (string) app(MessageBody::class)->parse($html, 'text/html')['body'];

        expect($body)->toBe($expected)
            ->not->toContain('<a ')
            ->not->toContain('drive.google');
    })->with([
        'describing text' => ['<p><a href="https://drive.google.com/file/d/x">Bildinformationen zur Bestellung</a></p>', '<p>Bildinformationen zur Bestellung [Link entfernt]</p>'],
        'address as text' => ['<p>Siehe <a href="https://drive.google.com/x">https://drive.google.com/x</a></p>', '<p>Siehe [Link entfernt]</p>'],
        'empty link' => ['<p>Hier<a href="https://evil.example"><img src="x"></a></p>', '<p>Hier[Link entfernt]</p>'],
        'nested markup' => ['<p><a href="https://evil.example"><b>Jetzt</b> öffnen</a></p>', '<p>Jetzt öffnen [Link entfernt]</p>'],
        'mailto' => ['<p><a href="mailto:a@example.org">Schreiben</a></p>', '<p>Schreiben [Link entfernt]</p>'],
        'allowed host shown as text' => ['<p>Shop: <a href="https://fachhaendler.looxis.de/login">https://fachhaendler.looxis.de</a></p>', '<p>Shop: https://fachhaendler.looxis.de</p>'],
        'allowed host with describing text' => ['<p><a href="https://www.dhl.de/de/privatkunden.html">Sendung verfolgen</a></p>', '<p>Sendung verfolgen</p>'],
        'allowed host without text shows the address' => ['<p><a href="https://looxis.de/agb"></a></p>', '<p>https://looxis.de/agb</p>'],
        'allowed address as text, foreign target' => ['<p><a href="https://evil.example">https://looxis.de</a></p>', '<p>[Link entfernt]</p>'],
        'look-alike host' => ['<p><a href="https://looxis.de.evil.example/x">Login</a></p>', '<p>Login [Link entfernt]</p>'],
        'allowed host over another scheme' => ['<p><a href="javascript://looxis.de/%0aalert(1)">Klick</a></p>', '<p>Klick [Link entfernt]</p>'],
    ]);

    test('addresses written as plain text stay unchanged and are not clickable', function () {
        expect((string) app(MessageBody::class)->parse('<p>Siehe www.dhl.de oder https://drive.google.com/x</p>', 'text/html')['body'])
            ->toBe('<p>Siehe www.dhl.de oder https://drive.google.com/x</p>')
            ->and((string) app(MessageBody::class)->parse("Foto: https://drive.google.com/x\nDanke", 'text/plain')['body'])
            ->toBe("<p>Foto: https://drive.google.com/x<br />\nDanke</p>");
    });

    test('links to trusted hosts stay clickable in a new tab', function (string $href, bool $clickable) {
        $body = (string) app(MessageBody::class)->parse('<p><a href="'.$href.'">Fall lösen</a></p>', 'text/html')['body'];

        $clickable
            ? expect($body)->toBe('<p><a href="'.$href.'" target="_blank" rel="noopener noreferrer nofollow">Fall lösen</a></p>')
            : expect($body)->toBe('<p>Fall lösen [Link entfernt]</p>');
    })->with([
        'amazon italy' => ['https://sellercentral.amazon.it/nms/redirect/abc', true],
        'amazon germany' => ['https://sellercentral.amazon.de/nms/redirect/abc', true],
        'amazon uk' => ['https://sellercentral.amazon.co.uk/x', true],
        'look-alike domain' => ['https://sellercentral.amazon.evil.com/x', false],
        'look-alike prefix' => ['https://sellercentral.amazon.it.evil.example/x', false],
        'other amazon host' => ['https://www.amazon.de/x', false],
        'without https' => ['http://sellercentral.amazon.it/x', false],
    ]);

    test('a trusted link without text disappears instead of showing a marker', function () {
        expect((string) app(MessageBody::class)->parse('<p>Hallo<a href="https://sellercentral.amazon.it/x"><img src="logo.png"></a></p>', 'text/html')['body'])
            ->toBe('<p>Hallo</p>');
    });

    test('the allowed hosts come from the configuration', function () {
        config(['services.zammad.allowed_link_hosts' => ['example.org']]);

        expect((string) app(MessageBody::class)->parse('<p><a href="https://shop.example.org">Shop</a> <a href="https://looxis.de">LOOXIS</a></p>', 'text/html')['body'])
            ->toBe('<p>Shop LOOXIS [Link entfernt]</p>');
    });

    test('links in the collapsed quote are replaced too', function () {
        $parsed = app(MessageBody::class)->parse('<p>Neu</p><blockquote><a href="https://evil.example">Klick</a></blockquote>', 'text/html');

        expect((string) $parsed['quote'])->toContain('Klick [Link entfernt]')->not->toContain('evil.example');
    });

    test('the ticket page shows no links from mails', function () {
        fakeZammad([zammadArticle(['body' => '<p>Bitte prüfen: <a href="https://drive.google.com/x">Bilder</a> und <a href="https://fachhaendler.looxis.de">Shop</a></p>'])]);

        $html = $this->get('/tickets/2137942')
            ->assertSeeText('Bitte prüfen: Bilder [Link entfernt] und Shop')
            ->assertDontSee('drive.google.com')
            ->getContent();

        preg_match('/<div class="mail-text">.*?<\/div>/s', $html, $text);

        expect($text[0])->not->toContain('<a ');
    });

    test('blank lines are reduced to at most one', function (string $html, string $expected) {
        expect((string) app(MessageBody::class)->parse($html, 'text/html')['body'])->toBe($expected);
    })->with([
        'many breaks in running text' => ['Hallo<br><br><br><br>Welt', 'Hallo<br /><br />Welt'],
        'empty div lines' => ['<div>Hallo</div><div>&nbsp;</div><div><br></div><div> </div><div>Welt</div>', '<div>Hallo</div><br /><div>Welt</div>'],
        'breaks at the end of a block' => ['<div>Hallo<br><br></div><div>Welt</div>', '<div>Hallo</div><div>Welt</div>'],
        'blank start and end' => ['<br><br><div>&nbsp;</div>Hallo<br><br><br>', 'Hallo'],
        'single blank line stays' => ['Hallo<br><br>Welt', 'Hallo<br /><br />Welt'],
    ]);

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
            ['filename' => 'message.html', 'size' => '1303', 'preferences' => ['content-alternative' => true, 'original-format' => true, 'Mime-Type' => 'text/html', 'Charset' => 'utf-8']],
        ]])]);

        $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['tasse.jpg', 'Bild, 2,4 MB', 'rechnung.pdf', 'PDF, 50 KB'])
            ->assertDontSeeText('logo.png')
            ->assertDontSeeText('message.html')
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

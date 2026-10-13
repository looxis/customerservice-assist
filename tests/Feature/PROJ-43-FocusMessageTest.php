<?php

use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

beforeEach(function () {
    $this->withoutVite();
    config(['services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token', 'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token', 'staff.admins' => ['Etienne']]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function focusArticle(ArticleKind $kind, int $id): TicketArticle
{
    return new TicketArticle($kind, 'Absender', CarbonImmutable::parse('2026-10-01 08:00'), new HtmlString('<p>x</p>'), id: $id);
}

function focusTicket(array $articles): Ticket
{
    return new Ticket('1', 1, 'T', 'open', false, null, null, null, null, CarbonImmutable::now(), $articles, '');
}

describe('which message', function () {
    test('the last customer message, not our answer and not a note that came later', function () {
        $ticket = focusTicket([focusArticle(ArticleKind::Customer, 1), focusArticle(ArticleKind::Customer, 2), focusArticle(ArticleKind::Agent, 3), focusArticle(ArticleKind::Internal, 4)]);

        expect($ticket->focusArticle()->id)->toBe(2);
    });

    test('without a customer message the last message, for an empty thread nothing', function () {
        expect(focusTicket([focusArticle(ArticleKind::Agent, 1), focusArticle(ArticleKind::Internal, 2)])->focusArticle()->id)->toBe(2)
            ->and(focusTicket([])->focusArticle())->toBeNull();
    });
});

describe('ticket page', function () {
    test('a single message stands on top as the customer message, above header, form and thread, without a link to the thread', function () {
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Mein Motiv ist zu klein.</p>'])]);

        $html = $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Kundennachricht', 'Mein Motiv ist zu klein.', 'Ticket#2137942', 'Vorschlag erstellen', 'Verlauf'])
            ->assertDontSeeText('Der ganze Verlauf')
            ->assertDontSeeText('Ticket aus Zammad')
            ->getContent();

        expect(substr_count($html, 'Mein Motiv ist zu klein.'))->toBe(2);
    });

    test('several messages show the last customer message on top and point to the thread below', function () {
        fakeZammad([
            zammadArticle(['id' => 1, 'body' => '<p>Erste Frage</p>']),
            zammadArticle(['id' => 2, 'sender' => 'Agent', 'from' => 'Nele', 'body' => '<p>Unsere Antwort</p>', 'created_at' => '2026-10-02T07:00:00.000Z']),
            zammadArticle(['id' => 3, 'body' => '<p>Zweite Frage vom Kunden</p>', 'created_at' => '2026-10-03T07:00:00.000Z']),
            zammadArticle(['id' => 4, 'sender' => 'Agent', 'internal' => true, 'from' => 'Cara', 'body' => '<p>Interne Notiz</p>', 'created_at' => '2026-10-04T07:00:00.000Z']),
        ]);

        $html = $this->get('/tickets/2137942')
            ->assertSeeTextInOrder(['Letzte Kundennachricht', 'Der ganze Verlauf (4 Nachrichten) steht unten', 'Zweite Frage vom Kunden'])
            ->getContent();
        $top = str($html)->before('id="verlauf"')->toString();

        expect($top)->toContain('Zweite Frage vom Kunden')->not->toContain('Unsere Antwort')->not->toContain('Interne Notiz')->not->toContain('Erste Frage')
            ->and($html)->toContain('href="#verlauf"')
            ->and(substr_count($html, 'id="neueste-nachricht"'))->toBe(1);
    });

    test('a ticket that cannot be loaded keeps the field to fetch a ticket', function () {
        Http::fake(['zammad.test/*' => Http::response([], 404)]);

        $this->get('/tickets/2137942')->assertSeeText('Ticket aus Zammad')->assertDontSeeText('Letzte Kundennachricht');
    });

    test('the message on top is escaped like in the thread', function () {
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Hallo &lt;script&gt;alert(1)&lt;/script&gt;</p>'])]);

        expect($this->get('/tickets/2137942')->getContent())->not->toContain('<script>alert(1)</script>');
    });

    test('the fetch field stays on the start page for a further ticket', function () {
        $this->get('/')->assertSeeText('Ticket aus Zammad');
    });

    test('a rewound test run shows the customer message of the cut point on top', function () {
        fakeZammad([
            zammadArticle(['id' => 11, 'body' => '<p>Anfrage zum Stand</p>']),
            zammadArticle(['id' => 12, 'sender' => 'Agent', 'from' => 'Nele', 'body' => '<p>Antwort später</p>', 'created_at' => '2026-10-02T07:00:00.000Z']),
            zammadArticle(['id' => 13, 'body' => '<p>Spätere Kundennachricht</p>', 'created_at' => '2026-10-03T07:00:00.000Z']),
        ]);

        $top = str($this->withCookie('staff_name', 'Etienne')->withCookie('test_mode', '1')->get('/tickets/2137942?stand=11')->getContent())->before('id="verlauf"')->toString();

        expect($top)->toContain('Anfrage zum Stand')->not->toContain('Spätere Kundennachricht');
    });
});

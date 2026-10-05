<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

/**
 * A fictitious ticket for the local preview (/styleguide/ticket) while no
 * Zammad access exists. Contains no real customer data.
 */
class SampleTicket
{
    public static function make(int $replies = 6): Ticket
    {
        $start = CarbonImmutable::parse('2026-10-01 09:12');
        $articles = [
            new TicketArticle(
                ArticleKind::Customer,
                'Erika Beispiel',
                $start,
                new HtmlString('<p>Hallo,</p><p>ich habe die Zaubertasse bekommen, aber das Motiv erscheint nicht, wenn ich heißen Kaffee einfülle. Die Tasse bleibt einfach schwarz.</p><p>Viele Grüße<br>Erika Beispiel</p>'),
                attachments: [
                    new TicketAttachment('tasse-kalt.jpg', 'image/jpeg', 2_480_311),
                    new TicketAttachment('tasse-warm.jpg', 'image/jpeg', 2_311_022),
                ],
                channel: 'E-Mail',
            ),
            new TicketArticle(
                ArticleKind::Agent,
                'Kundenservice',
                $start->addMinutes(3),
                new HtmlString('<p>Vielen Dank für Ihre Nachricht. Wir melden uns so schnell wie möglich.</p>'),
                channel: 'E-Mail',
                automatic: true,
            ),
            new TicketArticle(
                ArticleKind::Internal,
                'Nele',
                $start->addHours(2),
                new HtmlString('<p>Bestellung über Amazon. Fotos angesehen: Tasse wirkt kalt normal schwarz, warm leicht durchscheinend. Test mit 80 °C anfragen.</p>'),
                channel: 'Notiz',
            ),
        ];

        for ($i = 1; $i <= $replies; $i++) {
            $articles[] = $i % 2 === 1
                ? new TicketArticle(
                    ArticleKind::Agent,
                    'Nele',
                    $start->addDays($i)->setTime(10, 5),
                    new HtmlString("<p>Wir möchten gern prüfen, warum Ihr Motiv nicht sichtbar wird. Bitte füllen Sie die Tasse mindestens zur Hälfte mit mindestens 80 °C heißem Wasser und senden Sie uns ein Foto. (Antwort {$i})</p>"),
                    quote: new HtmlString('<p>Am 01.10.2026 um 09:12 schrieb Erika Beispiel:</p><blockquote><p>ich habe die Zaubertasse bekommen, aber das Motiv erscheint nicht …</p></blockquote>'),
                    channel: 'E-Mail',
                )
                : new TicketArticle(
                    ArticleKind::Customer,
                    'Erika Beispiel',
                    $start->addDays($i)->setTime(18, 40),
                    new HtmlString("<p>Ich habe es versucht, das Motiv ist nur ganz schwach zu sehen. Foto anbei. (Erwiderung {$i})</p>"),
                    quote: new HtmlString('<p>Am '.$start->addDays($i - 1)->format('d.m.Y').' schrieb Kundenservice:</p><blockquote><p>Wir möchten gern prüfen …</p></blockquote>'),
                    attachments: [new TicketAttachment("test-{$i}.heic", 'image/heic', 1_904_118)],
                    channel: 'E-Mail',
                );
        }

        return new Ticket(
            number: '2137942',
            id: 51234,
            title: 'Zaubertasse – Motiv erscheint nicht',
            state: 'offen',
            closed: false,
            mergedIntoNumber: null,
            group: 'Kundenservice',
            customerName: 'Erika Beispiel',
            customerEmail: 'erika@example.org',
            createdAt: $start,
            articles: $articles,
            zammadUrl: 'https://zammad.example.org/#ticket/zoom/51234',
        );
    }
}

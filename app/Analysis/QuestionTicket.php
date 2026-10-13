<?php

namespace App\Analysis;

use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

/**
 * A question without a ticket as a ticket with one message, so the analysis and the knowledge
 * retrieval need no second path (PROJ-40). Built per request, never stored, no Zammad behind it.
 */
final class QuestionTicket
{
    public static function make(string $number, string $text, ?CarbonImmutable $at = null): Ticket
    {
        $at ??= CarbonImmutable::now();
        $articles = trim($text) === '' ? [] : [new TicketArticle(
            kind: ArticleKind::Customer,
            senderName: 'Mitarbeiter',
            createdAt: $at,
            body: new HtmlString('<p>'.nl2br(e($text), false).'</p>'),
        )];

        return new Ticket($number, 0, 'Frage', 'open', false, null, null, null, null, $at, $articles, '');
    }
}

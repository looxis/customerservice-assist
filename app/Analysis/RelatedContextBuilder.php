<?php

namespace App\Analysis;

use App\Zammad\ArticleKind;
use App\Zammad\RelatedTicket;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;

/**
 * Builds the section "Früherer Verlauf" from the chosen tickets (PROJ-52): every message with sender and date,
 * our replies included. Within the character budget the newest messages stay; what is left out is counted and
 * told, never cut off silently.
 */
class RelatedContextBuilder
{
    private const array SOURCES = [
        RelatedTicket::LINKED => 'in Zammad verknüpft',
        RelatedTicket::ORDER => 'gleiche Bestellnummer',
        RelatedTicket::CUSTOMER => 'gleicher Kunde',
        RelatedTicket::MANUAL => 'von Hand ergänzt',
    ];

    /** How many of the newest customer messages of a ticket the knowledge retrieval reads. */
    private const int RETRIEVAL_MESSAGES = 3;

    /**
     * @param  list<array{ticket: Ticket, source: string}>  $tickets
     */
    public function build(array $tickets, ?int $budget = null): RelatedSection
    {
        if ($tickets === []) {
            return new RelatedSection('', [], []);
        }

        $budget ??= (int) config('analysis.related_tickets.budget');
        $perTicket = intdiv($budget, count($tickets));
        $blocks = [];
        $meta = [];
        $notes = [];
        $customerTexts = [];

        foreach ($tickets as ['ticket' => $ticket, 'source' => $source]) {
            $customerTexts = [...$customerTexts, ...$this->customerTexts($ticket)];
            $messages = (new TicketContext($ticket))->everyMessage();
            $head = "## Ticket {$ticket->number} – {$ticket->title}";
            $kept = [];
            $used = mb_strlen($head) + 200;

            foreach (array_reverse($messages) as $message) {
                if ($kept !== [] && $used + mb_strlen($message) > $perTicket) {
                    break;
                }

                $kept[] = $message;
                $used += mb_strlen($message) + 2;
            }

            $kept = array_reverse($kept);
            $left = count($messages) - count($kept);
            $first = $ticket->articles === [] ? null : $ticket->articles[0]->createdAt;
            $last = $ticket->articles === [] ? null : $ticket->articles[array_key_last($ticket->articles)]->createdAt;
            $period = $first === null ? '' : '; '.$first->format('d.m.Y').($last !== null && $last->format('d.m.Y') !== $first->format('d.m.Y') ? '–'.$last->format('d.m.Y') : '');
            $label = self::SOURCES[$source] ?? $source;

            $blocks[] = "{$head} ({$ticket->state}{$period}; {$label})\n".($left > 0 ? "[{$left} ältere ".($left === 1 ? 'Nachricht' : 'Nachrichten')." dieses Tickets ausgelassen]\n\n" : '').implode("\n\n", $kept);
            $meta[] = ['number' => $ticket->number, 'title' => $ticket->title, 'state' => $ticket->state, 'source' => $source, 'articles' => count($messages), 'included' => count($kept), 'omitted' => $left, 'first' => $first?->toIso8601String(), 'last' => $last?->toIso8601String()];

            if ($left > 0) {
                $notes[] = "Aus Ticket {$ticket->number} wurden {$left} ältere ".($left === 1 ? 'Nachricht' : 'Nachrichten').' ausgelassen (zu lang für den Vorverlauf).';
            }
        }

        $text = "# Früherer Verlauf (zusammengehörige Tickets)\nDiese Tickets gehören zum selben Fall; die aktuelle Anfrage steht im Abschnitt „Ticket“.\n\n".implode("\n\n", $blocks);

        return new RelatedSection($text, $meta, ['Früherer Verlauf aus '.count($tickets).' '.(count($tickets) === 1 ? 'Ticket' : 'Tickets').' ist in den Vorschlag eingeflossen.', ...$notes], $customerTexts);
    }

    /**
     * The subject and the newest customer messages: the chosen tickets belong to the case, so what the
     * customer wrote there can call up knowledge even when the current message no longer names the concern.
     *
     * @return list<string>
     */
    private function customerTexts(Ticket $ticket): array
    {
        $messages = array_filter($ticket->articles, fn (TicketArticle $article): bool => $article->kind === ArticleKind::Customer && $article->hasText());
        $texts = array_map(fn (TicketArticle $article): string => TicketContext::plainText($article), array_slice(array_values($messages), -self::RETRIEVAL_MESSAGES));

        return array_values(array_filter([$ticket->title, ...$texts], fn (string $text): bool => trim($text) !== ''));
    }
}

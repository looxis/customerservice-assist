<?php

namespace App\Analysis;

use App\Zammad\RelatedTicket;
use App\Zammad\Ticket;

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

        foreach ($tickets as ['ticket' => $ticket, 'source' => $source]) {
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

        return new RelatedSection($text, $meta, ['Früherer Verlauf aus '.count($tickets).' '.(count($tickets) === 1 ? 'Ticket' : 'Tickets').' ist in den Vorschlag eingeflossen.', ...$notes]);
    }
}

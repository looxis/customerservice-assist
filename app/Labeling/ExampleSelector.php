<?php

namespace App\Labeling;

use App\Models\LabelTicket;

/**
 * Picks the examples for the prompt from the tickets admins decided on (PROJ-78): corrections first, then the
 * rest, newest first. Tickets of the test set, the references that already carried a tag and tickets marked unsure
 * are never examples.
 */
class ExampleSelector
{
    private const int MAX_EXAMPLE_LENGTH = 700;

    /**
     * Decisions whose meaning changed with labeling-4 (storno held address changes, sonstiges, then "keine", held
     * payments and invoices) and labeling-7 (bestellung held notes on an order placed already): made before this time
     * (UTC), they are no examples until an admin decides on the ticket again.
     */
    private const array REDEFINED_BEFORE = [
        'storno' => '2026-11-08 11:14:24',
        'sonstiges' => '2026-11-08 11:14:24',
        'bestellung' => '2026-11-10 10:15:16',
    ];

    /**
     * @return array{text: string, ids: list<int>}
     */
    public function forPrompt(int $perCategory): array
    {
        if ($perCategory < 1) {
            return ['text' => '', 'ids' => []];
        }

        $blocks = [];
        $ids = [];

        foreach (array_column(LabelCategory::cases(), 'value') as $category) {
            $tickets = LabelTicket::query()
                ->where('decision', $category)
                ->where('split', 'training')
                ->whereIn('decision_origin', ['zweifel', 'stichprobe', 'korrektur'])
                ->whereNotNull('text')
                ->when(isset(self::REDEFINED_BEFORE[$category]), fn ($query) => $query->where('decided_at', '>=', self::REDEFINED_BEFORE[$category]))
                ->with('latestPrediction')
                ->latest('decided_at')
                ->limit($perCategory * 4)
                ->get()
                ->sortByDesc(fn (LabelTicket $ticket): int => (int) ($ticket->latestPrediction?->category !== $category))
                ->take($perCategory);

            foreach ($tickets as $ticket) {
                $ids[] = $ticket->id;
                $blocks[] = '- Kategorie '.$category.': '.(filled($ticket->subject) ? 'Betreff "'.str_replace("\n", ' ', $ticket->subject).'", ' : '').'Text "'.str_replace("\n", ' ', mb_substr((string) $ticket->text, 0, self::MAX_EXAMPLE_LENGTH)).'"';
            }
        }

        return [
            'text' => $blocks === [] ? '' : "\n\nBeispiele, von Mitarbeitern geprüft (Orientierung, keine Regeln):\n".implode("\n", $blocks),
            'ids' => $ids,
        ];
    }
}

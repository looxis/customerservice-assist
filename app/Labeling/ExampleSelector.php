<?php

namespace App\Labeling;

use App\Models\LabelTicket;

/**
 * Picks the examples for the prompt from the tickets admins decided on (PROJ-78): corrections first, then the
 * rest, newest first. Tickets of the test set and the references that already carried a tag are never examples.
 */
class ExampleSelector
{
    private const int MAX_EXAMPLE_LENGTH = 700;

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

        foreach ([...array_column(LabelCategory::cases(), 'value'), LabelTicket::NONE] as $category) {
            $tickets = LabelTicket::query()
                ->where('decision', $category)
                ->where('split', 'training')
                ->whereIn('decision_origin', ['zweifel', 'stichprobe', 'korrektur'])
                ->whereNotNull('text')
                ->with('latestPrediction')
                ->latest('decided_at')
                ->limit($perCategory * 4)
                ->get()
                ->sortByDesc(fn (LabelTicket $ticket): int => (int) (($ticket->latestPrediction?->category ?? LabelTicket::NONE) !== $category))
                ->take($perCategory);

            foreach ($tickets as $ticket) {
                $ids[] = $ticket->id;
                $blocks[] = '- Kategorie '.$category.': "'.str_replace("\n", ' ', mb_substr((string) $ticket->text, 0, self::MAX_EXAMPLE_LENGTH)).'"';
            }
        }

        return [
            'text' => $blocks === [] ? '' : "\n\nBeispiele, von Mitarbeitern geprüft (Orientierung, keine Regeln):\n".implode("\n", $blocks),
            'ids' => $ids,
        ];
    }
}

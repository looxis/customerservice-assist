<?php

namespace App\Analysis;

use Illuminate\Support\Str;

/**
 * What the employee answered to an open step of a case (PROJ-39) or added as a follow-up to a
 * question (PROJ-40), put together from the stored analysis so the option text never comes from the request.
 */
final class Clarification
{
    /**
     * The earlier clarifications of the case plus the new one.
     *
     * @param  array<string, mixed>  $parent  The stored analysis that is continued.
     * @return list<array{question: string, answer: string, by: string, at: string}>
     */
    public static function decisions(array $parent, ?string $optionKey, string $note, string $by): array
    {
        $decision = $parent['result']['decision'] ?? null;
        $label = $optionKey === null || $optionKey === '' ? '' : (string) (array_column((array) ($decision['options'] ?? []), null, 'key')[$optionKey]['label'] ?? '');
        $note = trim($note);

        return [
            ...array_values((array) ($parent['decisions'] ?? [])),
            [
                'question' => $decision['question'] ?? (($parent['result']['next_step'] ?? 'reply') === 'internal_first'
                    ? 'Interne Prüfung: '.Str::limit((string) ($parent['result']['recommendation'] ?? ''), 200)
                    : 'Nachfrage des Mitarbeiters'),
                'answer' => $label !== '' && $note !== '' ? "{$label} ({$note})" : ($label !== '' ? $label : $note),
                'by' => $by,
                'at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Whether the option exists in the decision of the stored analysis.
     *
     * @param  array<string, mixed>  $parent
     */
    public static function hasOption(array $parent, string $optionKey): bool
    {
        return isset(array_column((array) ($parent['result']['decision']['options'] ?? []), null, 'key')[$optionKey]);
    }
}

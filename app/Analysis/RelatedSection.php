<?php

namespace App\Analysis;

/**
 * The section "Früherer Verlauf" of an analysis (PROJ-52): the text that goes to the model and what is known about
 * the tickets in it. Kept with the analysis, so a continuation uses the same data.
 */
final readonly class RelatedSection
{
    /**
     * @param  list<array<string, mixed>>  $tickets  Number, title, state, source, how many messages, how many left out, period.
     * @param  list<string>  $notes  Hints for the employee, shown with the result.
     */
    public function __construct(public string $text, public array $tickets, public array $notes) {}

    public function isEmpty(): bool
    {
        return $this->tickets === [];
    }

    /**
     * @return array{text: string, tickets: list<array<string, mixed>>, notes: list<string>}
     */
    public function toStored(): array
    {
        return ['text' => $this->text, 'tickets' => $this->tickets, 'notes' => $this->notes];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     */
    public static function fromStored(?array $stored): ?self
    {
        if ($stored === null || ! is_array($stored['tickets'] ?? null) || $stored['tickets'] === []) {
            return null;
        }

        return new self((string) ($stored['text'] ?? ''), array_values($stored['tickets']), array_values(array_map('strval', (array) ($stored['notes'] ?? []))));
    }
}

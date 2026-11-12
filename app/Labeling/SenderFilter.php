<?php

namespace App\Labeling;

use App\Models\LabelTicket;

/**
 * Keeps mails of automatic senders (marketplace notices, bounces) out of the ticket tagging (PROJ-78): they do not
 * reach the ticket system later on, so they are no training data.
 */
class SenderFilter
{
    public function __construct(private readonly LabelingSettings $settings) {}

    /**
     * The saved patterns: full addresses or parts such as "@amazon.it", separated by comma, semicolon or line break.
     * A pattern with a leading "!" is an exception: a sender it matches is never filtered.
     *
     * @return list<string>
     */
    public function patterns(): array
    {
        $parts = preg_split('/[\s,;]+/', mb_strtolower($this->settings->all()['excluded_senders'])) ?: [];

        return array_values(array_filter(array_map(trim(...), $parts)));
    }

    public function matches(?string $sender): bool
    {
        if (blank($sender)) {
            return false;
        }

        $sender = mb_strtolower($sender);
        $matched = false;

        foreach ($this->patterns() as $pattern) {
            if (str_starts_with($pattern, '!')) {
                if (str_contains($sender, substr($pattern, 1))) {
                    return false;
                }

                continue;
            }

            $matched = $matched || str_contains($sender, $pattern);
        }

        return $matched;
    }

    /**
     * Removes the tickets already captured from a listed sender. Tickets that carried a tag of the tagging before
     * the first retrieval stay untouched.
     *
     * @return int The number of tickets removed
     */
    public function removeCaptured(string $admin): int
    {
        $removed = 0;

        LabelTicket::query()
            ->whereNotNull('sender')
            ->where('state', '!=', LabelTicket::EXCLUDED)
            ->where('source', '!=', 'vorhanden')
            ->lazyById(100)
            ->each(function (LabelTicket $ticket) use ($admin, &$removed): void {
                if ($this->matches($ticket->sender)) {
                    $ticket->exclude($admin);
                    $removed++;
                }
            });

        return $removed;
    }
}

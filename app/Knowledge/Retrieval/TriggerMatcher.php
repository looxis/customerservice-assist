<?php

namespace App\Knowledge\Retrieval;

/**
 * Looks for the trigger words of a document in the customer texts (PROJ-36).
 * A word matches at the start of a word, ignoring case ("Glasstein" finds
 * "Glassteinen"), like the customer terms of PROJ-33. An exclusion word in the
 * same sentence suppresses the hit, but never in a question: what a customer
 * asks for outright must not be lost.
 */
class TriggerMatcher
{
    private const string QUESTION_START = '/^\W*(wie|was|wer|wo|wann|warum|wieso|weshalb|welche[mnrs]?|kann|können|könnte|könnten|darf|dürfen|ist|sind|wäre|wären|gibt|muss|müssen|soll|sollen|würde|würden|habe|haben|hat|geht|gehen)\b/iu';

    /**
     * @param  array{A: list<string>, B: list<string>, C: list<string>}  $tiers
     * @param  list<string>  $triggers
     * @param  list<string>  $excluded
     * @return array{hits: list<TriggerHit>, suppressed: list<array{trigger: string, tier: string, sentence: string, exclusion: string}>}
     */
    public function match(array $tiers, array $triggers, array $excluded): array
    {
        $hits = [];
        $suppressed = [];

        if ($triggers === []) {
            return ['hits' => [], 'suppressed' => []];
        }

        foreach (['A', 'B', 'C'] as $tier) {
            foreach ($tiers[$tier] as $text) {
                foreach ($this->sentences($text) as $sentence) {
                    foreach ($triggers as $trigger) {
                        if (! $this->contains($sentence, $trigger)) {
                            continue;
                        }

                        $exclusion = $this->isQuestion($sentence) ? null : $this->firstMatch($sentence, $excluded);

                        if ($exclusion !== null) {
                            $suppressed[] = ['trigger' => $trigger, 'tier' => $tier, 'sentence' => $this->shorten($sentence), 'exclusion' => $exclusion];

                            continue;
                        }

                        $hits[$tier.'|'.mb_strtolower($trigger)] ??= new TriggerHit($trigger, $tier, $this->shorten($sentence));
                    }
                }
            }
        }

        return ['hits' => array_values($hits), 'suppressed' => $suppressed];
    }

    /**
     * @return list<string>
     */
    public function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+|\R+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $part): bool => $part !== ''));
    }

    public function isQuestion(string $sentence): bool
    {
        return str_contains($sentence, '?') || preg_match(self::QUESTION_START, $sentence) === 1;
    }

    private function contains(string $text, string $word): bool
    {
        return preg_match('/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'/iu', $text) === 1;
    }

    /**
     * @param  list<string>  $words
     */
    private function firstMatch(string $text, array $words): ?string
    {
        foreach ($words as $word) {
            if ($this->contains($text, $word)) {
                return $word;
            }
        }

        return null;
    }

    private function shorten(string $sentence): string
    {
        return mb_strlen($sentence) > 300 ? mb_substr($sentence, 0, 297).'…' : $sentence;
    }
}

<?php

namespace App\Knowledge\Retrieval\Router;

use App\Knowledge\Retrieval\TriggerMatcher;

/**
 * Checks the evidence the router gives for a document whose hit an exclusion suppressed (PROJ-37).
 * The evidence must be a sentence or phrase of the customer text the router saw (original or translation,
 * contact data replaced as for the router) and must not be the suppressed sentence, nor its counterpart in the
 * other language of the same message. One independent sentence is enough, so a quote that spans the suppressed
 * sentence and a second concern still counts. A document the author excluded for one sentence stays open for another concern.
 */
class EvidenceCheck
{
    private const int MIN_LENGTH = 8;

    private const float OVERLAP = 0.6;

    public function __construct(private readonly TriggerMatcher $matcher) {}

    /**
     * @param  list<string>  $suppressedSentences  Suppressed sentences, contact data replaced like the router text.
     * @param  list<array{tier: string, original: string, translation: ?string}>  $segments  The router text.
     * @return string|null Null when the evidence stands; otherwise the reason it is rejected.
     */
    public function check(string $evidence, array $suppressedSentences, array $segments): ?string
    {
        $text = ' '.implode(' | ', array_map(fn (array $segment): string => $this->normalise($segment['original'].' '.($segment['translation'] ?? '')), $segments)).' ';
        $suppressed = $this->suppressed($suppressedSentences, $segments);
        $found = false;

        foreach ($this->matcher->sentences($evidence) as $sentence) {
            $normal = $this->normalise($sentence);

            if (mb_strlen($normal) < self::MIN_LENGTH || ! str_contains($text, $normal)) {
                continue;
            }

            $found = true;

            if (! $this->overlaps($normal, $suppressed)) {
                return null;
            }
        }

        return $found ? 'stützt sich nur auf den unterdrückten Satz' : 'Beleg kommt im Kundentext nicht vor';
    }

    /**
     * The suppressed sentences and, where original and translation have the same number of sentences, their
     * counterpart in the other language.
     *
     * @param  list<string>  $sentences
     * @param  list<array{tier: string, original: string, translation: ?string}>  $segments
     * @return list<string>
     */
    private function suppressed(array $sentences, array $segments): array
    {
        $normals = array_values(array_filter(array_map(fn (string $sentence): string => $this->normalise(rtrim($sentence, '… ')), $sentences), fn (string $value): bool => $value !== ''));
        $all = $normals;

        foreach ($segments as $segment) {
            if ($segment['translation'] === null) {
                continue;
            }

            $original = array_map(fn (string $item): string => $this->normalise($item), $this->matcher->sentences($segment['original']));
            $translation = array_map(fn (string $item): string => $this->normalise($item), $this->matcher->sentences($segment['translation']));

            if (count($original) !== count($translation)) {
                continue;
            }

            foreach ($normals as $normal) {
                foreach ([[$original, $translation], [$translation, $original]] as [$from, $to]) {
                    foreach ($from as $index => $candidate) {
                        if ($candidate !== '' && (str_contains($candidate, $normal) || str_contains($normal, $candidate))) {
                            $all[] = $to[$index];
                        }
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($all, fn (string $value): bool => $value !== '')));
    }

    /**
     * @param  list<string>  $suppressed
     */
    private function overlaps(string $evidence, array $suppressed): bool
    {
        $tokens = array_values(array_filter(explode(' ', $evidence), fn (string $word): bool => mb_strlen($word) >= 3));

        foreach ($suppressed as $sentence) {
            if (str_contains($sentence, $evidence) || str_contains($evidence, $sentence)) {
                return true;
            }

            if ($tokens !== []) {
                $own = array_flip(explode(' ', $sentence));
                $shared = count(array_filter($tokens, fn (string $word): bool => isset($own[$word])));

                if ($shared / count($tokens) >= self::OVERLAP) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalise(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}

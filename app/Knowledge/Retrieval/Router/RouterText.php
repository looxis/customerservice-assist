<?php

namespace App\Knowledge\Retrieval\Router;

use App\Analysis\Pseudonymizer;
use App\Knowledge\Retrieval\CaseFacts;

/**
 * The customer text the router gets (PROJ-37): the current concern (tier A) and the recent course (tier B),
 * original and German translation apart and labelled, with contact data replaced by the same placeholders as in
 * the analysis. No order data, no tier C. When the configured size is exceeded, tier B goes first, then the middle
 * of the longest current text is replaced by a visible mark; nothing is cut silently.
 */
final readonly class RouterText
{
    /**
     * @param  list<array{tier: string, original: string, translation: ?string}>  $segments  Text of the router, contact data replaced.
     * @param  array{b_removed: int, a_shortened: int}  $cut  Characters taken out of tier B and out of tier A.
     */
    public function __construct(
        public string $text,
        public array $segments,
        public array $cut,
    ) {}

    public static function build(CaseFacts $facts, Pseudonymizer $pseudonymizer, int $limit): self
    {
        $segments = [];

        foreach (['A', 'B'] as $tier) {
            foreach ($facts->parts[$tier] as [$original, $translation]) {
                $original = trim($pseudonymizer->apply((string) $original));
                $translation = $translation === null ? null : trim($pseudonymizer->apply($translation));

                if ($original === '' && ($translation ?? '') === '') {
                    continue;
                }

                $segments[] = ['tier' => $tier, 'original' => $original, 'translation' => $translation];
            }
        }

        $cut = ['b_removed' => 0, 'a_shortened' => 0];

        while (self::length($segments) > $limit && self::lastIndex($segments, 'B') !== null) {
            $index = self::lastIndex($segments, 'B');
            $cut['b_removed'] += self::size($segments[$index]);
            array_splice($segments, $index, 1);
        }

        $excess = self::length($segments) - $limit;

        if ($excess > 0) {
            $longest = null;

            foreach ($segments as $index => $segment) {
                if ($longest === null || self::size($segment) > self::size($segments[$longest])) {
                    $longest = $index;
                }
            }

            if ($longest !== null) {
                [$segments[$longest], $removed] = self::shorten($segments[$longest], $excess);
                $cut['a_shortened'] = $removed;
            }
        }

        return new self(self::render($segments), $segments, $cut);
    }

    /**
     * @param  list<array{tier: string, original: string, translation: ?string}>  $segments
     */
    private static function render(array $segments): string
    {
        $out = [];

        foreach (['A' => '## Aktuelles Anliegen', 'B' => '## Früherer Verlauf'] as $tier => $heading) {
            $items = array_values(array_filter($segments, fn (array $segment): bool => $segment['tier'] === $tier));

            if ($items === []) {
                continue;
            }

            $out[] = $heading;

            foreach ($items as $segment) {
                $out[] = ($segment['original'] !== '' ? "Original: {$segment['original']}" : '').($segment['translation'] !== null ? "\nÜbersetzung (Deutsch, nur Verständnishilfe): {$segment['translation']}" : '');
            }
        }

        return implode("\n\n", $out);
    }

    /**
     * @param  array{tier: string, original: string, translation: ?string}  $segment
     */
    private static function size(array $segment): int
    {
        return mb_strlen($segment['original']) + mb_strlen((string) $segment['translation']);
    }

    /**
     * @param  list<array{tier: string, original: string, translation: ?string}>  $segments
     */
    private static function length(array $segments): int
    {
        return mb_strlen(self::render($segments));
    }

    /**
     * @param  list<array{tier: string, original: string, translation: ?string}>  $segments
     */
    private static function lastIndex(array $segments, string $tier): ?int
    {
        $found = null;

        foreach ($segments as $index => $segment) {
            if ($segment['tier'] === $tier) {
                $found = $index;
            }
        }

        return $found;
    }

    /**
     * Keep the start and the end of the longest part and replace the middle by a mark.
     *
     * @param  array{tier: string, original: string, translation: ?string}  $segment
     * @return array{0: array{tier: string, original: string, translation: ?string}, 1: int}
     */
    private static function shorten(array $segment, int $excess): array
    {
        $field = $segment['translation'] !== null && mb_strlen($segment['translation']) > mb_strlen($segment['original']) ? 'translation' : 'original';
        $text = (string) $segment[$field];
        $mark = fn (int $removed): string => "\n[… {$removed} Zeichen ausgelassen …]\n";
        $removed = min(mb_strlen($text) - 40, $excess + mb_strlen($mark(99999)));

        if ($removed <= 0) {
            return [$segment, 0];
        }

        $keep = mb_strlen($text) - $removed;
        $head = (int) ceil($keep * 0.6);
        $segment[$field] = mb_substr($text, 0, $head).$mark($removed).mb_substr($text, mb_strlen($text) - ($keep - $head));

        return [$segment, $removed];
    }
}

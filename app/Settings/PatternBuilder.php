<?php

namespace App\Settings;

/**
 * Turns the plain texts of the settings into the patterns the mail cleaning uses. An entry is only ever
 * text: it is quoted, never taken as a pattern. German umlauts and "ß" also match their spelling
 * ("ue", "ss"), case does not matter, and "…" stands for any text.
 */
final class PatternBuilder
{
    public const string WILDCARD = '…';

    /**
     * A phrase as a pattern fragment: spaces match any whitespace, umlauts match both spellings.
     */
    public static function phrase(string $text): string
    {
        $parts = array_map(fn (string $word): string => self::word($word), preg_split('/\s+/u', trim($text)) ?: []);

        return implode('\s+', $parts);
    }

    /**
     * Fragments for a closing phrase list, each a whole line of its own ("Viele Grüße").
     *
     * @param  list<string>  $phrases
     * @return list<string>
     */
    public static function phrases(array $phrases): array
    {
        return array_values(array_map(fn (string $phrase): string => self::phrase($phrase), array_filter($phrases, fn (string $phrase): bool => trim($phrase) !== '')));
    }

    /**
     * Pattern for the start of a quoted earlier message from entries such as "Am … schrieb …:" or
     * "-- Original Message --"; matched at the start of a line.
     *
     * @param  list<string>  $entries
     */
    public static function quoteHeaders(array $entries): string
    {
        $alternatives = [];

        foreach ($entries as $entry) {
            if (trim($entry) !== '') {
                $alternatives[] = self::header($entry);
            }
        }

        if ($alternatives === []) {
            return '/(?!)/u';
        }

        return '/^\s*(?:'.implode('|', $alternatives).')/isu';
    }

    /**
     * Pattern for one whole line made of one of the phrases, with an optional trailing full stop, e.g. Amazon's
     * "Du hast eine Nachricht erhalten.".
     *
     * @param  list<string>  $phrases
     */
    public static function wholeLine(array $phrases, string $suffix = '\.?'): string
    {
        $fragments = self::phrases($phrases);

        return $fragments === [] ? '/(?!)/u' : '/^(?:'.implode('|', $fragments).')'.$suffix.'$/iu';
    }

    /**
     * Pattern for a label followed by a captured value, e.g. "Bestellnummer: 123-4567890-1234567".
     *
     * @param  list<string>  $labels
     */
    public static function labelWithValue(array $labels, string $value): string
    {
        $fragments = self::phrases($labels);

        return $fragments === [] ? '/(?!)/u' : '/^(?:'.implode('|', $fragments).')\s*:?\s*('.$value.')\s*:?$/iu';
    }

    /**
     * Pattern for a label that ends with a colon on a line of its own ("Nachricht:").
     *
     * @param  list<string>  $labels
     */
    public static function labelLine(array $labels): string
    {
        $fragments = self::phrases($labels);

        return $fragments === [] ? '/(?!)/u' : '/^(?:'.implode('|', $fragments).')\s*:$/iu';
    }

    /**
     * How many characters of an entry are fixed text (neither spaces nor the wildcard).
     */
    public static function fixedCharacters(string $entry): int
    {
        return mb_strlen(preg_replace('/[\s…]+/u', '', $entry) ?? '');
    }

    private static function header(string $entry): string
    {
        $tokens = preg_split('/(…|-{2,}|\s+)/u', trim($entry), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $pattern = '';

        foreach ($tokens as $index => $token) {
            $previous = $tokens[$index - 1] ?? null;
            $next = $tokens[$index + 1] ?? null;
            $isSpace = preg_match('/^\s+$/u', $token) === 1;
            $isOpen = static fn (?string $other): bool => $other !== null && ($other === self::WILDCARD || preg_match('/^-{2,}$/', $other) === 1 || str_starts_with($other, ':'));

            $pattern .= match (true) {
                $token === self::WILDCARD => '.{0,250}?',
                preg_match('/^-{2,}$/', $token) === 1 => '-{2,}',
                $isSpace => $isOpen($previous) || $isOpen($next) ? '\s*' : '\s+',
                default => self::word($token),
            };
        }

        return $pattern;
    }

    private static function word(string $word): string
    {
        $quoted = '';

        foreach (mb_str_split($word) as $character) {
            $quoted .= match (mb_strtolower($character)) {
                'ä' => '(?:ä|ae)',
                'ö' => '(?:ö|oe)',
                'ü' => '(?:ü|ue)',
                'ß' => '(?:ß|ss)',
                default => preg_quote($character, '/'),
            };
        }

        return $quoted;
    }
}

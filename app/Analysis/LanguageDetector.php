<?php

namespace App\Analysis;

/**
 * Tells without a language model whether a text is probably not German
 * (PROJ-28), by counting frequent German words against frequent words of the
 * languages our customers write in. Only for the hint "not in German"; the
 * exact language comes from the model when translating.
 */
class LanguageDetector
{
    private const array GERMAN = [
        'der', 'das', 'und', 'ich', 'nicht', 'ist', 'ein', 'eine', 'einen', 'mit', 'für', 'auf', 'wir', 'sie', 'habe', 'haben', 'hat', 'bitte', 'danke',
        'vielen', 'sehr', 'geehrte', 'hallo', 'guten', 'grüße', 'grüßen', 'freundlichen', 'bestellung', 'wurde', 'noch', 'auch', 'aber', 'oder', 'wenn',
        'dass', 'mir', 'mich', 'meine', 'ihre', 'ihnen', 'kann', 'können', 'sind', 'bei', 'von', 'zum', 'zur', 'nach', 'leider', 'schon', 'wie', 'uns', 'den', 'dem',
    ];

    private const array OTHER = [
        // English
        'the', 'you', 'your', 'have', 'please', 'thank', 'thanks', 'order', 'with', 'this', 'that', 'not', 'are', 'would', 'hello', 'regards', 'received', 'when', 'could',
        // French
        'le', 'les', 'et', 'je', 'vous', 'est', 'pas', 'une', 'pour', 'merci', 'bonjour', 'commande', 'mon', 'que', 'avec', 'nous', 'cordialement', 'reçu', 'mais', 'sur',
        // Italian
        'il', 'che', 'non', 'per', 'sono', 'grazie', 'buongiorno', 'ordine', 'mio', 'una', 'della', 'saluti', 'ho', 'ricevuto', 'ma', 'vorrei', 'con', 'gli',
        // Dutch
        'het', 'een', 'ik', 'niet', 'van', 'voor', 'bedankt', 'bestelling', 'mijn', 'maar', 'heb', 'geachte', 'groet', 'vriendelijke', 'graag', 'ontvangen', 'wij', 'zijn',
        // Spanish
        'el', 'los', 'por', 'gracias', 'pedido', 'hola', 'pero', 'recibido', 'saludos', 'quiero', 'tengo', 'está',
    ];

    /**
     * True when the text is probably not German, false when it probably is,
     * null when it cannot be told (too short, no words).
     */
    public function isForeign(string $text): ?bool
    {
        if (mb_strlen(trim($text)) < (int) config('analysis.translation.min_length')) {
            return null;
        }

        preg_match_all('/[\p{L}]{2,}/u', mb_strtolower($text), $matches);
        $words = array_count_values($matches[0]);

        $german = $this->count($words, self::GERMAN);
        $other = $this->count($words, self::OTHER);

        if ($german + $other < 2) {
            return null;
        }

        return $other > $german;
    }

    /**
     * @param  array<string, int>  $words
     * @param  list<string>  $list
     */
    private function count(array $words, array $list): int
    {
        return array_sum(array_intersect_key($words, array_flip($list)));
    }
}

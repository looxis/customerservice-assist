<?php

namespace App\Analysis;

/**
 * Checks that an automatic reply is in the language of the customer (PROJ-85, PROJ-82). The language model names the
 * language itself and can get it wrong, for example when an English message ends with a German signature such as
 * „Kundenservice“ (ticket 2140598). This check counts frequent function words: a clearly German reply to a message that
 * is clearly in another language is not sent.
 */
final class ReplyLanguage
{
    /** Short words that are frequent in German and rare in English, Dutch, French, Italian or Spanish. */
    private const array GERMAN = [
        'und', 'ist', 'nicht', 'ich', 'wir', 'sie', 'bitte', 'für', 'ein', 'eine', 'einen', 'einem', 'auf', 'dem', 'der', 'zu',
        'haben', 'habe', 'hat', 'wurde', 'wurden', 'wird', 'werden', 'mein', 'meine', 'ihre', 'ihren', 'ihnen', 'ihr', 'uns',
        'unser', 'unsere', 'sind', 'auch', 'aber', 'noch', 'nach', 'kann', 'können', 'leider', 'danke', 'vielen', 'dass', 'mit',
        'von', 'bei', 'sehr', 'schon', 'keine', 'kein', 'mich', 'oder', 'sich', 'diese', 'dieser', 'nur', 'jetzt', 'gerne',
    ];

    /** Short words that are frequent in English, French, Italian, Spanish or Dutch and rare in German. */
    private const array FOREIGN = [
        'the', 'and', 'is', 'are', 'were', 'not', 'you', 'your', 'we', 'our', 'please', 'with', 'for', 'of', 'to', 'have', 'has',
        'my', 'this', 'that', 'would', 'could', 'but', 'from', 'it', 'they', 'she', 'he', 'his', 'i', 'me', 'be', 'been', 'on',
        'at', 'if', 'thank', 'thanks', 'can', 'do', 'did', 'all', 'received',
        'le', 'la', 'les', 'et', 'est', 'pas', 'vous', 'nous', 'je', 'une', 'pour', 'avec', 'que', 'mon', 'ma', 'mes', 'merci',
        'sur', 'dans', 'bonjour', 'de',
        'il', 'di', 'che', 'non', 'sono', 'per', 'con', 'mi', 'ho', 'grazie', 'della', 'del', 'buongiorno', 'ciao',
        'el', 'los', 'las', 'y', 'es', 'por', 'gracias', 'hola', 'pero', 'para',
        'het', 'een', 'en', 'niet', 'ik', 'wij', 'mijn', 'voor', 'met', 'dank', 'bedankt', 'graag', 'zijn', 'ook', 'maar',
    ];

    /**
     * Why the reply is not in the customer's language, or null.
     *
     * @param  string  $customer  the customer's own text (without the subject line the app adds)
     * @param  string  $language  the language the model named, in German (e.g. „Englisch“)
     */
    public static function problem(string $customer, string $reply, string $replyDe, string $language): ?string
    {
        $language = mb_strtolower(trim($language));
        $saysGerman = $language === '' ? null : preg_match('/^(de\b|deutsch|german)/u', $language) === 1;

        if ($saysGerman === false && $replyDe !== '' && trim($reply) === trim($replyDe)) {
            return "Die Antwort ist nicht in der Sprache des Kunden ({$language}), sondern gleich der deutschen Fassung.";
        }

        [$customerGerman, $customerForeign] = self::count($customer);
        [$replyGerman, $replyForeign] = self::count($reply);

        if ($customerForeign >= 3 && $customerForeign > 2 * $customerGerman && $replyGerman >= 3 && $replyGerman > 2 * $replyForeign) {
            return 'Die Antwort ist deutsch, die Nachricht des Kunden aber nicht.';
        }

        return null;
    }

    /**
     * @return array{int, int} number of German and of foreign function words
     */
    private static function count(string $text): array
    {
        preg_match_all('/\p{L}+/u', mb_strtolower($text), $matches);
        $words = $matches[0];

        return [count(array_intersect($words, self::GERMAN)), count(array_intersect($words, self::FOREIGN))];
    }
}

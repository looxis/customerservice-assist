<?php

namespace App\Wismo;

/**
 * The last check before an automatic reply goes to a customer (PROJ-82). The text comes from a language model that has
 * read an untrusted customer message, so it may contain only what the app allows: the tracking links of the shipments,
 * no other link or address, no e-mail address, no amounts or vouchers, and a reasonable length.
 */
final class ReplyGuard
{
    public const int MAX_LENGTH = 3000;

    private const string URL = '#(?:https?://|www\.)[^\s<>"\']+#iu';

    private const string DOMAIN = '#\b[a-z0-9][a-z0-9-]*\.(?:com|de|net|org|info|io|eu|at|ch|ru|cn|xyz|top|shop|link|biz|me|co|uk|tk|ml|cc|site|online|click|live)\b#iu';

    private const string EMAIL = '#[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}#iu';

    private const string MONEY = '#\d+(?:[.,]\d+)?\s?(?:€|EUR\b|Euro\b)|(?:€|EUR\b)\s?\d#iu';

    private const string OFFER = '#\b(?:gutschein\w*|gutschrift\w*|rabatt\w*|gratis|kostenlos\w*|coupon\w*|voucher\w*|discount\w*|promo\w*)\b#iu';

    /**
     * Why the text must not be sent, or null.
     *
     * @param  list<string>  $allowedLinks  The tracking links of the shipments.
     */
    public static function violation(string $text, array $allowedLinks): ?string
    {
        if (mb_strlen($text) > self::MAX_LENGTH) {
            return 'Die Antwort ist ungewöhnlich lang.';
        }

        $rest = $text;

        foreach ($allowedLinks as $link) {
            $rest = str_replace($link, ' ', $rest);
        }

        if (preg_match(self::URL, $rest) === 1) {
            return 'Die Antwort enthält einen Link, der kein Trackinglink der Sendung ist.';
        }

        if (preg_match(self::EMAIL, $rest) === 1) {
            return 'Die Antwort enthält eine E-Mail-Adresse.';
        }

        if (preg_match(self::DOMAIN, $rest) === 1) {
            return 'Die Antwort nennt eine Internetadresse, die kein Trackinglink der Sendung ist.';
        }

        if (preg_match(self::MONEY, $rest) === 1) {
            return 'Die Antwort nennt einen Geldbetrag.';
        }

        if (preg_match(self::OFFER, $rest) === 1) {
            return 'Die Antwort nennt einen Gutschein, Rabatt oder eine Gutschrift.';
        }

        return null;
    }
}

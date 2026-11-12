<?php

namespace App\Zammad;

/**
 * The "Fall lösen" link of an Amazon buyer-message notice. Amazon keeps the message open in Seller Central until the
 * case is marked as solved there; the link is different in every notice. The mail text is already sanitized, so only
 * links to the trusted Seller Central hosts are still in it.
 */
final class AmazonCaseLink
{
    private const string LABELS = 'Fall lösen|Fall schließen|Resolve case|Close case|Risolvi il caso|Résoudre le cas|Resolver caso';

    /**
     * The link to resolve the case in the given message, null without one.
     */
    public static function in(?TicketArticle $article): ?string
    {
        if ($article === null) {
            return null;
        }

        $pattern = '/<a\b[^>]*\bhref\s*=\s*(["\'])(https:\/\/sellercentral\.amazon\.[a-z.]+\/[^"\']*)\1[^>]*>\s*(?:'.self::LABELS.')\s*<\/a>/iu';

        if (! preg_match($pattern, $article->body->toHtml(), $match)) {
            return null;
        }

        return html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

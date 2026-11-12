<?php

namespace App\ClaimReply;

use App\Wismo\ReplyGuard;

/**
 * The last check before the automatic first reply to a complaint goes to a customer (PROJ-85): the checks of the WISMO
 * reply without any allowed link, and no promise of replacement, refund or goodwill (the staff decides on that).
 */
final class ClaimReplyGuard
{
    private const string PROMISE = '#\b(?:ersatz\w*|erstattung\w*|rückerstattung\w*|kulan[zt]\w*|neulieferung\w*|nachlieferung\w*|neu\s+angefertigt|neu\s+produziert|geld\s+zurück|wandlung|refund\w*|replacement\w*|reimburs\w*|compensat\w*|goodwill|rimbors\w*|sostituzion\w*|remboursement\w*|remplacement\w*|reembols\w*|reemplazo\w*|vergoeding\w*|vervanging\w*)#iu';

    /**
     * Why the text must not be sent, or null.
     */
    public static function violation(string $text): ?string
    {
        $violation = ReplyGuard::violation($text, []);

        if ($violation !== null) {
            return $violation;
        }

        return preg_match(self::PROMISE, $text) === 1 ? 'Die Antwort spricht von Ersatz, Erstattung oder Kulanz.' : null;
    }
}

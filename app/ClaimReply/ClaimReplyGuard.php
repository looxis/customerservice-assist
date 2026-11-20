<?php

namespace App\ClaimReply;

use App\Wismo\ReplyGuard;

/**
 * The last check before the automatic first reply to a complaint goes to a customer (PROJ-85): the checks of the WISMO
 * reply without any allowed link, no promise of replacement, refund or goodwill (the staff decides on that), and no apology
 * or regret (the reply stays neutral and does not acknowledge the complaint).
 */
final class ClaimReplyGuard
{
    private const string PROMISE = '#\b(?:ersatz\w*|erstattung\w*|rückerstattung\w*|kulan[zt]\w*|neulieferung\w*|nachlieferung\w*|neu\s+angefertigt|neu\s+produziert|geld\s+zurück|wandlung|refund\w*|replacement\w*|reimburs\w*|compensat\w*|goodwill|rimbors\w*|sostituzion\w*|remboursement\w*|remplacement\w*|reembols\w*|reemplazo\w*|vergoeding\w*|vervanging\w*)#iu';

    /**
     * The reply stays neutral: no apology or regret, which would read like an acknowledgement of the complaint.
     */
    private const string APOLOGY = '#\b(?:tut\s+(?:uns|mir)\s+(?:sehr\s+|wirklich\s+)?leid|entschuldig\w*|bedauer\w*|sorry|apologi[sz]\w*|regret\w*|désolé\w*|excuse\w*|dispiac\w*|scus\w*|lo\s+sentimos|lament\w*|disculp\w*|spijt\w*|excuses)#iu';

    /**
     * Why the text must not be sent, or null.
     */
    public static function violation(string $text): ?string
    {
        $violation = ReplyGuard::violation($text, []);

        if ($violation !== null) {
            return $violation;
        }

        if (preg_match(self::PROMISE, $text) === 1) {
            return 'Die Antwort spricht von Ersatz, Erstattung oder Kulanz.';
        }

        return preg_match(self::APOLOGY, $text) === 1 ? 'Die Antwort entschuldigt sich oder bedauert und ist damit nicht neutral.' : null;
    }
}

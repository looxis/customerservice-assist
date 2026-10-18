<?php

namespace App\Zammad;

/**
 * Why a reply could not be handed to Zammad (PROJ-19). Never carries the token.
 */
enum ReplyProblem: string
{
    case NoToken = 'no_token';
    case TokenRejected = 'token_rejected';
    case Forbidden = 'forbidden';
    case NotFound = 'not_found';
    case Rejected = 'rejected';
    case Unavailable = 'unavailable';
    case Unclear = 'unclear';

    public function message(string $staff): string
    {
        return match ($this) {
            self::NoToken => "Für {$staff} ist kein Zammad-Token hinterlegt. Bitte Token hinterlegen, dann ist ein Direktversand möglich.",
            self::TokenRejected => "Zammad lehnt das Token von {$staff} ab (ungültig oder abgelaufen). Bitte ein neues Token hinterlegen. Die Antwort wurde nicht gesendet.",
            self::Forbidden => "Zammad erlaubt {$staff} das Antworten auf dieses Ticket nicht (fehlende Rechte oder Gruppe). Die Antwort wurde nicht gesendet.",
            self::NotFound => 'Das Ticket wurde in Zammad nicht gefunden (gelöscht oder zusammengeführt?). Die Antwort wurde nicht gesendet.',
            self::Rejected => 'Zammad hat die Antwort nicht angenommen. Die Antwort wurde nicht gesendet.',
            self::Unavailable => 'Zammad ist gerade nicht erreichbar. Die Antwort wurde nicht gesendet; bitte erneut versuchen.',
            self::Unclear => 'Zammad hat nicht rechtzeitig geantwortet. Es ist unklar, ob die Antwort gesendet wurde. Beim nächsten Versuch prüft die App zuerst das Ticket.',
        };
    }

    /**
     * Whether nothing was sent for sure, so a new attempt is safe.
     */
    public function notSent(): bool
    {
        return $this !== self::Unclear;
    }
}

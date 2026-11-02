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
     * The message when closing a ticket without a reply failed (PROJ-68).
     */
    public function closeMessage(string $staff): string
    {
        return match ($this) {
            self::NoToken => "Für {$staff} ist kein Zammad-Token hinterlegt. Ohne Token kann die App das Ticket nicht schließen.",
            self::TokenRejected => "Zammad lehnt das Token von {$staff} ab (ungültig oder abgelaufen). Das Ticket wurde nicht geschlossen.",
            self::Forbidden => "Zammad erlaubt {$staff} das Schließen dieses Tickets nicht (fehlende Rechte oder Gruppe). Das Ticket wurde nicht geschlossen.",
            self::NotFound => 'Das Ticket wurde in Zammad nicht gefunden (gelöscht oder zusammengeführt?). Es wurde nichts geändert.',
            self::Rejected => 'Zammad hat die Änderung nicht angenommen. Das Ticket wurde nicht geschlossen.',
            self::Unavailable => 'Zammad ist gerade nicht erreichbar. Das Ticket wurde nicht geschlossen; bitte erneut versuchen.',
            self::Unclear => 'Zammad hat nicht rechtzeitig geantwortet. Es ist unklar, ob das Ticket geschlossen wurde; bitte „Aktualisieren“ und den Status prüfen.',
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

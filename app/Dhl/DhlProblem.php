<?php

namespace App\Dhl;

/**
 * Why a call to DHL (PROJ-64) did not give a usable result. Never carries credentials, addresses or label data.
 */
enum DhlProblem: string
{
    case Misconfigured = 'misconfigured';
    case Unauthorized = 'unauthorized';
    case Forbidden = 'forbidden';
    case Rejected = 'rejected';
    case RateLimited = 'rate_limited';
    case Unavailable = 'unavailable';
    case OutcomeUnknown = 'outcome_unknown';
    case InvalidLabel = 'invalid_label';
    case UnexpectedResponse = 'unexpected_response';

    public function message(): string
    {
        return match ($this) {
            self::Misconfigured => 'Die DHL-Anbindung ist nicht vollständig eingerichtet (Zugangsdaten oder Empfänger-ID fehlen). Es wurde kein Label erzeugt.',
            self::Unauthorized => 'DHL lehnt die Anmeldung ab (Zugangsdaten falsch oder abgelaufen). Es wurde kein Label erzeugt.',
            self::Forbidden => 'DHL erlaubt diese Aktion nicht (fehlende Berechtigung für Retouren oder den Empfänger). Es wurde kein Label erzeugt.',
            self::Rejected => 'DHL hat die Angaben nicht angenommen. Bitte die Adresse prüfen und korrigieren. Es wurde kein Label erzeugt.',
            self::RateLimited => 'DHL hat zu viele Anfragen erhalten. Bitte kurz warten und erneut versuchen. Es wurde kein Label erzeugt.',
            self::Unavailable => 'DHL ist gerade nicht erreichbar. Es wurde kein Label erzeugt; bitte erneut versuchen.',
            self::OutcomeUnknown => 'DHL hat nicht eindeutig geantwortet. Es ist unklar, ob ein Label angelegt wurde. Bitte im Geschäftskundenportal nachsehen, bevor ein weiteres Label erzeugt wird.',
            self::InvalidLabel => 'DHL hat das Label angelegt, aber die Datei ist unbrauchbar. Die Sendungsnummer ist gespeichert; bitte im Geschäftskundenportal nachsehen.',
            self::UnexpectedResponse => 'DHL hat eine unerwartete Antwort geliefert. Es ist unklar, ob ein Label angelegt wurde. Bitte im Geschäftskundenportal nachsehen.',
        };
    }

    /**
     * Whether DHL certainly did not create a label, so a new attempt is safe.
     */
    public function notCreated(): bool
    {
        return ! in_array($this, [self::OutcomeUnknown, self::InvalidLabel, self::UnexpectedResponse], true);
    }
}

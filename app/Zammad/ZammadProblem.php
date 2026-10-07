<?php

namespace App\Zammad;

enum ZammadProblem: string
{
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case Unavailable = 'unavailable';
    case Misconfigured = 'misconfigured';

    /**
     * The message when a ticket address from Zammad was given instead of a number.
     */
    public function messageForAddress(): string
    {
        return match ($this) {
            self::NotFound => 'Zu dieser Adresse gibt es in Zammad kein Ticket. Bitte die Adresse prüfen.',
            self::Forbidden => 'Auf dieses Ticket hat die App in Zammad keinen Zugriff. Bitte wende dich an den Entwickler.',
            default => $this->message(''),
        };
    }

    public function message(string $number): string
    {
        return match ($this) {
            self::NotFound => "Ticket#{$number} wurde in Zammad nicht gefunden. Bitte die Nummer prüfen. Ist das Ticket ganz neu, findet die Suche von Zammad es manchmal noch nicht – dann stattdessen die Adresse des Tickets aus der Browserzeile von Zammad einfügen.",
            self::Forbidden => "Auf Ticket#{$number} hat die App in Zammad keinen Zugriff. Bitte wende dich an den Entwickler.",
            self::Unavailable => 'Zammad ist gerade nicht erreichbar. Bitte in einer Minute erneut versuchen.',
            self::Misconfigured => 'Die Verbindung zu Zammad ist nicht eingerichtet oder ungültig. Bitte wende dich an den Entwickler.',
        };
    }

    public function status(): int
    {
        return match ($this) {
            self::NotFound => 404,
            self::Forbidden => 403,
            self::Unavailable, self::Misconfigured => 503,
        };
    }

    public function canRetry(): bool
    {
        return $this === self::Unavailable;
    }
}

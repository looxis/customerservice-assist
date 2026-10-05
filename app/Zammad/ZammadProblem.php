<?php

namespace App\Zammad;

enum ZammadProblem: string
{
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case Unavailable = 'unavailable';
    case Misconfigured = 'misconfigured';

    public function message(string $number): string
    {
        return match ($this) {
            self::NotFound => "Ticket#{$number} wurde in Zammad nicht gefunden. Bitte die Nummer prüfen.",
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

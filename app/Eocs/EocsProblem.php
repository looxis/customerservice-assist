<?php

namespace App\Eocs;

enum EocsProblem: string
{
    case Unavailable = 'unavailable';
    case Misconfigured = 'misconfigured';

    public function message(): string
    {
        return match ($this) {
            self::Unavailable => 'EOCS ist gerade nicht erreichbar. Bitte erneut versuchen.',
            self::Misconfigured => 'Die Verbindung zu EOCS ist nicht eingerichtet oder ungültig. Bitte wende dich an den Entwickler.',
        };
    }

    public function canRetry(): bool
    {
        return $this === self::Unavailable;
    }
}

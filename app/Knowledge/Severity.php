<?php

namespace App\Knowledge;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';

    public function label(): string
    {
        return match ($this) {
            self::Error => 'Fehler',
            self::Warning => 'Warnung',
        };
    }
}

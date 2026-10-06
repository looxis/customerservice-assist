<?php

namespace App\Analysis;

enum AnalysisProblem: string
{
    case Unavailable = 'unavailable';
    case Misconfigured = 'misconfigured';
    case InvalidResult = 'invalid_result';

    public function message(): string
    {
        return match ($this) {
            self::Unavailable => 'Die Analyse ist gerade nicht möglich. Bitte erneut versuchen.',
            self::Misconfigured => 'Die Verbindung zum Sprachmodell ist nicht eingerichtet oder ungültig. Bitte wende dich an den Entwickler.',
            self::InvalidResult => 'Das Sprachmodell hat kein verwertbares Ergebnis geliefert. Bitte erneut versuchen.',
        };
    }
}

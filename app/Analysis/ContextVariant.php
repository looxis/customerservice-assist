<?php

namespace App\Analysis;

/**
 * Which part of the ticket goes to the language model.
 */
enum ContextVariant: string
{
    case FullThread = 'verlauf';
    case LastMessage = 'letzte';
    case LastWithSummary = 'zusammenfassung';

    public function label(): string
    {
        return match ($this) {
            self::FullThread => 'Ganzer Verlauf',
            self::LastMessage => 'Nur letzte Kundennachricht',
            self::LastWithSummary => 'Letzte Kundennachricht + Zusammenfassung',
        };
    }
}

<?php

namespace App\Analysis;

/**
 * How far CSA got with the current customer message of a tracked ticket, apart
 * from the ticket state in Zammad (PROJ-66). Stored as a string, so the later
 * states (analyzing, draft_ready, approval_required, internal_review, auto_done)
 * are added here when their features arrive, without a migration.
 */
enum ProcessingState: string
{
    case NotAnalyzed = 'not_analyzed';
    case Analyzed = 'analyzed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NotAnalyzed => 'Noch nicht analysiert',
            self::Analyzed => 'Vorschlag vorhanden',
            self::Failed => 'Analyse fehlgeschlagen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotAnalyzed => 'neutral',
            self::Analyzed => 'success',
            self::Failed => 'danger',
        };
    }
}

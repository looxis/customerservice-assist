<?php

namespace App\Models;

use Database\Factories\TicketSummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The summary of a ticket's earlier thread, per ticket or test-run cut point (PROJ-9, PROJ-11).
 */
#[Fillable(['scope_key', 'ticket_number', 'content'])]
class TicketSummary extends Model
{
    /** @use HasFactory<TicketSummaryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'encrypted:array',
        ];
    }
}

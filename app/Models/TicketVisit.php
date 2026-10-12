<?php

namespace App\Models;

use Database\Factories\TicketVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A ticket that was opened in the app (PROJ-35), one row per ticket. Customer
 * name and subject are encrypted; the row is dropped after the retention period.
 */
#[Fillable(['ticket_number', 'customer_name', 'title', 'first_opened_at', 'first_opened_by', 'last_opened_at', 'last_opened_by', 'opened_count'])]
class TicketVisit extends Model
{
    /** @use HasFactory<TicketVisitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_name' => 'encrypted',
            'title' => 'encrypted',
            'first_opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'opened_count' => 'integer',
        ];
    }
}

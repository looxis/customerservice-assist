<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Order numbers typed in by hand and found in EOCS, remembered for a ticket (PROJ-7).
 */
#[Fillable(['ticket_number', 'orders', 'staff_name'])]
class TicketOrderMemory extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orders' => 'array',
        ];
    }
}

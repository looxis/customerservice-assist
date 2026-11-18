<?php

namespace App\Models;

use App\Tickets\TicketLocks;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The lock of a ticket by the person working on it (PROJ-96). Free once the last activity is older than
 * {@see TicketLocks::TTL_MINUTES}.
 */
#[Fillable(['ticket_number', 'staff_name', 'last_activity_at'])]
class TicketLock extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }
}

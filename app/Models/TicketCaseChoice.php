<?php

namespace App\Models;

use Database\Factories\TicketCaseChoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer group and products last chosen for a ticket (PROJ-9, PROJ-11).
 */
#[Fillable(['ticket_number', 'customer_group', 'products', 'staff_name'])]
class TicketCaseChoice extends Model
{
    /** @use HasFactory<TicketCaseChoiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'products' => 'array',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One automatic check of a ToDo for the tracking number (PROJ-77): when, and what came out of it.
 */
#[Fillable(['ticket_todo_id', 'checked_at', 'result', 'note'])]
class TodoAutoCheck extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<TicketTodo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(TicketTodo::class, 'ticket_todo_id');
    }
}

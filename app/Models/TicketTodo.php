<?php

namespace App\Models;

use App\Todo\TodoKind;
use App\Todo\TodoStatus;
use Database\Factories\TicketTodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An internal follow-up task tied to a Zammad ticket (PROJ-70). It lives on its own: closing the ticket or dropping it
 * from the ticket list leaves the ToDo untouched. The text may contain customer data and is stored encrypted.
 */
#[Fillable(['zammad_id', 'ticket_number', 'description', 'assignee', 'due_on', 'kind', 'waiting_for', 'status', 'notify_customer', 'notify_hint', 'notify_analysis_uuid', 'notify_skipped', 'source', 'source_analysis_uuid', 'external_ref', 'idempotency_key', 'created_by', 'updated_by', 'completed_by', 'completed_at'])]
class TicketTodo extends Model
{
    /** @use HasFactory<TicketTodoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'description' => 'encrypted',
            'notify_hint' => 'encrypted',
            'due_on' => 'date',
            'kind' => TodoKind::class,
            'status' => TodoStatus::class,
            'notify_customer' => 'boolean',
            'notify_skipped' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * ToDos that still need someone: open ones and those waiting for the customer message.
     *
     * @param  Builder<TicketTodo>  $query
     */
    public function scopeUnfinished(Builder $query): void
    {
        $query->whereIn('status', [TodoStatus::Open->value, TodoStatus::NotifyPending->value]);
    }

    /**
     * @param  Builder<TicketTodo>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->unfinished()->whereNotNull('due_on')->whereDate('due_on', '<', now('Europe/Berlin')->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->status !== TodoStatus::Done && $this->due_on !== null && $this->due_on->toDateString() < now('Europe/Berlin')->toDateString();
    }
}

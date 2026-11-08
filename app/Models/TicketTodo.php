<?php

namespace App\Models;

use App\Todo\TodoKind;
use App\Todo\TodoStatus;
use App\Todo\TrackingSchedule;
use Database\Factories\TicketTodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An internal follow-up task tied to a Zammad ticket (PROJ-70). It lives on its own: closing the ticket or dropping it
 * from the ticket list leaves the ToDo untouched. The text may contain customer data and is stored encrypted.
 */
#[Fillable(['zammad_id', 'ticket_number', 'description', 'assignee', 'due_on', 'kind', 'waiting_for', 'status', 'notify_customer', 'notify_hint', 'notify_analysis_uuid', 'notify_skipped', 'source', 'source_analysis_uuid', 'external_ref', 'idempotency_key', 'created_by', 'updated_by', 'completed_by', 'completed_at', 'auto_send', 'customer_language', 'auto_state', 'auto_reason', 'auto_checks', 'auto_errors', 'auto_last_check_at', 'auto_sending_at', 'auto_sent_at', 'auto_tracking', 'auto_text'])]
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
            'auto_send' => 'boolean',
            'auto_tracking' => 'encrypted',
            'auto_text' => 'encrypted',
            'auto_last_check_at' => 'datetime',
            'auto_sending_at' => 'datetime',
            'auto_sent_at' => 'datetime',
        ];
    }

    /**
     * The checks are small and shown with the ToDo.
     *
     * @var list<string>
     */
    protected $with = ['autoChecks'];

    /**
     * @return HasMany<TodoAutoCheck, $this>
     */
    public function autoChecks(): HasMany
    {
        return $this->hasMany(TodoAutoCheck::class)->orderByDesc('checked_at')->orderByDesc('id');
    }

    /**
     * When the next automatic check is due, for the list ("heute 18:34"); null when none follows.
     */
    protected function autoNextLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->auto_state === 'watching' ? TrackingSchedule::label($this) : null);
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
     * ToDos that people work on, without those the app handles itself (automatic sending of the tracking number).
     *
     * @param  Builder<TicketTodo>  $query
     */
    public function scopeManual(Builder $query): void
    {
        $query->where('auto_send', false);
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

<?php

namespace App\Todo;

use App\Analysis\AnalysisStore;
use App\Models\TicketTodo;
use App\Zammad\ArticleKind;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Creates, changes and finishes ticket ToDos (PROJ-70). Finishing is an atomic step on the row, so two employees
 * finishing the same ToDo never produce two results.
 */
class TodoService
{
    public function __construct(private readonly AnalysisStore $store) {}

    /**
     * The due date for "in 1, 3 or 7 days"; none for anything else.
     */
    public static function dueDate(mixed $days): ?string
    {
        return in_array((string) $days, ['1', '3', '7'], true) ? now('Europe/Berlin')->addDays((int) $days)->toDateString() : null;
    }

    /**
     * Whether the tracking number is sent by the app (PROJ-77): wanted, a number to look for and a customer who
     * writes German. The state "watching" starts the checks.
     *
     * @param  array<string, mixed>  $fields
     * @return array{auto_send: bool, auto_state: string|null}
     */
    private function automation(array $fields, ?string $language, string $reference): array
    {
        $on = (bool) ($fields['auto_send'] ?? false) && $reference !== '' && str_starts_with(mb_strtolower(trim((string) $language)), 'deutsch');

        return ['auto_send' => $on, 'auto_state' => $on ? 'watching' : null];
    }

    /**
     * @param  array<string, mixed>  $fields  Validated by TodoRules.
     * @return array<string, mixed>
     */
    private function attributes(array $fields, string $by): array
    {
        $waiting = ($fields['kind'] ?? null) === TodoKind::Waiting->value;
        $notify = (bool) ($fields['notify_customer'] ?? false);
        $hint = trim((string) ($fields['notify_hint'] ?? ''));

        return [
            'description' => trim((string) $fields['description']),
            'assignee' => ($fields['assignee'] ?? null) ?: $by,
            'kind' => $fields['kind'],
            'waiting_for' => $waiting ? trim((string) ($fields['waiting_for'] ?? '')) : null,
            'notify_customer' => $notify,
            'notify_hint' => $notify && $hint !== '' ? $hint : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function create(int $zammadId, string $number, array $fields, string $by, ?string $key = null, string $source = 'manual', ?string $analysis = null, ?string $language = null): TicketTodo
    {
        $reference = trim((string) ($fields['reference'] ?? ''));

        try {
            return TicketTodo::query()->create([
                ...$this->attributes($fields, $by),
                'zammad_id' => $zammadId,
                'ticket_number' => $number,
                'due_on' => self::dueDate($fields['due_in'] ?? null),
                'external_ref' => $reference ?: null,
                'customer_language' => $language,
                ...$this->automation($fields, $language, $reference),
                'status' => TodoStatus::Open,
                'source' => $source,
                'source_analysis_uuid' => $analysis,
                'idempotency_key' => $key,
                'created_by' => $by,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            return TicketTodo::query()->where('idempotency_key', $key)->firstOr(fn () => throw $exception);
        }
    }

    /**
     * The ToDos of a sent reply, each at most once however often the request is repeated.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function createFromReply(int $zammadId, string $number, array $rows, string $analysis, string $by): int
    {
        $language = $this->store->result($analysis)['result']['reply']['language'] ?? null;

        foreach (array_values($rows) as $index => $row) {
            $this->create($zammadId, $number, $row, $by, "reply:{$analysis}:{$index}", 'reply', $analysis, is_string($language) ? mb_substr($language, 0, 40) : null);
        }

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function update(TicketTodo $todo, array $fields, string $by): void
    {
        $changes = [...$this->attributes($fields, $by), 'updated_by' => $by];

        // No name chosen: the assignee stays as it is, it is not silently handed to the person who edits.
        if (blank($fields['assignee'] ?? null)) {
            unset($changes['assignee']);
        }
        if (array_key_exists('due_in', $fields) && $fields['due_in'] !== 'keep') {
            $changes['due_on'] = self::dueDate($fields['due_in']);
        }

        if (array_key_exists('reference', $fields) || array_key_exists('auto_send', $fields)) {
            $reference = trim((string) ($fields['reference'] ?? ''));
            $changes['external_ref'] = $reference ?: null;
            $changes = [...$changes, ...$this->automationOnEdit($todo, $fields, $reference)];
        }

        $todo->update($changes);
    }

    /**
     * Changing the switch or the number of a ToDo whose automation has not ended starts or stops the checks.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function automationOnEdit(TicketTodo $todo, array $fields, string $reference): array
    {
        if (in_array($todo->auto_state, ['sent', 'sending'], true)) {
            return [];
        }

        $automation = $this->automation($fields, $todo->customer_language, $reference);

        return $automation['auto_send'] === $todo->auto_send ? [] : [...$automation, 'auto_reason' => null, 'auto_errors' => 0];
    }

    /**
     * Finish a ToDo that needs no message to the customer, or whose message is skipped.
     *
     * @return bool False when someone else finished it first.
     */
    public function finish(TicketTodo $todo, string $by, bool $skipNotification = false): bool
    {
        return TicketTodo::query()->whereKey($todo->id)->unfinished()->update([
            'status' => TodoStatus::Done->value,
            'completed_by' => $by,
            'completed_at' => now(),
            'notify_skipped' => $skipNotification && $todo->notify_customer,
            'updated_at' => now(),
        ]) === 1;
    }

    /**
     * Take the ToDo to "Benachrichtigung offen"; only one caller wins.
     */
    public function claimNotification(TicketTodo $todo, string $by): bool
    {
        return TicketTodo::query()->whereKey($todo->id)->where('status', TodoStatus::Open->value)->update([
            'status' => TodoStatus::NotifyPending->value,
            'completed_by' => $by,
            'completed_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    public function releaseNotification(TicketTodo $todo): void
    {
        TicketTodo::query()->whereKey($todo->id)->where('status', TodoStatus::NotifyPending->value)->whereNull('notify_analysis_uuid')->update([
            'status' => TodoStatus::Open->value, 'completed_by' => null, 'completed_at' => null,
        ]);
    }

    /**
     * Prepare the reply for the customer: an empty draft of the ticket with the hint of the ToDo as its text.
     *
     * @throws ZammadException
     */
    public function prepareNotification(TicketTodo $todo, string $by, ZammadClient $zammad, AnalysisStore $store): string
    {
        $ticket = $zammad->ticket($todo->ticket_number);
        $newest = collect($ticket->articles)->reject(fn ($article): bool => $article->kind === ArticleKind::Internal)->last()?->id;
        $id = $store->putManualReply($todo->ticket_number, $ticket->summaryKey(), $by, $newest, $ticket->lastCustomerArticleId());

        if ($todo->notify_hint !== null) {
            $store->changeResult($id, fn (array $content): array => [...$content, 'reply_edit' => ['text' => $todo->notify_hint, 'staff' => $by, 'at' => CarbonImmutable::now()->toIso8601String()]]);
        }

        $todo->update(['notify_analysis_uuid' => $id]);

        return $id;
    }

    /**
     * Whether the prepared reply can still be sent: it exists, is the newest proposal of the ticket and not yet sent.
     */
    public function draftUsable(TicketTodo $todo, AnalysisStore $store): bool
    {
        $stored = $todo->notify_analysis_uuid === null ? null : $store->result($todo->notify_analysis_uuid);

        return $stored !== null && $store->latest((string) $stored['scope']) === $todo->notify_analysis_uuid
            && ($store->replySending($todo->notify_analysis_uuid)['sent_at'] ?? null) === null;
    }

    /**
     * The reply to the customer went out: the ToDos waiting for it are now completely done.
     */
    public function markNotified(string $analysis): void
    {
        TicketTodo::query()->where('notify_analysis_uuid', $analysis)->where('status', TodoStatus::NotifyPending->value)->update([
            'status' => TodoStatus::Done->value, 'updated_at' => now(),
        ]);
    }
}

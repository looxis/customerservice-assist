<?php

namespace App\Zammad;

use App\Analysis\ProcessingState;
use App\Analysis\TicketProcessingState;
use App\Jobs\ClassifyIncomingTicket;
use App\Models\LabelTicket;
use App\Models\TrackedTicket;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Brings one tracked ticket up to the current state in Zammad (PROJ-66). Every
 * trigger only says "this ticket changed"; the data is always read fresh, so
 * repeated or reordered triggers do no harm. Only reads from Zammad, never writes.
 */
class TicketSynchronizer
{
    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly TicketProcessingState $processing,
    ) {}

    /**
     * @param  string|null  $listedUpdatedAt  The change time seen in a list; an up-to-date ticket then needs no call to Zammad.
     * @param  bool  $readArticles  Read the thread even when the contact times have not changed.
     *
     * @throws ZammadException On a temporary problem; 403 and 404 are stored as the ticket's status instead.
     * @throws LockTimeoutException While another path holds this ticket.
     */
    public function sync(int $zammadId, ?string $listedUpdatedAt = null, bool $readArticles = false): void
    {
        Cache::lock("zammad-sync.{$zammadId}", 120)->block(
            (int) config('services.zammad.sync_lock_wait'),
            fn () => $this->syncLocked($zammadId, $listedUpdatedAt, $readArticles),
        );
    }

    private function syncLocked(int $zammadId, ?string $listedUpdatedAt, bool $readArticles): void
    {
        $row = TrackedTicket::query()->where('zammad_id', $zammadId)->first();

        if ($row !== null && $listedUpdatedAt !== null && ! $readArticles && self::isCurrent($row, CarbonImmutable::parse($listedUpdatedAt))) {
            return;
        }

        try {
            $head = $this->zammad->ticketHead($zammadId);
        } catch (ZammadException $exception) {
            $status = match ($exception->problem) {
                ZammadProblem::NotFound => SyncStatus::NotFound,
                ZammadProblem::Forbidden => SyncStatus::AccessDenied,
                default => throw $exception,
            };

            $row?->update(['sync_status' => $status, 'sync_problem_since' => $row->sync_problem_since ?? now()]);

            return;
        }

        if (in_array($head->group, $this->zammad->excludedGroups(), true)) {
            return;
        }

        if ($row !== null && $head->updatedAt->lessThan($row->zammad_updated_at) && ! $readArticles) {
            $row->update(['sync_status' => SyncStatus::Synced, 'sync_problem_since' => null, 'synced_at' => now()]);

            return;
        }

        $this->write($row, $head, $readArticles);
    }

    private function write(?TrackedTicket $row, TicketHead $head, bool $readArticles): void
    {
        $attributes = [
            'zammad_id' => $head->id,
            'number' => $head->number,
            'title' => $head->title,
            'state' => $head->state,
            'is_closed' => $head->closed,
            'pending_until' => $head->pendingUntil,
            'group_name' => $head->group,
            'owner_zammad_id' => $head->ownerId,
            'owner_name' => $this->ownerName($row, $head),
            'customer_zammad_id' => $head->customerId,
            'organization_zammad_id' => $head->organizationId,
            'zammad_created_at' => $head->createdAt,
            'zammad_updated_at' => $head->updatedAt,
            'last_activity_at' => $head->lastActivityAt,
            'last_contact_customer_at' => $head->lastContactCustomerAt,
            'sync_status' => SyncStatus::Synced,
            'sync_problem_since' => null,
            'synced_at' => now(),
        ];

        if ($row?->is_closed && ! $head->closed) {
            $attributes['reopened_at'] = now();
        }

        if ($row === null || $readArticles || self::contactChanged($row, $head)) {
            $attributes += $this->currentMessage($row, $head);
        }

        if ($row !== null) {
            $row->update($attributes);

            return;
        }

        TrackedTicket::query()->create($attributes);
        $this->label($head, $attributes['last_customer_article_id'] ?? null);
    }

    /**
     * A ticket CSA sees for the first time (PROJ-80): its category from the ticket tagging goes into the list; a new
     * ticket in which the customer has written is classified. Older tickets, imported or reconciled, are not.
     */
    private function label(TicketHead $head, ?int $customerArticleId): void
    {
        $labelTicket = LabelTicket::query()->where('zammad_id', $head->id)->first();

        if ($labelTicket !== null) {
            $labelTicket->shareCategory();

            return;
        }

        if ($customerArticleId !== null && $head->createdAt?->greaterThanOrEqualTo(now()->subHours((int) config('labeling.intake_max_age_hours')))) {
            ClassifyIncomingTicket::dispatch($head->id);
        }
    }

    /**
     * The current customer message and, when it is another one than before, its state.
     *
     * @return array<string, mixed>
     */
    private function currentMessage(?TrackedTicket $row, TicketHead $head): array
    {
        $article = $this->zammad->lastCustomerArticle($head->id, $head->customerId);
        $articleAt = $article?->createdAt->setTimezone(config('app.timezone'));
        $message = ['last_customer_article_id' => $article?->id, 'last_customer_article_at' => $articleAt];

        if ($row !== null && $row->last_customer_article_id === $article?->id) {
            return $message;
        }

        $state = $row === null ? $this->processing->initial($head->number, $article?->id, $articleAt) : $this->processing->resolve($head->number, $article?->id);

        return [...$message, 'processing_state' => $state] + ($state === ProcessingState::NotAnalyzed ? [] : ['processed_article_id' => $article?->id]);
    }

    private function ownerName(?TrackedTicket $row, TicketHead $head): ?string
    {
        if ($head->ownerId === null) {
            return null;
        }

        return $row?->owner_zammad_id === $head->ownerId && $row->owner_name !== null ? $row->owner_name : $this->zammad->userName($head->ownerId);
    }

    /**
     * A customer message, or an agent mail that counts as one, changes one of these times.
     * An internal note, a status change or a new owner changes neither.
     */
    private static function contactChanged(TrackedTicket $row, TicketHead $head): bool
    {
        return ! self::sameTime($row->last_contact_customer_at, $head->lastContactCustomerAt)
            || ! self::sameTime($row->last_activity_at, $head->lastActivityAt);
    }

    /**
     * Whether a list entry shows nothing new for a readable ticket.
     */
    public static function isCurrent(TrackedTicket $row, CarbonImmutable $listedUpdatedAt): bool
    {
        return $row->sync_status === SyncStatus::Synced && ! $listedUpdatedAt->greaterThan($row->zammad_updated_at);
    }

    private static function sameTime(mixed $stored, ?CarbonImmutable $read): bool
    {
        return $stored === null || $read === null ? $stored === null && $read === null : $read->equalTo($stored);
    }
}

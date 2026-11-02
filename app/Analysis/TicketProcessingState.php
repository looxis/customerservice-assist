<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\TrackedTicket;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The processing state of a tracked ticket (PROJ-66). It always belongs to one
 * message, the current customer message: only analyses based on exactly that
 * message count. A late analysis of an older message therefore changes nothing,
 * whichever of analysis and sync finishes first.
 */
class TicketProcessingState
{
    /**
     * The state of one customer message, from the real analyses based on exactly that message. Proposals an admin
     * deleted no longer count, and neither do failures from before such a deletion: it starts the ticket afresh.
     */
    public function resolve(string $number, ?int $articleId): ProcessingState
    {
        $deletedAt = Analysis::query()->where('ticket_number', $number)->max('content_deleted_at');
        $statuses = $this->realAnalyses($number)
            ->whereNull('content_deleted_at')
            ->when($deletedAt !== null, fn (Builder $query) => $query->where('created_at', '>', $deletedAt))
            ->when($articleId === null, fn (Builder $query) => $query->whereNull('based_on_article_id'), fn (Builder $query) => $query->where('based_on_article_id', $articleId))
            ->distinct()->pluck('status')->all();

        return match (true) {
            in_array('completed', $statuses, true) => ProcessingState::Analyzed,
            in_array('failed', $statuses, true) => ProcessingState::Failed,
            default => ProcessingState::NotAnalyzed,
        };
    }

    /**
     * The state of a ticket seen for the first time. Analyses from before this
     * feature name no message, so for them alone the time decides.
     */
    public function initial(string $number, ?int $articleId, ?CarbonInterface $articleAt): ProcessingState
    {
        $state = $this->resolve($number, $articleId);

        if ($state !== ProcessingState::NotAnalyzed || $articleId === null || $articleAt === null) {
            return $state;
        }

        $earlier = $this->realAnalyses($number)->whereNull('based_on_article_id')->whereNull('content_deleted_at')->where('status', 'completed')->where('created_at', '>=', $articleAt)->exists();

        return $earlier ? ProcessingState::Analyzed : ProcessingState::NotAnalyzed;
    }

    /**
     * An analysis was stored. The state changes only while the analysis is based
     * on the current customer message; the check is part of the update, so it
     * cannot overtake a sync. Test runs and questions have their own scope and never count.
     */
    public function recordAnalysis(string $number, string $scopeKey, ?int $basedOnArticleId, mixed $testUntil = null): void
    {
        if ($scopeKey !== $number || $testUntil !== null) {
            return;
        }

        TrackedTicket::query()
            ->where('number', $number)
            ->when($basedOnArticleId === null, fn (Builder $query) => $query->whereNull('last_customer_article_id'), fn (Builder $query) => $query->where('last_customer_article_id', $basedOnArticleId))
            ->update(['processing_state' => $this->resolve($number, $basedOnArticleId)->value, 'processed_article_id' => $basedOnArticleId]);
    }

    /**
     * Calculate the state of a ticket again for its current customer message, after its proposals were deleted.
     */
    public function refresh(string $number): void
    {
        $ticket = TrackedTicket::query()->where('number', $number)->first();

        if ($ticket === null) {
            return;
        }

        $state = $this->resolve($number, $ticket->last_customer_article_id);
        $ticket->update(['processing_state' => $state, 'processed_article_id' => $state === ProcessingState::NotAnalyzed ? null : $ticket->last_customer_article_id]);
    }

    /**
     * @return Builder<Analysis>
     */
    private function realAnalyses(string $number): Builder
    {
        return Analysis::query()->where('ticket_number', $number)->where('scope_key', $number)->whereNull('test_until');
    }
}

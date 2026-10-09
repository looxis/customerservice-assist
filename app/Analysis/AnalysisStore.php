<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\CustomerGroupMemory;
use App\Models\KnowledgeGap;
use App\Models\MessageTranslation;
use App\Models\TicketCaseChoice;
use App\Models\TicketSummary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lasting storage for analyses, summaries and remembered choices (PROJ-11).
 * Customer content is encrypted and emptied after the retention period; the
 * figures of an analysis stay for evaluations.
 */
class AnalysisStore
{
    public function summary(string $scopeKey): ?Summary
    {
        $summary = TicketSummary::query()->where('scope_key', $scopeKey)->first();

        return $summary === null ? null : Summary::fromArray($summary->content);
    }

    public function putSummary(string $scopeKey, Summary $summary): void
    {
        TicketSummary::query()->updateOrCreate(
            ['scope_key' => $scopeKey],
            ['ticket_number' => Str::before($scopeKey, '.'), 'content' => $summary->toArray()],
        )->touch();
    }

    /**
     * The customer group and products chosen for a ticket, with who chose them.
     *
     * @return array{group: string, products: list<string>, staff: string, at: string}|null
     */
    public function caseChoice(string $ticketNumber): ?array
    {
        $choice = TicketCaseChoice::query()->where('ticket_number', $ticketNumber)->first();

        return $choice === null ? null : [
            'group' => $choice->customer_group,
            'products' => array_values($choice->products ?? []),
            'staff' => $choice->staff_name,
            'at' => $choice->updated_at->toIso8601String(),
        ];
    }

    /**
     * @param  list<string>  $products
     */
    public function putCaseChoice(string $ticketNumber, string $group, array $products, string $staff): void
    {
        TicketCaseChoice::query()->updateOrCreate(
            ['ticket_number' => $ticketNumber],
            ['customer_group' => $group, 'products' => $products, 'staff_name' => $staff],
        )->touch();
    }

    /**
     * The customer group last chosen for a Zammad customer or organization.
     */
    public function customerGroup(?string $customerKey): ?string
    {
        return $customerKey === null ? null : CustomerGroupMemory::query()
            ->where('customer_key', $customerKey)
            ->where('updated_at', '>=', now()->subDays((int) config('analysis.customer_group_retention_days')))
            ->value('customer_group');
    }

    public function putCustomerGroup(?string $customerKey, string $group): void
    {
        if ($customerKey !== null) {
            CustomerGroupMemory::query()->updateOrCreate(['customer_key' => $customerKey], ['customer_group' => $group])->touch();
        }
    }

    /**
     * Store a finished analysis: its figures in columns, the whole record encrypted.
     *
     * @param  array<string, mixed>  $result
     */
    public function putResult(array $result): string
    {
        $meta = $result['meta'] ?? [];
        $answer = $result['result'] ?? [];
        $id = (string) Str::uuid();

        Analysis::query()->create([
            'uuid' => $id,
            'ticket_number' => (string) $result['ticket'],
            'scope_key' => (string) ($result['scope'] ?? $result['ticket']),
            'status' => 'completed',
            'staff_name' => (string) ($meta['staff'] ?? ''),
            'test_until' => $meta['test_until'] ?? null,
            'customer_group' => $meta['customer_group'] ?? null,
            'products' => $meta['products'] ?? [],
            'variant' => $meta['variant'] ?? null,
            'category' => $answer['category'] ?? null,
            'assessment' => $answer['assessment'] ?? null,
            'confidence' => $answer['confidence']['level'] ?? null,
            'actions' => $answer['actions'] ?? [],
            'knowledge_ids' => $answer['knowledge_ids'] ?? [],
            'knowledge_fingerprints' => $meta['knowledge_fingerprints'] ?? [],
            'provider' => $meta['provider'] ?? null,
            'model' => $meta['model'] ?? null,
            'prompt_version' => $meta['prompt_version'] ?? null,
            'summary_prompt_version' => $meta['summary_prompt_version'] ?? null,
            'knowledge_state' => $meta['knowledge_state'] ?? null,
            'duration_ms' => $meta['duration_ms'] ?? null,
            'input_tokens' => $meta['input_tokens'] ?? null,
            'output_tokens' => $meta['output_tokens'] ?? null,
            'attempts' => $meta['attempts'] ?? null,
            'content' => $result,
        ]);

        return $id;
    }

    /**
     * Log a failed analysis without any content.
     */
    public function putFailure(string $ticketNumber, string $scopeKey, string $staff, string $model, string $error, int $durationMs): void
    {
        Analysis::query()->create([
            'uuid' => (string) Str::uuid(),
            'ticket_number' => $ticketNumber,
            'scope_key' => $scopeKey,
            'status' => 'failed',
            'error' => $error,
            'staff_name' => $staff,
            'provider' => config('analysis.provider'),
            'model' => $model,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Save a changed result, e.g. with the edited reply (PROJ-10).
     *
     * @param  array<string, mixed>  $result
     */
    public function updateResult(string $id, array $result): void
    {
        unset($result['scope']);

        Analysis::query()->where('uuid', $id)->whereNotNull('content')->first()?->update(['content' => $result]);
    }

    /**
     * Change the stored record of an analysis in one step: the record is read
     * and written under a lock, so two changes arriving at the same time
     * (e.g. saving the draft while a back translation finishes) never
     * overwrite each other.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $change
     * @return array<string, mixed>|null The changed record, or null when the analysis has no content.
     */
    public function changeResult(string $id, callable $change): ?array
    {
        return DB::transaction(function () use ($id, $change): ?array {
            $analysis = Analysis::query()->where('uuid', $id)->whereNotNull('content')->lockForUpdate()->first();

            if ($analysis === null) {
                return null;
            }

            $content = $change($analysis->content);
            $analysis->update(['content' => $content]);

            return $content;
        });
    }

    /**
     * The latest finished analysis with content of a ticket (or of a test-run
     * cut point, see Ticket::summaryKey()).
     */
    public function latest(string $scopeKey): ?string
    {
        return $this->finished($scopeKey)->whereNotNull('content')->value('uuid');
    }

    /**
     * Finished analyses of a ticket or cut point, newest first, without content.
     *
     * @return list<array{id: string, created_at: CarbonImmutable, staff: string, group: string|null, variant: string|null, assessment: string|null, confidence: string|null, test: bool, removed: string|null}>
     */
    public function history(string $scopeKey): array
    {
        return $this->finished($scopeKey)
            ->get(['uuid', 'created_at', 'staff_name', 'customer_group', 'variant', 'assessment', 'confidence', 'test_until', 'content_purged_at', 'content_deleted_at', 'feedback_level'])
            ->map(fn (Analysis $analysis): array => [
                'id' => $analysis->uuid,
                'created_at' => CarbonImmutable::parse($analysis->created_at),
                'staff' => $analysis->staff_name,
                'group' => $analysis->customer_group,
                'variant' => $analysis->variant,
                'assessment' => $analysis->assessment,
                'confidence' => $analysis->confidence,
                'test' => $analysis->test_until !== null,
                'feedback' => $analysis->feedback_level,
                'removed' => match (true) {
                    $analysis->content_deleted_at !== null => 'deleted',
                    $analysis->content_purged_at !== null => 'purged',
                    default => null,
                },
            ])
            ->values()
            ->all();
    }

    /**
     * Who emptied the content of a ticket last, if nothing newer exists.
     *
     * @return array{staff: string, at: CarbonImmutable}|null
     */
    public function deletion(string $ticketNumber): ?array
    {
        $deleted = Analysis::query()->where('ticket_number', $ticketNumber)->whereNotNull('content_deleted_at')->latest('content_deleted_at')->first();

        if ($deleted === null || Analysis::query()->where('ticket_number', $ticketNumber)->whereNotNull('content')->where('created_at', '>', $deleted->content_deleted_at)->exists()) {
            return null;
        }

        return ['staff' => (string) $deleted->content_deleted_by, 'at' => CarbonImmutable::parse($deleted->content_deleted_at)];
    }

    /**
     * Save the feedback for an analysis with content (PROJ-12); the latest one counts.
     */
    public function putFeedback(string $id, string $level, ?string $suggested, ?string $comment, string $staff): ?array
    {
        $analysis = Analysis::query()->where('uuid', $id)->whereNotNull('content')->first();

        if ($analysis === null) {
            return null;
        }

        $analysis->update([
            'feedback_level' => $level,
            'feedback_suggested' => $suggested,
            'feedback_comment' => $comment === null || trim($comment) === '' ? null : $comment,
            'feedback_by' => $staff,
            'feedback_at' => now(),
        ]);

        return $this->feedbackOf($analysis);
    }

    /**
     * @return array{level: string, by: string, at: CarbonImmutable, comment: string|null}|null
     */
    public function feedback(string $id): ?array
    {
        $analysis = Analysis::query()->where('uuid', $id)->first();

        return $analysis === null ? null : $this->feedbackOf($analysis);
    }

    /**
     * Share of usable feedback among rated real analyses of the last days
     * (no test runs), or null without any rating.
     *
     * @return array{share: int, rated: int}|null
     */
    public function successRate(): ?array
    {
        $rated = Analysis::query()
            ->where('status', 'completed')
            ->whereNull('test_until')
            ->whereNotNull('feedback_level')
            ->where('created_at', '>=', now()->subDays((int) config('analysis.feedback_success_days')));

        $total = (clone $rated)->count();

        if ($total === 0) {
            return null;
        }

        $usable = (clone $rated)->whereIn('feedback_level', config('analysis.feedback_usable_levels'))->count();

        return ['share' => (int) round($usable / $total * 100), 'rated' => $total];
    }

    /**
     * @return array{level: string, by: string, at: CarbonImmutable, comment: string|null}|null
     */
    private function feedbackOf(Analysis $analysis): ?array
    {
        return $analysis->feedback_level === null ? null : [
            'level' => $analysis->feedback_level,
            'by' => (string) $analysis->feedback_by,
            'at' => CarbonImmutable::parse($analysis->feedback_at),
            'comment' => $analysis->feedback_comment,
        ];
    }

    /**
     * The stored record of an analysis with content, plus its scope.
     *
     * @return array<string, mixed>|null
     */
    public function result(string $id): ?array
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $analysis = Analysis::query()->where('uuid', $id)->whereNotNull('content')->first();

        return $analysis === null ? null : [...$analysis->content, 'scope' => $analysis->scope_key, 'feedback' => $this->feedbackOf($analysis)];
    }

    /**
     * Empty all customer content of a ticket on request (admin, PROJ-11); the
     * figures stay with a note who deleted when.
     */
    public function deleteTicket(string $ticketNumber, string $staff): void
    {
        Analysis::query()->where('ticket_number', $ticketNumber)->where('status', 'completed')->whereNull('content_deleted_at')
            ->update(['content' => null, 'feedback_comment' => null, 'content_deleted_at' => now(), 'content_deleted_by' => $staff]);

        TicketSummary::query()->where('ticket_number', $ticketNumber)->delete();
        TicketCaseChoice::query()->where('ticket_number', $ticketNumber)->delete();
        MessageTranslation::query()->where('ticket_number', $ticketNumber)->delete();
    }

    /**
     * Empty content older than the retention period; drop old summaries,
     * choices and customer memories.
     *
     * @return array{analyses: int, gaps: int, translations: int, summaries: int, choices: int, customers: int}
     */
    public function purge(): array
    {
        $before = now()->subMonths((int) config('analysis.content_retention_months'));

        return [
            'analyses' => Analysis::query()->whereNotNull('content')->where('created_at', '<', $before)
                ->update(['content' => null, 'feedback_comment' => null, 'content_purged_at' => now()]),
            'gaps' => KnowledgeGap::query()->whereIn('status', ['done', 'discarded'])->whereNotNull('content')->where('resolved_at', '<', $before)
                ->update(['content' => null, 'content_purged_at' => now()]),
            'translations' => MessageTranslation::query()->where('created_at', '<', $before)->delete(),
            'summaries' => TicketSummary::query()->where('updated_at', '<', $before)->delete(),
            'choices' => TicketCaseChoice::query()->where('updated_at', '<', $before)->delete(),
            'customers' => CustomerGroupMemory::query()->where('updated_at', '<', now()->subDays((int) config('analysis.customer_group_retention_days')))->delete(),
        ];
    }

    /**
     * @return Builder<Analysis>
     */
    private function finished(string $scopeKey): Builder
    {
        return Analysis::query()->where('scope_key', $scopeKey)->where('status', 'completed')->latest()->latest('id');
    }
}

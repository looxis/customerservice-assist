<?php

namespace App\Analysis;

use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeLibrary;
use App\Models\Analysis;
use App\Models\KnowledgeGap;
use App\Models\KnowledgeReviewFlag;
use Illuminate\Support\Facades\Cache;

/**
 * The few figures shown to everybody at the bottom of the sidebar (PROJ-41).
 * Only counts and sizes, never content. The knowledge figures are kept for a
 * minute so not every page reads all files.
 */
class AppStats
{
    private const int KNOWLEDGE_CACHE_SECONDS = 60;

    private const string REVIEW_CACHE_KEY = 'app-stats.review';

    public function __construct(private readonly KnowledgeLibrary $library) {}

    /**
     * @return array{version: string, state: string, documents: int, drafts: int, bytes: int, tickets: int, gaps: int, openGaps: int, review: int}
     */
    public function all(): array
    {
        return [
            'version' => (string) config('app.version'),
            ...$this->knowledge(),
            'tickets' => $this->processedTickets(),
            ...$this->gaps(),
            'review' => $this->reviewCount(),
        ];
    }

    /**
     * Tickets with at least one finished analysis, test runs and questions without a ticket left out.
     */
    public function processedTickets(): int
    {
        return Analysis::query()->where('status', 'completed')->whereNull('test_until')->where('ticket_number', 'not like', 'F%')->distinct()->count('ticket_number');
    }

    /**
     * The figure for the sidebar, kept for a minute like the knowledge figures so not every page reads all files.
     * Reports, decisions and changes of the trial forget it at once.
     */
    public function reviewCount(): int
    {
        return Cache::remember(self::REVIEW_CACHE_KEY, self::KNOWLEDGE_CACHE_SECONDS, fn (): int => $this->review());
    }

    public static function forgetReviewCount(): void
    {
        Cache::forget(self::REVIEW_CACHE_KEY);
    }

    /**
     * Knowledge documents to look at (PROJ-56): a trial that is over, or open reports "please check".
     */
    public function review(): int
    {
        $overdue = $this->library->all()->filter(fn (KnowledgeDocument $document): bool => $document->parsed && $document->id !== null && $document->trialOpen() && $document->trialExpired())->pluck('id');
        $reported = KnowledgeReviewFlag::query()->where('status', 'open')->distinct()->pluck('document_id');

        return $overdue->merge($reported)->unique()->count();
    }

    /**
     * @return array{gaps: int, openGaps: int}
     */
    public function gaps(): array
    {
        $gaps = KnowledgeGap::query()->where('test_run', false);

        return [
            'gaps' => (clone $gaps)->count(),
            'openGaps' => (clone $gaps)->where('status', 'open')->count(),
        ];
    }

    /**
     * @return array{state: string, documents: int, drafts: int, bytes: int}
     */
    public function knowledge(): array
    {
        return Cache::remember('app-stats.knowledge', self::KNOWLEDGE_CACHE_SECONDS, function (): array {
            $usable = $this->library->usable();
            $root = rtrim((string) config('knowledge.path'), '/');

            return [
                'state' => $this->library->state()->label(),
                'documents' => $usable->count(),
                'drafts' => $usable->where('status', 'draft')->count(),
                'bytes' => (int) $usable->sum(fn (KnowledgeDocument $document): int => (int) @filesize($root.'/'.$document->path)),
            ];
        });
    }

    /**
     * A size in German notation, e.g. "412 KB" or "1,2 MB".
     */
    public static function size(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_048_576 => number_format($bytes / 1_048_576, 1, ',', '.').' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 0, ',', '.').' KB',
            default => $bytes.' Byte',
        };
    }
}

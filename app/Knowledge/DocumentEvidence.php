<?php

namespace App\Knowledge;

use App\Analysis\AnalysisStore;
use App\Models\Analysis;
use App\Models\KnowledgeReviewFlag;

/**
 * What speaks for or against confirming a document (PROJ-23): in how many proposals it was
 * retrieved and named by the AI, and how they were rated. Figures only, taken from the stored
 * columns, so it works after the contents were deleted. Test runs and failed analyses do not count.
 */
class DocumentEvidence
{
    /** Below this many proposals the page notes that the data is thin; it never blocks. */
    public const int THIN = 3;

    /**
     * How a document works in practice (PROJ-56): in how many recent proposals the AI named it, what happened to the
     * reply drafts of those proposals (sent or copied as they were, adjusted, only edited, not taken over), how they
     * were rated compared with all proposals, and the reports "please check". Figures only, from stored columns.
     *
     * @return array{named: int, recent: int, outcomes: array<string, int>, rated: int, usable_share: int|null, overall_share: int|null, reports: array{open: int, checked: int, reasons: array<string, int>}}
     */
    public function usage(string $id): array
    {
        $named = Analysis::query()->where('status', 'completed')->whereNull('test_until')->whereJsonContains('knowledge_ids', $id)
            ->get(['feedback_level', 'reply_copied_at', 'reply_copied_changed', 'reply_sent_at', 'reply_edited_at', 'created_at']);

        $outcomes = ['sent_unchanged' => 0, 'sent_changed' => 0, 'copied_unchanged' => 0, 'copied_changed' => 0, 'edited' => 0, 'none' => 0];

        foreach ($named as $analysis) {
            $key = match (true) {
                $analysis->reply_sent_at !== null => $analysis->reply_copied_changed ? 'sent_changed' : 'sent_unchanged',
                $analysis->reply_copied_at !== null => $analysis->reply_copied_changed ? 'copied_changed' : 'copied_unchanged',
                $analysis->reply_edited_at !== null => 'edited',
                default => 'none',
            };
            $outcomes[$key]++;
        }

        $rated = $named->whereNotNull('feedback_level');
        $usable = $rated->whereIn('feedback_level', config('analysis.feedback_usable_levels'))->count();
        $flags = KnowledgeReviewFlag::query()->where('document_id', $id)->get(['status', 'reason']);

        return [
            'named' => $named->count(),
            'recent' => $named->where('created_at', '>=', now()->subDays(30))->count(),
            'outcomes' => $outcomes,
            'rated' => $rated->count(),
            'usable_share' => $rated->count() === 0 ? null : (int) round($usable / $rated->count() * 100),
            'overall_share' => app(AnalysisStore::class)->successRate()['share'] ?? null,
            'reports' => [
                'open' => $flags->where('status', 'open')->count(),
                'checked' => $flags->where('status', 'checked')->count(),
                'reasons' => $flags->where('status', 'open')->countBy('reason')->all(),
            ],
        ];
    }

    /**
     * @return array{retrieved: int, named: int, ratings: array<string, int>, unrated: int, thin: bool}
     */
    public function for(string $id): array
    {
        $base = fn () => Analysis::query()->where('status', 'completed')->whereNull('test_until');

        $named = $base()->whereJsonContains('knowledge_ids', $id)->get(['feedback_level']);
        $retrievedCount = $base()->whereJsonContainsKey('knowledge_fingerprints->'.$id)->count();

        $ratings = [];

        foreach (array_keys(config('analysis.feedback_levels')) as $level) {
            $ratings[$level] = $named->where('feedback_level', $level)->count();
        }

        return [
            'retrieved' => $retrievedCount,
            'named' => $named->count(),
            'ratings' => $ratings,
            'unrated' => $named->whereNull('feedback_level')->count(),
            'thin' => $named->count() < self::THIN,
        ];
    }
}

<?php

namespace App\Knowledge;

use App\Models\Analysis;

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

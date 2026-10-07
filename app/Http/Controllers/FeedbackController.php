<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisStore;
use App\Http\Requests\StoreFeedbackRequest;
use App\Staff\StaffDirectory;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    /**
     * Save how usable an analysis was (PROJ-12), sent in the background.
     */
    public function __invoke(string $number, string $analysis, StoreFeedbackRequest $request, AnalysisStore $store, StaffDirectory $staff): JsonResponse
    {
        $stored = $store->result($analysis);

        if ($stored === null || ($stored['ticket'] ?? null) !== $number) {
            return response()->json(['message' => 'Diese Analyse ist nicht mehr verfügbar.'], 404);
        }

        $feedback = $store->putFeedback($analysis, $request->validated('level'), $request->validated('suggested'), $request->validated('comment'), (string) $staff->current($request));

        return response()->json([
            'saved' => true,
            'level' => $feedback['level'],
            'label' => config('analysis.feedback_levels.'.$feedback['level']),
            'by' => 'Bewertet von '.$feedback['by'].' am '.$feedback['at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr',
        ]);
    }
}

<?php

namespace App\Labeling;

use App\Models\LabelRelease;
use App\Models\LabelReview;
use App\Models\LabelTicket;

/**
 * Files the decision of an admin about a ticket (PROJ-78) and watches the hit rate of released categories.
 */
class ReviewRecorder
{
    /** Samples after the release before a bad hit rate locks a category. */
    private const int LOCK_AFTER_SAMPLES = 20;

    public function __construct(private readonly LabelingSettings $settings) {}

    /**
     * @param  string  $decision  A category value or LabelTicket::NONE
     */
    public function record(LabelTicket $ticket, string $decision, string $reviewer): LabelReview
    {
        $prediction = $ticket->latestPrediction;

        $origin = match (true) {
            $ticket->isTest() => 'test',
            $ticket->decision !== null => 'korrektur',
            $prediction?->queue === 'stichprobe' => 'stichprobe',
            default => 'zweifel',
        };

        $review = $ticket->reviews()->create([
            'label_prediction_id' => $prediction?->id,
            'decision' => $decision,
            'origin' => $origin,
            'hit' => $origin === 'stichprobe' ? ($prediction->category ?? LabelTicket::NONE) === $decision : null,
            'reviewer' => $reviewer,
        ]);

        $ticket->update([
            'decision' => $decision,
            'decision_origin' => $origin,
            'decided_by' => $reviewer,
            'decided_at' => now(),
            'tag_kind' => 'bestätigt',
            'last_activity_at' => now(),
        ]);

        if ($origin === 'stichprobe' && $prediction?->category !== null) {
            $this->lockWhenFailing($prediction->category);
        }

        return $review;
    }

    private function lockWhenFailing(string $category): void
    {
        $release = LabelRelease::query()->where('category', $category)->first();

        if ($release === null || ! $release->released) {
            return;
        }

        $samples = LabelReview::query()
            ->where('origin', 'stichprobe')
            ->where('created_at', '>', $release->released_at)
            ->whereHas('prediction', fn ($query) => $query->where('category', $category));

        $count = (clone $samples)->count();

        if ($count >= self::LOCK_AFTER_SAMPLES && (clone $samples)->where('hit', true)->count() / $count * 100 < $this->settings->all()['min_rate']) {
            $release->update(['released' => false, 'released_at' => null, 'released_by' => null, 'locked_at' => now()]);
        }
    }
}

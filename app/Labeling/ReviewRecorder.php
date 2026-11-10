<?php

namespace App\Labeling;

use App\Models\LabelRelease;
use App\Models\LabelReview;
use App\Models\LabelTicket;
use Illuminate\Support\Collection;

/**
 * Files the decision of an admin about a ticket (PROJ-78), or the choice of the staff on the ticket page (PROJ-80),
 * and watches the hit rate of released categories.
 */
class ReviewRecorder
{
    /** Samples after the release before a bad hit rate locks a category. */
    private const int LOCK_AFTER_SAMPLES = 20;

    /** How many of an admin's last decisions "Zurück" in the review reaches. */
    public const int REVISABLE = 2;

    public function __construct(private readonly LabelingSettings $settings) {}

    /**
     * A sample marked unsure keeps its origin but is no hit and no miss.
     *
     * @param  string  $decision  A category value or LabelTicket::UNSURE
     */
    public function record(LabelTicket $ticket, string $decision, string $reviewer): LabelReview
    {
        $prediction = $ticket->latestPrediction;
        $unsure = $decision === LabelTicket::UNSURE;

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
            'hit' => $origin === 'stichprobe' && ! $unsure ? $prediction->category === $decision : null,
            'reviewer' => $reviewer,
        ]);

        $ticket->update([
            'decision' => $decision,
            'decision_origin' => $origin,
            'decided_by' => $reviewer,
            'decided_at' => now(),
            'tag_kind' => $unsure ? null : 'bestätigt',
            'last_activity_at' => now(),
        ]);

        if ($origin === 'stichprobe' && ! $unsure && $prediction?->category !== null) {
            $this->lockWhenFailing($prediction->category);
        }

        $ticket->shareCategory();

        return $review;
    }

    /**
     * Files the choice of a member of the staff on the ticket page (PROJ-80). It counts at once when it matches the
     * newest proposal; a differing one waits in the queue "abweichung" until an admin decided, and its tag waits too.
     * Such a decision is never an example in the prompt nor a sample. "Unsure" is only a note, no decision: the
     * ticket stays open for the admin and is never tagged automatically.
     *
     * @param  string  $decision  A category value or LabelTicket::UNSURE
     */
    public function recordFromTicket(LabelTicket $ticket, string $decision, string $staff): void
    {
        $prediction = $ticket->latestPrediction;

        if ($decision === LabelTicket::UNSURE) {
            $ownDecision = $ticket->decision_origin === LabelTicket::STAFF
                ? ['decision' => null, 'decision_origin' => null, 'decided_by' => null, 'decided_at' => null, 'tag_kind' => null]
                : [];

            $ticket->update([...$ownDecision, 'staff_unsure_by' => $staff, 'staff_unsure_at' => now(), 'last_activity_at' => now()]);

            // The test set has its own queue; a clear proposal of the training set goes to the doubtful ones.
            $queue = match (true) {
                $ticket->isTest() => null,
                in_array($prediction?->queue, [null, 'stichprobe', 'abweichung'], true) => 'zweifel',
                default => $prediction?->queue,
            };
            $prediction?->update(['queue' => $queue]);
        } else {
            $ticket->reviews()->create([
                'label_prediction_id' => $prediction?->id,
                'decision' => $decision,
                'origin' => LabelTicket::STAFF,
                'hit' => null,
                'reviewer' => $staff,
            ]);

            $ticket->update([
                'decision' => $decision,
                'decision_origin' => LabelTicket::STAFF,
                'decided_by' => $staff,
                'decided_at' => now(),
                'staff_unsure_by' => null,
                'staff_unsure_at' => null,
                'tag_kind' => 'bestätigt',
                'last_activity_at' => now(),
            ]);

            if ($prediction?->category !== $decision) {
                $prediction?->update(['queue' => 'abweichung']);
            } elseif ($prediction->queue === 'abweichung') {
                $prediction->update(['queue' => null]);
            }
        }

        $ticket->shareCategory();
    }

    /**
     * The last decisions of an admin, newest first, that can still be changed: the ticket is still there and no later
     * decision about it replaced this one.
     *
     * @return Collection<int, LabelReview>
     */
    public function recent(string $reviewer): Collection
    {
        return LabelReview::query()
            ->where('reviewer', $reviewer)
            ->where('origin', '!=', LabelTicket::STAFF)
            ->latest('id')
            ->limit(self::REVISABLE)
            ->with(['labelTicket.latestPrediction', 'prediction'])
            ->get()
            ->filter(fn (LabelReview $review): bool => $review->labelTicket !== null && (int) $review->labelTicket->reviews()->max('id') === $review->id)
            ->values();
    }

    /**
     * Replaces a decision taken by mistake in place: origin and order stay, a sample counts as hit or miss by the new
     * answer and the next tag run sets, changes or removes the tag accordingly.
     *
     * @param  string  $decision  A category value or LabelTicket::UNSURE
     */
    public function amend(LabelReview $review, string $decision): void
    {
        $prediction = $review->prediction;
        $unsure = $decision === LabelTicket::UNSURE;

        $review->update([
            'decision' => $decision,
            'hit' => $review->origin === 'stichprobe' && ! $unsure ? $prediction?->category === $decision : null,
        ]);

        $review->labelTicket->update([
            'decision' => $decision,
            'decided_at' => now(),
            'tag_kind' => $unsure ? null : 'bestätigt',
            'last_activity_at' => now(),
        ]);

        if ($review->origin === 'stichprobe' && ! $unsure && $prediction?->category !== null) {
            $this->lockWhenFailing($prediction->category);
        }

        $review->labelTicket->shareCategory();
    }

    private function lockWhenFailing(string $category): void
    {
        $release = LabelRelease::query()->where('category', $category)->first();

        if ($release === null || ! $release->released) {
            return;
        }

        $samples = LabelReview::query()
            ->where('origin', 'stichprobe')
            ->whereNotNull('hit')
            ->where('created_at', '>', $release->released_at)
            ->whereHas('prediction', fn ($query) => $query->where('category', $category)->where('model', LabelClassifier::modelName()));

        $count = (clone $samples)->count();

        if ($count >= self::LOCK_AFTER_SAMPLES && (clone $samples)->where('hit', true)->count() / $count * 100 < $this->settings->all()['min_rate']) {
            $release->update(['released' => false, 'released_at' => null, 'released_by' => null, 'locked_at' => now()]);
        }
    }
}

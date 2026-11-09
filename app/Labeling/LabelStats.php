<?php

namespace App\Labeling;

use App\Models\LabelPrediction;
use App\Models\LabelRelease;
use App\Models\LabelReview;
use App\Models\LabelTicket;
use Illuminate\Support\Collection;

/**
 * The figures per category for the page "Ticket-Klassifikation" and the rules of the release (PROJ-78).
 */
class LabelStats
{
    public function __construct(private readonly LabelingSettings $settings) {}

    /**
     * Tickets marked unsure count for the category the model proposed.
     *
     * @return Collection<int, array{category: LabelCategory, confirmed: int, corrected: int, automatic: int, open: int, unsure: int, tested: int, sampled: int, hits: int, released: bool, locked: bool}>
     */
    public function categories(): Collection
    {
        $decided = LabelTicket::query()
            ->whereNotNull('decision')
            ->whereNot('decision_origin', 'vorhanden')
            ->with('latestPrediction')
            ->get(['id', 'decision', 'decision_origin'])
            ->groupBy('decision');

        $unsure = $decided->get(LabelTicket::UNSURE, collect())->countBy(fn (LabelTicket $ticket): string => (string) $ticket->latestPrediction?->category);
        $releases = LabelRelease::query()->get()->keyBy('category');
        $tested = $this->tested();

        return collect(LabelCategory::cases())->map(function (LabelCategory $category) use ($decided, $unsure, $releases, $tested): array {
            $tickets = $decided->get($category->value, collect());
            $human = $tickets->where('decision_origin', '!=', 'automatisch');
            $release = $releases->get($category->value);
            [$sampled, $hits] = $this->sample($category, $release);

            return [
                'category' => $category,
                'confirmed' => $human->filter(fn (LabelTicket $ticket): bool => $ticket->latestPrediction?->category === $category->value)->count(),
                'corrected' => $human->filter(fn (LabelTicket $ticket): bool => $ticket->latestPrediction?->category !== $category->value)->count(),
                'automatic' => $tickets->where('decision_origin', 'automatisch')->count(),
                'open' => LabelTicket::query()->whereNull('decision')->where('state', 'classified')->whereHas('latestPrediction', fn ($query) => $query->where('category', $category->value))->count(),
                'unsure' => $unsure->get($category->value, 0),
                'tested' => $tested[$category->value] ?? 0,
                'sampled' => $sampled,
                'hits' => $hits,
                'released' => (bool) $release?->released,
                'locked' => $release !== null && ! $release->released && $release->locked_at !== null,
            ];
        });
    }

    /**
     * The random samples of a category since it was last locked: how many were looked at, how many the model got right.
     * Only proposals of the current model count, so that a change of model needs samples of its own.
     *
     * @return array{0: int, 1: int}
     */
    public function sample(LabelCategory $category, ?LabelRelease $release = null): array
    {
        $release ??= LabelRelease::query()->where('category', $category->value)->first();

        $query = LabelReview::query()
            ->where('origin', 'stichprobe')
            ->whereNotNull('hit')
            ->whereHas('prediction', fn ($prediction) => $prediction->where('category', $category->value)->where('model', LabelClassifier::modelName()))
            ->when($release?->locked_at !== null, fn ($query) => $query->where('created_at', '>', $release->locked_at));

        return [(clone $query)->count(), (clone $query)->where('hit', true)->count()];
    }

    /**
     * How many tickets of the test set carry a decision, per category (tickets marked unsure do not count).
     *
     * @return array<string, int>
     */
    public function tested(): array
    {
        return LabelTicket::query()
            ->where('split', 'test')
            ->whereIn('decision', array_column(LabelCategory::cases(), 'value'))
            ->groupBy('decision')
            ->selectRaw('decision, count(*) as tickets')
            ->pluck('tickets', 'decision')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * The categories whose test set has reached the test target: tickets of the test set proposed for them need no review.
     *
     * @return list<string>
     */
    public function fullTestCategories(): array
    {
        $target = $this->settings->all()['test_target'];

        return array_keys(array_filter($this->tested(), fn (int $count): bool => $count >= $target));
    }

    /**
     * Whether the samples of a category are enough to release it.
     */
    public function canRelease(LabelCategory $category): bool
    {
        [$sampled, $hits] = $this->sample($category);
        $settings = $this->settings->all();

        return $sampled >= $settings['min_cases'] && $hits / $sampled * 100 >= $settings['min_rate'];
    }

    public function release(LabelCategory $category, string $admin): bool
    {
        if (! $this->canRelease($category)) {
            return false;
        }

        LabelRelease::query()->updateOrCreate(['category' => $category->value], ['released' => true, 'released_by' => $admin, 'released_at' => now()]);

        return true;
    }

    /**
     * How many of the references that carried a tag before the tagging (the hand-tagged tickets) the model recognized.
     *
     * @return array{total: int, hits: int}
     */
    public function references(): array
    {
        $tickets = LabelTicket::query()->where('decision_origin', 'vorhanden')->with('latestPrediction')->get(['id', 'decision']);
        $predicted = $tickets->filter(fn (LabelTicket $ticket): bool => $ticket->latestPrediction !== null);

        return ['total' => $predicted->count(), 'hits' => $predicted->filter(fn (LabelTicket $ticket): bool => $ticket->latestPrediction->category === $ticket->decision)->count()];
    }

    public function isReleased(?string $category): bool
    {
        return $category !== null && LabelRelease::query()->where('category', $category)->where('released', true)->exists();
    }

    /**
     * Whether a ticket may get its tag without a look from an admin: a clear proposal in a released category.
     */
    public function mayTagAutomatically(LabelTicket $ticket): bool
    {
        $prediction = $ticket->latestPrediction;

        return $ticket->decision === null
            && $ticket->split === 'training'
            && $ticket->state === 'classified'
            && $ticket->existing_tag === null
            && $prediction instanceof LabelPrediction
            && $prediction->queue === null
            && $this->isReleased($prediction->category);
    }
}

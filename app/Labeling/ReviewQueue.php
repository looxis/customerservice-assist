<?php

namespace App\Labeling;

use App\Models\LabelTicket;
use Illuminate\Database\Eloquent\Builder;

/**
 * The queues of the review (PROJ-78), in the order in which an admin works through them. Each queue is split into
 * what is needed, because the proposed category still lacks training tickets, test tickets or samples
 * (LabelStats::needs()), and what may be reviewed if wanted. The split shifts by itself as categories fill up.
 */
class ReviewQueue
{
    public const string NEEDED = 'noetig';

    public const string OPTIONAL = 'bei-bedarf';

    public const array PARTS = [
        self::NEEDED => 'Nötig',
        self::OPTIONAL => 'Bei Bedarf',
    ];

    public const array LABELS = [
        'abweichung' => 'Abweichung von früherer Entscheidung',
        'zweifel' => 'Unklar / mehrere Anliegen',
        'konfidenz' => 'Niedrige Konfidenz',
        'storno' => 'Vorschläge „Storno“ und „Adressänderung“',
        'storno-wort' => 'Wort „Storno“, nicht WISMO',
        'stichprobe' => 'Stichprobe',
        'test' => 'Testmenge',
        'unsicher' => 'Unsicher markiert',
    ];

    public function __construct(private readonly LabelStats $stats) {}

    /**
     * The queues per part with their counts; "Unsicher markiert" belongs to the optional part only. The needed samples
     * count what can still be had, drawn already or not. A page that calls next() too passes the needs once to both.
     *
     * @param  array<string, array{training: int, test: int, samples: int, released: bool}>|null  $needs
     * @return array<string, array<string, array{label: string, count: int}>>
     */
    public function queues(?LabelCategory $category = null, ?array $needs = null): array
    {
        $needs ??= $this->stats->needs();

        return collect(self::PARTS)->map(fn (string $title, string $part): array => collect(self::LABELS)
            ->reject(fn (string $label, string $key): bool => $key === 'unsicher' && $part === self::NEEDED)
            ->map(fn (string $label, string $key): array => [
                'label' => $label,
                'count' => $key === 'stichprobe' && $part === self::NEEDED ? $this->samplesToReview($needs, $category) : $this->filtered($key, $part, $category, $needs)->count(),
            ])
            ->all())->all();
    }

    /**
     * The newest ticket first (date in Zammad), so that today's concerns come before old ones. Marked unsure again, a
     * ticket moves to the end of its queue instead of coming right back. When no drawn sample is left that is still
     * needed, the next one is drawn.
     *
     * @param  array<string, array{training: int, test: int, samples: int, released: bool}>|null  $needs
     */
    public function next(string $queue, string $part = self::NEEDED, ?LabelCategory $category = null, ?array $needs = null): ?LabelTicket
    {
        $needs ??= $this->stats->needs();

        $ticket = $this->filtered($queue, $part, $category, $needs)
            ->with('latestPrediction')
            ->when($queue === 'unsicher', fn ($query) => $query->orderBy('decided_at')->orderBy('id'), fn ($query) => $query->orderByDesc('zammad_created_at')->orderByDesc('id'))
            ->first();

        return $ticket ?? ($queue === 'stichprobe' && $part === self::NEEDED ? $this->draw($needs, $category) : null);
    }

    /**
     * @return Builder<LabelTicket>
     */
    public function query(string $queue, string $part = self::NEEDED, ?LabelCategory $category = null): Builder
    {
        return $this->filtered($queue, $part, $category, $this->stats->needs());
    }

    /**
     * Needed is a ticket whose proposed category still lacks what its queue gives: test tickets in the test set,
     * samples in the sample, training tickets everywhere else. A deviation counts for its decision too, a ticket
     * without proposal is always needed.
     *
     * @param  array<string, array{training: int, test: int, samples: int, released: bool}>  $needs
     * @return Builder<LabelTicket>
     */
    private function filtered(string $queue, string $part, ?LabelCategory $category, array $needs): Builder
    {
        $query = $this->base($queue)->when($category !== null, fn ($query) => $query->whereHas('latestPrediction', fn ($prediction) => $prediction->where('category', $category->value)));

        if ($queue === 'unsicher') {
            return $query;
        }

        $lack = match ($queue) {
            'test' => 'test',
            'stichprobe' => 'samples',
            default => 'training',
        };
        $needed = array_keys(array_filter($needs, fn (array $need): bool => $need[$lack] > 0));

        if ($part === self::NEEDED) {
            return $query->where(fn ($query) => $query
                ->whereHas('latestPrediction', fn ($prediction) => $prediction->where(fn ($proposal) => $proposal->whereIn('category', $needed)->orWhereNull('category')))
                ->when($queue === 'abweichung', fn ($query) => $query->orWhereIn('decision', $needed)));
        }

        return $query
            ->whereHas('latestPrediction', fn ($prediction) => $prediction->whereNotIn('category', $needed))
            ->when($queue === 'abweichung', fn ($query) => $query->whereNotIn('decision', $needed));
    }

    /**
     * @return Builder<LabelTicket>
     */
    private function base(string $queue): Builder
    {
        if ($queue === 'abweichung') {
            // Decided tickets whose newest proposal differs and was not reviewed yet; the decision stays meanwhile.
            return LabelTicket::query()
                ->whereNotNull('decision')
                ->where('state', 'classified')
                ->whereNotNull('text')
                ->whereHas('latestPrediction', fn ($query) => $query->where('queue', 'abweichung')->whereNotExists(fn ($reviews) => $reviews
                    ->from('labeling_reviews')
                    ->whereColumn('labeling_reviews.label_prediction_id', 'labeling_predictions.id')));
        }

        if ($queue === 'unsicher') {
            return LabelTicket::query()->where('decision', LabelTicket::UNSURE)->where('state', 'classified')->whereNotNull('text');
        }

        $base = LabelTicket::query()->whereNull('decision')->where('state', 'classified')->whereNotNull('text');

        if ($queue === 'test') {
            return $base->where('split', 'test');
        }

        return $base->where('split', 'training')->whereHas('latestPrediction', fn ($query) => $query->where('queue', $queue));
    }

    /**
     * Per category that needs samples the smaller of its need and the samples drawn or drawable.
     *
     * @param  array<string, array{training: int, test: int, samples: int, released: bool}>  $needs
     */
    private function samplesToReview(array $needs, ?LabelCategory $category): int
    {
        return (int) collect($this->sampleNeeds($needs, $category))
            ->map(fn (array $need, string $value): int => min($need['samples'], $this->sampleable($value)->count()))
            ->sum();
    }

    /**
     * Marks a random clear proposal of the current model as sample, so that it counts for the hit rate: for released
     * categories first, whose automatic tags rest on the samples, then for the largest gap.
     *
     * @param  array<string, array{training: int, test: int, samples: int, released: bool}>  $needs
     */
    private function draw(array $needs, ?LabelCategory $category): ?LabelTicket
    {
        $order = collect($this->sampleNeeds($needs, $category))->sortBy([['released', 'desc'], ['samples', 'desc']]);

        foreach ($order->keys() as $value) {
            $ticket = $this->sampleable($value)
                ->whereHas('latestPrediction', fn ($prediction) => $prediction->whereNull('queue'))
                ->with('latestPrediction')
                ->inRandomOrder()
                ->first();

            if ($ticket !== null) {
                $ticket->latestPrediction->update(['queue' => 'stichprobe']);

                return $ticket;
            }
        }

        return null;
    }

    /**
     * @param  array<string, array{training: int, test: int, samples: int, released: bool}>  $needs
     * @return array<string, array{training: int, test: int, samples: int, released: bool}>
     */
    private function sampleNeeds(array $needs, ?LabelCategory $category): array
    {
        return array_filter($needs, fn (array $need, string $value): bool => $need['samples'] > 0 && ($category === null || $category->value === $value), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Open training tickets of a category that are a sample already or could become one: a clear proposal of the
     * current model, without a tag from before.
     *
     * @return Builder<LabelTicket>
     */
    private function sampleable(string $category): Builder
    {
        return LabelTicket::query()
            ->whereNull('decision')
            ->where('state', 'classified')
            ->where('split', 'training')
            ->whereNotNull('text')
            ->whereNull('existing_tag')
            ->whereHas('latestPrediction', fn ($prediction) => $prediction
                ->where('category', $category)
                ->where(fn ($query) => $query->where('queue', 'stichprobe')->orWhere(fn ($clear) => $clear->whereNull('queue')->where('model', LabelClassifier::modelName()))));
    }
}

<?php

namespace App\Labeling;

use App\Models\LabelTicket;
use Illuminate\Database\Eloquent\Builder;

/**
 * The queues of the review (PROJ-78), in the order in which an admin works through them.
 */
class ReviewQueue
{
    public const array LABELS = [
        'abweichung' => 'Abweichung von früherer Entscheidung',
        'zweifel' => 'Unklar / mehrere Anliegen',
        'konfidenz' => 'Niedrige Konfidenz',
        'storno' => 'Vorschläge „Storno“ und „Adressänderung“',
        'storno-wort' => 'Wort „Storno“, nicht WISMO',
        'stichprobe' => 'Stichprobe',
        'test' => 'Testmenge',
    ];

    /**
     * @return array<string, array{label: string, count: int}>
     */
    public function queues(): array
    {
        return collect(self::LABELS)->map(fn (string $label, string $key): array => ['label' => $label, 'count' => $this->query($key)->count()])->all();
    }

    public function next(string $queue): ?LabelTicket
    {
        return $this->query($queue)->with('latestPrediction')->orderBy('id')->first();
    }

    /**
     * @return Builder<LabelTicket>
     */
    public function query(string $queue): Builder
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

        $base = LabelTicket::query()->whereNull('decision')->where('state', 'classified')->whereNotNull('text');

        if ($queue === 'test') {
            return $base->where('split', 'test');
        }

        return $base->where('split', 'training')->whereHas('latestPrediction', fn ($query) => $query->where('queue', $queue));
    }
}

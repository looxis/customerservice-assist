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
        'zweifel' => 'Unklar / mehrere Anliegen',
        'konfidenz' => 'Niedrige Konfidenz',
        'storno' => 'Vorschläge „Storno“',
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
        $base = LabelTicket::query()->whereNull('decision')->where('state', 'classified')->whereNotNull('text');

        if ($queue === 'test') {
            return $base->where('split', 'test');
        }

        return $base->where('split', 'training')->whereHas('latestPrediction', fn ($query) => $query->where('queue', $queue));
    }
}

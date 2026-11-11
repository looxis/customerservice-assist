<?php

namespace App\Labeling;

use App\Models\LabelTicket;
use App\Models\TrackedTicket;
use Illuminate\Support\Collection;

/**
 * Why a ticket in the start page list shows no category (PROJ-80): the classification is still pending, the proposal
 * of a ticket of the test set is hidden on purpose, or there is none.
 */
class CategoryStatus
{
    public const string PENDING = 'pending';

    public const string HIDDEN = 'hidden';

    public const string NONE = 'none';

    public function __construct(private readonly LabelingSettings $settings) {}

    /**
     * The status of each ticket without a category, keyed by its Zammad ID.
     *
     * @param  Collection<int, TrackedTicket>  $rows
     * @return array<int, string>
     */
    public function forRows(Collection $rows): array
    {
        $open = $rows->whereNull('category');

        if ($open->isEmpty()) {
            return [];
        }

        $labels = LabelTicket::query()->whereIn('zammad_id', $open->pluck('zammad_id'))->get(['zammad_id', 'state', 'split', 'decision'])->keyBy('zammad_id');
        $intake = $this->settings->all()['intake'];
        $oldest = now()->subHours((int) config('labeling.intake_max_age_hours'));

        return $open->mapWithKeys(function (TrackedTicket $row) use ($labels, $intake, $oldest): array {
            $label = $labels->get($row->zammad_id);

            $status = match (true) {
                $label === null => $intake && $row->zammad_created_at?->greaterThanOrEqualTo($oldest) ? self::PENDING : self::NONE,
                $label->state === 'captured' => self::PENDING,
                $label->state === 'classified' && $label->isTest() && $label->decision === null => self::HIDDEN,
                default => self::NONE,
            };

            return [$row->zammad_id => $status];
        })->all();
    }
}

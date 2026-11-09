<?php

namespace App\Labeling;

use Illuminate\Support\Facades\DB;

/**
 * The values of the settings area on the page "Ticket-Klassifikation" (PROJ-78): what an admin saved, else the start value.
 */
class LabelingSettings
{
    /**
     * @return array{target: int, min_cases: int, min_rate: int, sample_share: int, confidence: float, examples: int, test_share: int, test_target: int, excluded_groups: string, excluded_senders: string}
     */
    public function all(): array
    {
        $saved = DB::table('labeling_settings')->pluck('value', 'key')->map(fn (string $value): mixed => json_decode($value, true))->all();
        $values = array_merge(config('labeling.defaults'), $saved);

        return [
            'target' => (int) $values['target'],
            'min_cases' => (int) $values['min_cases'],
            'min_rate' => (int) $values['min_rate'],
            'sample_share' => (int) $values['sample_share'],
            'confidence' => (float) $values['confidence'],
            'examples' => (int) $values['examples'],
            'test_share' => (int) $values['test_share'],
            'test_target' => (int) $values['test_target'],
            'excluded_groups' => (string) $values['excluded_groups'],
            'excluded_senders' => (string) $values['excluded_senders'],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach (array_intersect_key($values, config('labeling.defaults')) as $key => $value) {
            DB::table('labeling_settings')->upsert(
                [['key' => $key, 'value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]],
                ['key'],
                ['value', 'updated_at'],
            );
        }
    }

    /**
     * @return list<string>
     */
    public function excludedGroups(): array
    {
        return array_values(array_filter(array_map(trim(...), explode(',', $this->all()['excluded_groups']))));
    }
}

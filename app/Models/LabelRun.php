<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One run of the ticket tagging (PROJ-78): fetching, classifying or writing tags.
 */
#[Fillable(['kind', 'state', 'admin', 'options', 'cursor', 'total', 'done', 'failed', 'planned', 'cancel_requested', 'started_at', 'finished_at'])]
class LabelRun extends Model
{
    public const array KINDS = [
        'fetch' => 'Abruf',
        'classify' => 'Klassifikation',
        'reclassify' => 'Neu klassifiziert',
        'tags' => 'Tags schreiben',
    ];

    protected $table = 'labeling_runs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'cursor' => 'array',
            'cancel_requested' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return in_array($this->state, ['queued', 'running'], true);
    }
}

<?php

namespace App\Models;

use App\Labeling\LabelCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One proposal of the language model for a ticket (PROJ-78), kept with model, prompt version and the examples used.
 */
#[Fillable(['label_ticket_id', 'label_run_id', 'category', 'unclear', 'multiple_concerns', 'confidence', 'reason', 'evidence', 'queue', 'model', 'prompt_version', 'example_ids', 'input_tokens', 'output_tokens'])]
class LabelPrediction extends Model
{
    protected $table = 'labeling_predictions';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unclear' => 'boolean',
            'multiple_concerns' => 'boolean',
            'confidence' => 'float',
            'example_ids' => 'array',
            'reason' => 'encrypted',
            'evidence' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<LabelTicket, $this>
     */
    public function labelTicket(): BelongsTo
    {
        return $this->belongsTo(LabelTicket::class);
    }

    public function categoryEnum(): ?LabelCategory
    {
        return $this->category === null ? null : LabelCategory::tryFrom($this->category);
    }
}

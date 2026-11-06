<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One decision of an admin about a ticket (PROJ-78). The history stays; the decision in force is on the ticket.
 * "hit" is only set for random samples: it is what the hit rate of a category is calculated from.
 */
#[Fillable(['label_ticket_id', 'label_prediction_id', 'decision', 'origin', 'hit', 'reviewer'])]
class LabelReview extends Model
{
    protected $table = 'labeling_reviews';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['hit' => 'boolean'];
    }

    /**
     * @return BelongsTo<LabelPrediction, $this>
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(LabelPrediction::class, 'label_prediction_id');
    }

    /**
     * @return BelongsTo<LabelTicket, $this>
     */
    public function labelTicket(): BelongsTo
    {
        return $this->belongsTo(LabelTicket::class);
    }
}

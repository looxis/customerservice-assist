<?php

namespace App\Models;

use App\Labeling\LabelCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A Zammad ticket in the ticket tagging (PROJ-78): its first customer message (encrypted, emptied after the
 * retention period), the decision of an admin and the state of its tag in Zammad.
 */
#[Fillable(['zammad_id', 'number', 'group_name', 'article_id', 'text', 'zammad_created_at', 'source', 'split', 'state', 'existing_tag', 'problem', 'decision', 'decision_origin', 'decided_by', 'decided_at', 'tag_kind', 'tag_value', 'tag_written_at', 'tag_problem', 'last_activity_at'])]
class LabelTicket extends Model
{
    public const string NONE = 'keine';

    protected $table = 'labeling_tickets';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'text' => 'encrypted',
            'zammad_created_at' => 'datetime',
            'decided_at' => 'datetime',
            'tag_written_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<LabelPrediction, $this>
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(LabelPrediction::class);
    }

    /**
     * @return HasOne<LabelPrediction, $this>
     */
    public function latestPrediction(): HasOne
    {
        return $this->hasOne(LabelPrediction::class)->latestOfMany();
    }

    /**
     * @return HasMany<LabelReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(LabelReview::class);
    }

    public function isTest(): bool
    {
        return $this->split === 'test';
    }

    public function decidedCategory(): ?LabelCategory
    {
        return $this->decision === null || $this->decision === self::NONE ? null : LabelCategory::tryFrom($this->decision);
    }

    /**
     * The tag the ticket should carry in Zammad, or null for none. A ticket of the test set carries "test:".
     */
    public function desiredTag(): ?string
    {
        return $this->decidedCategory()?->tag($this->isTest());
    }

    /**
     * Whether Zammad still has to be told about the decision.
     */
    public function tagPending(): bool
    {
        return $this->decision !== null && $this->desiredTag() !== $this->tag_value;
    }

    /**
     * Tickets nobody has decided on yet.
     */
    public function scopeUndecided($query)
    {
        return $query->whereNull('decision');
    }
}

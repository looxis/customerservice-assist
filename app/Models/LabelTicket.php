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
#[Fillable(['zammad_id', 'number', 'group_name', 'article_id', 'text', 'subject', 'sender', 'zammad_created_at', 'source', 'split', 'state', 'existing_tag', 'problem', 'decision', 'decision_origin', 'decided_by', 'decided_at', 'tag_kind', 'tag_value', 'tag_written_at', 'tag_problem', 'last_activity_at'])]
class LabelTicket extends Model
{
    /** The decision of an admin who cannot place the ticket with certainty: no tag, no example, not in the hit rate. */
    public const string UNSURE = 'unsicher';

    public const string EXCLUDED = 'excluded';

    protected $table = 'labeling_tickets';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'text' => 'encrypted',
            'subject' => 'encrypted',
            'sender' => 'encrypted',
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

    /**
     * The message as the language model sees it: sender and subject, then the text.
     */
    public function classifierInput(int $maxTextLength): string
    {
        return collect([
            filled($this->sender) ? 'Absender: '.$this->sender : null,
            filled($this->subject) ? 'Betreff: '.$this->subject : null,
        ])->filter()->push("Erste Nachricht des Kunden:\n\n".mb_substr((string) $this->text, 0, $maxTextLength))->implode("\n");
    }

    /**
     * Removes everything but the Zammad ID and number, so that later retrievals skip the ticket.
     */
    public function exclude(string $admin): void
    {
        $this->predictions()->delete();
        $this->reviews()->delete();

        $this->update([
            'state' => self::EXCLUDED,
            'text' => null,
            'subject' => null,
            'sender' => null,
            'article_id' => null,
            'group_name' => null,
            'decision' => null,
            'decision_origin' => null,
            'decided_by' => $admin,
            'decided_at' => now(),
            'tag_kind' => null,
            'tag_value' => null,
            'tag_problem' => null,
            'problem' => 'Entfernt',
            'last_activity_at' => now(),
        ]);
    }

    public function isTest(): bool
    {
        return $this->split === 'test';
    }

    public function decidedCategory(): ?LabelCategory
    {
        return $this->decision === null ? null : LabelCategory::tryFrom($this->decision);
    }

    /**
     * The decision as the pages show it, null while nobody decided.
     */
    public function decisionLabel(): ?string
    {
        return match ($this->decision) {
            null => null,
            self::UNSURE => 'Unsicher, nicht verwendet',
            default => $this->decidedCategory()?->label() ?? $this->decision,
        };
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
     * Whether a new proposal differs from the decision and no admin has looked at it yet: the decision stays,
     * but its tag waits until the ticket left the queue "abweichung".
     */
    public function hasOpenDeviation(): bool
    {
        $prediction = $this->latestPrediction;

        return $prediction?->queue === 'abweichung' && ! $this->reviews->contains('label_prediction_id', $prediction->id);
    }

    /**
     * Tickets nobody has decided on yet.
     */
    public function scopeUndecided($query)
    {
        return $query->whereNull('decision');
    }
}

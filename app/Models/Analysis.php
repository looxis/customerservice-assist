<?php

namespace App\Models;

use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One analysis (PROJ-11): figures without customer data in their own columns,
 * everything with customer data encrypted in `content`, which is emptied after
 * the retention period or on deletion. Failed analyses have no content.
 */
#[Fillable(['uuid', 'ticket_number', 'scope_key', 'status', 'error', 'staff_name', 'test_until', 'customer_group', 'products', 'variant', 'category', 'assessment', 'confidence', 'actions', 'knowledge_ids', 'knowledge_fingerprints', 'provider', 'model', 'prompt_version', 'summary_prompt_version', 'knowledge_state', 'knowledge_characters', 'documents_retrieved', 'documents_omitted', 'passes', 'duration_ms', 'input_tokens', 'output_tokens', 'attempts', 'content', 'content_purged_at', 'content_deleted_at', 'content_deleted_by', 'feedback_level', 'feedback_suggested', 'feedback_by', 'feedback_at', 'feedback_comment', 'reply_edited_at', 'reply_copied_at', 'reply_copied_by', 'reply_copied_changed'])]
class Analysis extends Model
{
    /** @use HasFactory<AnalysisFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'test_until' => 'datetime',
            'products' => 'array',
            'actions' => 'array',
            'knowledge_ids' => 'array',
            'knowledge_fingerprints' => 'array',
            'content' => 'encrypted:array',
            'content_purged_at' => 'datetime',
            'content_deleted_at' => 'datetime',
            'feedback_at' => 'datetime',
            'feedback_comment' => 'encrypted',
            'reply_edited_at' => 'datetime',
            'reply_copied_at' => 'datetime',
            'reply_copied_changed' => 'boolean',
        ];
    }

    /**
     * @return HasMany<KnowledgeGap, $this>
     */
    public function gaps(): HasMany
    {
        return $this->hasMany(KnowledgeGap::class);
    }

    /**
     * Test runs (PROJ-32): analyses of a rewound ticket, failed ones included.
     *
     * @param  Builder<Analysis>  $query
     */
    #[Scope]
    protected function testRun(Builder $query): void
    {
        $query->where(fn (Builder $inner) => $inner->whereNotNull('test_until')->orWhere('scope_key', 'like', '%.stand-%'));
    }

    /**
     * @param  Builder<Analysis>  $query
     */
    #[Scope]
    protected function realRun(Builder $query): void
    {
        $query->whereNull('test_until')->where('scope_key', 'not like', '%.stand-%');
    }

    /**
     * The rule behind the success figure (PROJ-12): finished real analyses with a rating.
     *
     * @param  Builder<Analysis>  $query
     */
    #[Scope]
    protected function rated(Builder $query): void
    {
        $query->where('status', 'completed')->whereNull('test_until')->whereNotNull('feedback_level');
    }
}

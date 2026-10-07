<?php

namespace App\Models;

use Database\Factories\KnowledgeGapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reported gap in the knowledge base (PROJ-12). The free texts in
 * `content` (missing, solution, comment, discard reason) are encrypted;
 * the topic of a gap named by the AI is kept only as a hash to recognise
 * a second report.
 */
#[Fillable(['analysis_id', 'ticket_number', 'staff_name', 'customer_group', 'products', 'test_run', 'gap_topic_hash', 'content', 'status', 'resolved_by', 'resolved_at', 'knowledge_id', 'content_purged_at'])]
class KnowledgeGap extends Model
{
    /** @use HasFactory<KnowledgeGapFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'products' => 'array',
            'test_run' => 'boolean',
            'content' => 'encrypted:array',
            'resolved_at' => 'datetime',
            'content_purged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Analysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}

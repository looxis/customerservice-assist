<?php

namespace App\Models;

use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One analysis (PROJ-11): figures without customer data in their own columns,
 * everything with customer data encrypted in `content`, which is emptied after
 * the retention period or on deletion. Failed analyses have no content.
 */
#[Fillable(['uuid', 'ticket_number', 'scope_key', 'status', 'error', 'staff_name', 'test_until', 'customer_group', 'products', 'variant', 'category', 'assessment', 'confidence', 'actions', 'knowledge_ids', 'knowledge_fingerprints', 'provider', 'model', 'prompt_version', 'summary_prompt_version', 'knowledge_state', 'duration_ms', 'input_tokens', 'output_tokens', 'attempts', 'content', 'content_purged_at', 'content_deleted_at', 'content_deleted_by'])]
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
        ];
    }
}

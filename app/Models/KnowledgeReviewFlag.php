<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A staff report "possibly outdated, please check" about one knowledge document (PROJ-56). The comment is
 * encrypted and deleted with the other customer-related content after the retention period.
 */
#[Fillable(['document_id', 'document_fingerprint', 'analysis_uuid', 'ticket_number', 'staff_name', 'reason', 'comment', 'status', 'resolved_by', 'resolved_at'])]
class KnowledgeReviewFlag extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'comment' => 'encrypted',
            'resolved_at' => 'datetime',
        ];
    }
}

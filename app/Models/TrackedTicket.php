<?php

namespace App\Models;

use App\Analysis\ProcessingState;
use App\Zammad\SyncStatus;
use Database\Factories\TrackedTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A Zammad ticket known to CSA (PROJ-66), one row per ticket. Zammad leads for the
 * ticket data, CSA for channel, category and processing state. Holds no customer
 * name and no message text; the subject is encrypted.
 */
#[Fillable(['zammad_id', 'number', 'title', 'state', 'is_closed', 'pending_until', 'group_name', 'owner_zammad_id', 'owner_name', 'customer_zammad_id', 'organization_zammad_id', 'zammad_created_at', 'zammad_updated_at', 'last_activity_at', 'last_contact_customer_at', 'last_customer_article_id', 'last_customer_article_at', 'reopened_at', 'sync_status', 'sync_problem_since', 'synced_at', 'channel', 'category', 'category_confirmed', 'processing_state', 'processed_article_id'])]
class TrackedTicket extends Model
{
    /** @use HasFactory<TrackedTicketFactory> */
    use HasFactory;

    /**
     * With milliseconds: a change within the same second as the last read must still count as a change.
     */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'encrypted',
            'is_closed' => 'boolean',
            'category_confirmed' => 'boolean',
            'pending_until' => 'datetime',
            'zammad_created_at' => 'datetime',
            'zammad_updated_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_contact_customer_at' => 'datetime',
            'last_customer_article_at' => 'datetime',
            'reopened_at' => 'datetime',
            'sync_status' => SyncStatus::class,
            'sync_problem_since' => 'datetime',
            'synced_at' => 'datetime',
            'processing_state' => ProcessingState::class,
        ];
    }
}

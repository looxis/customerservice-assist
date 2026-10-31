<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One accepted webhook call from Zammad (PROJ-66), to spot repeats. Holds no payload.
 */
#[Fillable(['delivery_id', 'trigger_name', 'zammad_ticket_id', 'status', 'error_code', 'received_at', 'processed_at'])]
class ZammadWebhookDelivery extends Model
{
    public const string RECEIVED = 'received';

    public const string PROCESSED = 'processed';

    public const string FAILED = 'failed';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'zammad_ticket_id' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}

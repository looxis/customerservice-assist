<?php

namespace App\Models;

use App\Returns\ReturnInstructions;
use Database\Factories\ReturnLabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A DHL return label (PROJ-64): who ordered it for which order and ticket, the shipment number DHL gave it,
 * where its PDF lies and whether it was sent to the customer. Holds no address and no e-mail address.
 */
#[Fillable(['ticket_number', 'order_number', 'eocs_order_id', 'shipment_no', 'status', 'fingerprint', 'created_by', 'file_path', 'file_sha256', 'error', 'sent_at', 'sent_by', 'sent_ticket_number', 'sent_as_new_ticket'])]
class ReturnLabel extends Model
{
    /** @use HasFactory<ReturnLabelFactory> */
    use HasFactory, HasUuids;

    public const string CREATING = 'creating';

    public const string CREATED = 'created';

    public const string FAILED = 'failed';

    public const string OUTCOME_UNKNOWN = 'outcome_unknown';

    public const string INVALID = 'invalid';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eocs_order_id' => 'integer',
            'sent_at' => 'datetime',
            'sent_as_new_ticket' => 'boolean',
        ];
    }

    public function hasFile(): bool
    {
        return $this->status === self::CREATED && $this->file_path !== null;
    }

    /**
     * What the page needs to show the label and to continue with it.
     *
     * @return array<string, mixed>
     */
    public function payload(bool $reused = false): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'reused' => $reused,
            'shipment_no' => $this->shipment_no,
            'message' => $this->error,
            'download_url' => $this->hasFile() ? route('dhl-return.download', ['label' => $this->id, 'ticket' => $this->ticket_number]) : null,
            'filename' => ReturnInstructions::filename($this->order_number),
            'instructions' => $this->hasFile() ? ReturnInstructions::mail((string) $this->shipment_no, $this->order_number) : null,
            'block' => $this->hasFile() ? ReturnInstructions::block((string) $this->shipment_no) : null,
            'sent' => $this->sent_at === null ? null : ['by' => $this->sent_by, 'ticket_number' => $this->sent_ticket_number, 'new_ticket' => $this->sent_as_new_ticket],
        ];
    }
}

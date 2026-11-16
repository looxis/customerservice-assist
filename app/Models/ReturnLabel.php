<?php

namespace App\Models;

use App\Returns\ReturnCountry;
use App\Returns\ReturnInstructions;
use App\Returns\ReturnLanguage;
use Database\Factories\ReturnLabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A DHL return label (PROJ-64): who ordered it for which order and ticket, the shipment number DHL gave it,
 * where its PDF lies and whether it was sent to the customer. Holds no address and no e-mail address, only the
 * country of the sender.
 */
#[Fillable(['ticket_number', 'order_number', 'eocs_order_id', 'country', 'shipment_no', 'status', 'fingerprint', 'created_by', 'file_path', 'file_sha256', 'error', 'send_started_at', 'send_started_target', 'sent_at', 'sent_by', 'sent_ticket_number', 'sent_as_new_ticket'])]
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
            'send_started_at' => 'datetime',
            'sent_at' => 'datetime',
            'sent_as_new_ticket' => 'boolean',
        ];
    }

    public function hasFile(): bool
    {
        return $this->status === self::CREATED && $this->file_path !== null;
    }

    /**
     * The country of the sender; labels of a country that is no longer offered count as German.
     */
    public function returnCountry(): ReturnCountry
    {
        return ReturnCountry::tryFrom((string) $this->country) ?? ReturnCountry::Germany;
    }

    /**
     * What the page needs to show the label and to continue with it. The texts for the customer come in every
     * language; "block" and "instructions" are the ones in the given language, or in the one usual for the country.
     *
     * @return array<string, mixed>
     */
    public function payload(bool $reused = false, ?ReturnLanguage $language = null): array
    {
        $country = $this->returnCountry();
        $language ??= $country->language();
        $number = (string) $this->shipment_no;
        $texts = [];

        if ($this->hasFile()) {
            foreach (ReturnLanguage::cases() as $each) {
                $texts[$each->value] = [
                    'block' => ReturnInstructions::block($number, $each, $country),
                    'mail' => ReturnInstructions::mail($number, $this->order_number, $each, $country),
                    'subject' => ReturnInstructions::subject($this->order_number, $each),
                ];
            }
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'reused' => $reused,
            'shipment_no' => $this->shipment_no,
            'message' => $this->error,
            'download_url' => $this->hasFile() ? route('dhl-return.download', ['label' => $this->id, 'ticket' => $this->ticket_number]) : null,
            'filename' => ReturnInstructions::filename($this->order_number),
            'country' => $country->value,
            'country_name' => $country->label(),
            'language' => $language->value,
            'texts' => $texts,
            'instructions' => $texts[$language->value]['mail'] ?? null,
            'block' => $texts[$language->value]['block'] ?? null,
            'sent' => $this->sent_at === null ? null : ['by' => $this->sent_by, 'ticket_number' => $this->sent_ticket_number, 'new_ticket' => $this->sent_as_new_ticket],
        ];
    }
}

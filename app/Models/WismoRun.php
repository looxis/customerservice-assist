<?php

namespace App\Models;

use Database\Factories\WismoRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The log of the automatic WISMO reply to one ticket (PROJ-82): what came out of it and why. The texts that went to the
 * customer are encrypted and emptied after 90 days.
 */
#[Fillable(['zammad_id', 'ticket_number', 'mode', 'result', 'reason', 'attempts', 'order_number', 'carrier', 'tracking_number', 'delivery_problem', 'delivery_problem_reason', 'pickup_address_used', 'stuck_workdays', 'prompt_version', 'model', 'input_tokens', 'output_tokens', 'duration_ms', 'reply_text', 'reply_text_de', 'sending_at', 'processed_at'])]
class WismoRun extends Model
{
    /** @use HasFactory<WismoRunFactory> */
    use HasFactory;

    public const string WORKING = 'working';

    public const string RETRY = 'retry';

    public const string SENDING = 'sending';

    public const string SENT = 'sent';

    public const string DRY_RUN = 'dry_run';

    public const string NOTE = 'note';

    public const string ERROR = 'error';

    public const string SKIPPED = 'skipped';

    /** How long the texts stay readable. */
    public const int TEXT_DAYS = 90;

    /**
     * @return array<string, string>
     */
    public static function results(): array
    {
        return [
            self::SENT => 'Gesendet',
            self::DRY_RUN => 'Trockenlauf',
            self::NOTE => 'Notiz geschrieben',
            self::ERROR => 'Fehler',
            self::SKIPPED => 'Übersprungen',
            self::WORKING => 'In Arbeit',
            self::RETRY => 'Wird wiederholt',
            self::SENDING => 'Versand unklar',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_problem' => 'boolean',
            'pickup_address_used' => 'boolean',
            'reply_text' => 'encrypted',
            'reply_text_de' => 'encrypted',
            'sending_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function label(): string
    {
        return self::results()[$this->result] ?? $this->result;
    }
}

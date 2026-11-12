<?php

namespace App\Models;

use Database\Factories\ClaimReplyRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The log of the automatic first reply to one complaint ticket (PROJ-85): what came out of it and why. The texts that
 * went to the customer are encrypted and emptied after 90 days.
 */
#[Fillable(['zammad_id', 'ticket_number', 'mode', 'result', 'reason', 'attempts', 'order_number', 'days_since_shipped', 'photo_count', 'scenario', 'prompt_version', 'model', 'input_tokens', 'output_tokens', 'duration_ms', 'reply_text', 'reply_text_de', 'sending_at', 'processed_at'])]
class ClaimReplyRun extends Model
{
    /** @use HasFactory<ClaimReplyRunFactory> */
    use HasFactory;

    public const string WORKING = 'working';

    public const string RETRY = 'retry';

    public const string SENDING = 'sending';

    public const string SENT = 'sent';

    public const string DRY_RUN = 'dry_run';

    public const string NOTE = 'note';

    public const string ERROR = 'error';

    public const string SKIPPED = 'skipped';

    public const string NO_PHOTO = 'no_photo';

    public const string NO_DESCRIPTION = 'photo_no_description';

    public const string CONFIRMATION = 'photo_with_description';

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
    public static function scenarios(): array
    {
        return [
            self::NO_PHOTO => 'Fotos bitte',
            self::NO_DESCRIPTION => 'Beschreibung bitte',
            self::CONFIRMATION => 'Eingangsbestätigung',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function scenarioLabel(): string
    {
        return $this->scenario === null ? '–' : (self::scenarios()[$this->scenario] ?? $this->scenario);
    }
}

<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One run of a Zammad sync command (PROJ-66). The newest covered_until of the
 * "recent" runs is the persistent progress marker.
 */
#[Fillable(['type', 'started_at', 'finished_at', 'status', 'covered_until', 'tickets_seen', 'tickets_failed', 'error_code'])]
class ZammadSyncRun extends Model
{
    public const string RECENT = 'recent';

    public const string RECONCILE = 'reconcile';

    public const string IMPORT = 'import';

    public const string SUCCEEDED = 'succeeded';

    public const string FAILED = 'failed';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'covered_until' => 'datetime',
            'tickets_seen' => 'integer',
            'tickets_failed' => 'integer',
        ];
    }

    public static function begin(string $type): self
    {
        return self::query()->create(['type' => $type, 'started_at' => now()]);
    }

    public function finish(string $status, ?string $errorCode = null): void
    {
        $this->update(['status' => $status, 'error_code' => $errorCode, 'finished_at' => now()]);
    }

    /**
     * Up to where all changes are known to be complete; null before the first complete window.
     */
    public static function marker(): ?CarbonImmutable
    {
        $marker = self::query()->where('type', self::RECENT)->max('covered_until');

        return $marker === null ? null : CarbonImmutable::parse($marker);
    }
}

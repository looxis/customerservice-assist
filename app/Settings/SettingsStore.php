<?php

namespace App\Settings;

use App\Models\Setting;
use App\Models\SettingVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reads and saves the values of the settings groups (PROJ-27). The database holds only what differs from the
 * default of the app; every change is appended to a history that can be restored from. If the database cannot be
 * read, or a value is unusable, the default applies: the mail view never works with empty rules.
 */
class SettingsStore
{
    public const string SAVED = 'gespeichert';

    public const string RESTORED = 'wiederhergestellt';

    public const string DEFAULT = 'standard';

    public const string RELEASED = 'freigegeben';

    /**
     * @var array<string, array{value: mixed, version: int, by: string|null, at: string|null}>|null
     */
    private ?array $rows = null;

    /**
     * The value in force: the saved one, or the default.
     */
    public function value(string $key): mixed
    {
        $group = $this->group($key);
        $row = $this->rows()[$key] ?? null;

        if ($row === null) {
            return $group->default();
        }

        [$value] = $group->normalize($row['value']);

        return $group->errors($value) === [] ? $value : $group->default();
    }

    /**
     * The version of the saved value (0 while the default applies and nothing was ever saved).
     */
    public function version(string $key): int
    {
        return (int) ($this->rows()[$key]['version'] ?? SettingVersion::query()->where('group_key', $key)->max('version') ?? 0);
    }

    public function isDefault(string $key): bool
    {
        return ! isset($this->rows()[$key]);
    }

    /**
     * Who saved the group last and when, or null when it never changed.
     *
     * @return array{by: string, at: string}|null
     */
    public function lastChange(string $key): ?array
    {
        $last = SettingVersion::query()->where('group_key', $key)->orderByDesc('version')->first();

        return $last === null ? null : ['by' => $last->changed_by, 'at' => $last->created_at->toIso8601String()];
    }

    /**
     * Saves a new value (already normalized and checked). A value equal to the default removes the stored value,
     * so later defaults of the app reach the group again.
     *
     * @return int The new version.
     */
    public function save(string $key, mixed $value, string $by, string $kind = self::SAVED): int
    {
        $group = $this->group($key);

        return DB::transaction(function () use ($group, $key, $value, $by, $kind): int {
            $version = ((int) SettingVersion::query()->where('group_key', $key)->lockForUpdate()->max('version')) + 1;
            $isDefault = $value === $group->default() || json_encode($value) === json_encode($group->default());

            SettingVersion::query()->create(['group_key' => $key, 'version' => $version, 'value' => $value, 'kind' => $isDefault && $kind === self::SAVED ? self::DEFAULT : $kind, 'changed_by' => $by]);

            if ($isDefault) {
                Setting::query()->where('group_key', $key)->delete();
            } else {
                Setting::query()->updateOrCreate(['group_key' => $key], ['value' => $value, 'version' => $version, 'changed_by' => $by]);
            }

            $this->rows = null;

            return $version;
        });
    }

    /**
     * Puts an earlier version back as a new version.
     */
    public function restore(string $key, int $version, string $by): bool
    {
        $old = SettingVersion::query()->where('group_key', $key)->where('version', $version)->first();

        if ($old === null) {
            return false;
        }

        $this->save($key, $old->value, $by, self::RESTORED);

        return true;
    }

    /**
     * Goes back to the default of the app as a new version.
     */
    public function reset(string $key, string $by): int
    {
        return $this->save($key, $this->group($key)->default(), $by, self::DEFAULT);
    }

    /**
     * The versions of a group, newest first.
     *
     * @return Collection<int, SettingVersion>
     */
    public function history(string $key, int $limit = 20): Collection
    {
        return SettingVersion::query()->where('group_key', $key)->orderByDesc('version')->limit($limit)->get();
    }

    /**
     * @return array<string, array{value: mixed, version: int, by: string|null, at: string|null}>
     */
    private function rows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        try {
            $this->rows = Setting::query()->get()->mapWithKeys(fn (Setting $setting): array => [$setting->group_key => ['value' => $setting->value, 'version' => $setting->version, 'by' => $setting->changed_by, 'at' => $setting->updated_at?->toIso8601String()]])->all();
        } catch (QueryException $exception) {
            Log::warning('Settings could not be read, defaults apply', ['error' => $exception::class]);
            $this->rows = [];
        }

        return $this->rows;
    }

    private function group(string $key): SettingGroup
    {
        return SettingsRegistry::group($key) ?? throw new \InvalidArgumentException("Unknown settings group {$key}");
    }
}

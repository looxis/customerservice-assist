<?php

namespace App\Http\Requests;

use App\Settings\SettingGroup;
use App\Settings\SettingsRegistry;
use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The input of the page "Einstellungen > Mail-Anzeige" (PROJ-27): only admins may send it. Every group
 * is cleaned and checked here; nothing is saved when one of them is unusable.
 */
class MailSettingsRequest extends FormRequest
{
    public const string PAGE = 'mail';

    /**
     * @var array<string, mixed>
     */
    private array $drafts = [];

    /**
     * @var list<string>
     */
    private array $notes = [];

    public function authorize(): bool
    {
        return app(TestMode::class)->isAvailable($this);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'versions' => ['sometimes', 'array'],
            'versions.*' => ['integer', 'min:0'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (SettingsRegistry::forPage(self::PAGE) as $key => $group) {
                if (! array_key_exists($key, (array) $this->input('settings'))) {
                    continue;
                }

                [$value, $notes] = $group->normalize($this->input("settings.{$key}"));

                foreach ($group->errors($value) as $message) {
                    $validator->errors()->add("settings.{$key}", $message);
                }

                $this->drafts[$key] = $value;
                $this->notes = [...$this->notes, ...$notes];
            }
        }];
    }

    /**
     * The cleaned values of the groups that were sent, keyed by group.
     *
     * @return array<string, mixed>
     */
    public function drafts(): array
    {
        return $this->drafts;
    }

    /**
     * What was cleaned away on the way (e.g. duplicates).
     *
     * @return list<string>
     */
    public function notes(): array
    {
        return $this->notes;
    }

    public function openedVersion(string $key): int
    {
        return (int) $this->input("versions.{$key}", 0);
    }

    protected function getRedirectUrl(): string
    {
        return route('settings.mail');
    }

    public function group(string $key): ?SettingGroup
    {
        return SettingsRegistry::group($key);
    }
}

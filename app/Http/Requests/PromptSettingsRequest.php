<?php

namespace App\Http\Requests;

use App\Settings\ModelCatalog;
use App\Settings\SettingGroup;
use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use Illuminate\Validation\Validator;

/**
 * The input of the page "Einstellungen > Prompts und Modell" (PROJ-31). Besides the usual checks, a changed model
 * must answer a tiny test call before anything is saved.
 */
class PromptSettingsRequest extends MailSettingsRequest
{
    public const string PAGE = 'prompts';

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->drafts() as $key => $value) {
                    $group = SettingsRegistry::group($key);

                    if ($group?->kind !== SettingGroup::MODEL || $value === app(SettingsStore::class)->value($key)) {
                        continue;
                    }

                    $problem = app(ModelCatalog::class)->check((string) $value);

                    if ($problem !== null) {
                        $validator->errors()->add("settings.{$key}", $problem);
                    }
                }
            },
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Settings\KnowledgeValueRules;
use Illuminate\Validation\Validator;

/**
 * The input of the page "Einstellungen > Kundengruppen und Werte" (PROJ-50). Besides the checks of every group,
 * the groups are checked against each other and against the documents before anything is saved.
 */
class ValuesSettingsRequest extends MailSettingsRequest
{
    public const string PAGE = 'values';

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

                foreach (KnowledgeValueRules::crossErrors($this->drafts()) as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add("settings.{$key}", $message);
                    }
                }
            },
        ];
    }
}

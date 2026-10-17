<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * "Mit Ticket ausprobieren" (PROJ-27): the same input as saving, plus the number of a ticket. Saves nothing.
 */
class TryMailSettingsRequest extends MailSettingsRequest
{
    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'ticket' => ['required', 'string', 'regex:/^[0-9]{1,20}$/']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['ticket.required' => 'Bitte eine Ticketnummer eingeben.', 'ticket.regex' => 'Eine Ticketnummer besteht nur aus Ziffern.'];
    }
}

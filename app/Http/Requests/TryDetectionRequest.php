<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * "Erkennung ausprobieren" (PROJ-49): the same input as saving, plus a text. Saves nothing.
 */
class TryDetectionRequest extends DetectionSettingsRequest
{
    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'sample' => ['required', 'string', 'max:10000']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['sample.required' => 'Bitte einen Text zum Ausprobieren einfügen.', 'sample.max' => 'Der Text ist zu lang (höchstens 10.000 Zeichen).'];
    }
}

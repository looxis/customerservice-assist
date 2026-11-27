<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The case patterns that fit the scope of the form (PROJ-103): customer group, channel, products, the chosen guided
 * form and the category of the ticket. `gewaehlt` names the patterns the employee has ticked, so the answer can say
 * why one of them no longer fits.
 */
class CasePatternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'kundengruppe' => ['nullable', 'string', 'max:60'],
            'verkaufskanal' => ['nullable', 'string', 'max:60'],
            'produkte' => ['sometimes', 'array', 'max:10'],
            'produkte.*' => ['string', 'max:100'],
            'formular' => ['nullable', 'string', 'max:40'],
            'kategorie' => ['nullable', 'string', 'max:40'],
            'gewaehlt' => ['sometimes', 'array', 'max:20'],
            'gewaehlt.*' => ['string', 'regex:/^[A-Z]+(?:-[A-Z]+)*-\d{3}$/'],
        ];
    }
}

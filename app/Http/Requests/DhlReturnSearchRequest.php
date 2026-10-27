<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The search of the page "DHL Retoure" (PROJ-64): a ticket number ("Ticket#2137942" is fine) or an order number.
 */
class DhlReturnSearchRequest extends FormRequest
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
            'suche' => ['nullable', 'string', 'max:100', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'suche.max' => 'Bitte eine Ticketnummer oder eine Bestellnummer eingeben.',
            'suche.regex' => 'Bitte eine Ticketnummer oder eine Bestellnummer eingeben.',
        ];
    }
}

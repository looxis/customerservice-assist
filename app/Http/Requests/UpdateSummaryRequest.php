<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSummaryRequest extends FormRequest
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
            'zusammenfassung' => ['required', 'string', 'max:20000'],
            'bestellungen' => ['sometimes', 'array', 'max:10'],
            'bestellungen.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['zusammenfassung.required' => 'Die Zusammenfassung darf nicht leer sein.'];
    }

    protected function getRedirectUrl(): string
    {
        return route('tickets.show', ['number' => $this->route('number'), 'bestellungen' => (array) $this->input('bestellungen', [])]).'#zusammenfassung';
    }
}

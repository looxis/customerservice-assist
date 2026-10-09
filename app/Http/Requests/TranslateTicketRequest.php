<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nachricht' => ['nullable', 'integer', 'min:1'],
            'stand' => ['nullable', 'integer'],
            'bestellungen' => ['sometimes', 'array', 'max:10'],
            'bestellungen.*' => ['string', 'max:40'],
        ];
    }
}

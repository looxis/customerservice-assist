<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Saves an offer of the offer calculator (PROJ-34): customer data and the items with their prices.
 */
class SaveQuoteOfferRequest extends FormRequest
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
            'company' => ['nullable', 'string', 'max:200'],
            'contact' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'max:200'],
            'street' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:200'],
            'note' => ['nullable', 'string', 'max:4000'],
            'ticket_number' => ['nullable', 'string', 'regex:/^[0-9]{1,20}$/'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.sku' => ['required', 'string', 'max:40'],
            'items.*.name' => ['required', 'string', 'max:300'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.discount_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.unit_gross' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'items.*.vat' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Das Angebot enthält noch keine Artikel.',
            'items.min' => 'Das Angebot enthält noch keine Artikel.',
        ];
    }
}

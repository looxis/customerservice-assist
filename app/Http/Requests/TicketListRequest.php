<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TicketListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Values of the wrong shape (lists, over-long texts, a page that is no number) are
     * dropped before validation, so a hand-changed address shows the list without them.
     */
    protected function prepareForValidation(): void
    {
        $clean = array_filter($this->query(), fn (mixed $value, string $key): bool => is_string($value)
            && mb_strlen($value) <= ($key === 'page' ? 5 : 120)
            && ($key !== 'page' || preg_match('/^[1-9]\d*$/', $value) === 1), ARRAY_FILTER_USE_BOTH);

        $this->query->replace($clean);
    }

    /**
     * Shape only; values that do not fit are dropped when the filter is built.
     *
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $text = ['nullable', 'string', 'max:120'];

        return [
            'ansicht' => $text,
            'status' => $text,
            'mitarbeiter' => $text,
            'kanal' => $text,
            'klassifikation' => $text,
            'csa' => $text,
            'sync' => $text,
            'sort' => $text,
            'richtung' => $text,
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

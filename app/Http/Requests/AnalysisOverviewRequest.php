<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnalysisOverviewRequest extends FormRequest
{
    /**
     * Only admins see the analysis overview.
     */
    public function authorize(TestMode $testMode): bool
    {
        return $testMode->isAvailable($this);
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
            'tage' => ['nullable', 'string', 'max:3'],
            'von' => ['nullable', 'string', 'max:10'],
            'bis' => ['nullable', 'string', 'max:10'],
            'kundengruppe' => $text,
            'produkt' => $text,
            'kategorie' => $text,
            'feedback' => $text,
            'kopiert' => $text,
            'mitarbeiter' => $text,
            'modell' => $text,
            'prompt' => $text,
            'router' => $text,
            'testlaeufe' => ['nullable', 'string'],
            'fehlgeschlagen' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

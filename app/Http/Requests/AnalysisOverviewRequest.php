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
            'testlaeufe' => ['nullable', 'string', 'max:1'],
            'fehlgeschlagen' => ['nullable', 'string', 'max:1'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}

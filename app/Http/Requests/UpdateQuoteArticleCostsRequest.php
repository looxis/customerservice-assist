<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Changes the internal costs of one article (PROJ-34). Only admins; the page shows the costs to everyone.
 */
class UpdateQuoteArticleCostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = app(StaffDirectory::class);

        return $staff->isAdmin($staff->current($this));
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'raw_articles' => ['present', 'array', 'max:30'],
            'raw_articles.*.id' => ['nullable', 'string', 'max:40'],
            'raw_articles.*.qty' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'raw_articles.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:100000'],
            'machine_minutes' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'labor_minutes' => ['required', 'numeric', 'min:0', 'max:100000'],
            'hour_rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'machine_rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'pack_cost' => ['required', 'numeric', 'min:0', 'max:100000'],
            'ship_cost' => ['required', 'numeric', 'min:0', 'max:100000'],
            'other_cost' => ['required', 'numeric', 'min:0', 'max:100000'],
            'target_margin' => ['required', 'numeric', 'min:0', 'max:90'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'raw_articles.*.unit_cost.required' => 'Bitte bei jedem Grundartikel den Einstandspreis eintragen.',
            'raw_articles.*.qty.required' => 'Bitte bei jedem Grundartikel die Menge eintragen.',
        ];
    }
}

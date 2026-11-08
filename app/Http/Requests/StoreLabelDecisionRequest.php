<?php

namespace App\Http\Requests;

use App\Labeling\LabelCategory;
use App\Models\LabelTicket;
use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabelDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(TestMode::class)->isAvailable($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in([...array_column(LabelCategory::cases(), 'value'), LabelTicket::UNSURE])],
            'queue' => ['nullable', 'string', 'max:20'],
        ];
    }
}

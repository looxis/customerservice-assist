<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLabelSettingsRequest extends FormRequest
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
            'intake' => ['sometimes', 'boolean'],
            'target' => ['required', 'integer', 'min:1', 'max:5000'],
            'min_cases' => ['required', 'integer', 'min:1', 'max:5000'],
            'min_rate' => ['required', 'integer', 'min:50', 'max:100'],
            'sample_share' => ['required', 'integer', 'min:1', 'max:100'],
            'confidence' => ['required', 'numeric', 'min:0', 'max:1'],
            'examples' => ['required', 'integer', 'min:0', 'max:20'],
            'test_target' => ['required', 'integer', 'min:1', 'max:5000'],
            'excluded_groups' => ['nullable', 'string', 'max:500'],
            'excluded_senders' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

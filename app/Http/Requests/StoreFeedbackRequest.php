<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackRequest extends FormRequest
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
            'level' => ['required', 'string', Rule::in(array_keys(config('analysis.feedback_levels')))],
            'suggested' => ['nullable', 'string', Rule::in(array_keys(config('analysis.feedback_levels')))],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

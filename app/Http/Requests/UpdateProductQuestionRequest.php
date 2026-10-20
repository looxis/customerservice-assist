<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductQuestionRequest extends FormRequest
{
    /**
     * Only admins work through the product questions.
     */
    public function authorize(TestMode $testMode): bool
    {
        return $testMode->isAvailable($this);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'product_key' => ['present', 'nullable', 'string', 'max:80'],
            'topic' => ['required', 'string', 'max:80'],
            'status' => ['required', 'string', Rule::in(['open', 'ignored', 'done'])],
        ];
    }
}

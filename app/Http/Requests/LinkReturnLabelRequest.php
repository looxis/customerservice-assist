<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ties a return label ordered after the proposal to that proposal (PROJ-64), so that it is still there after reloading.
 */
class LinkReturnLabelRequest extends FormRequest
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
            'label' => ['required', 'uuid'],
        ];
    }
}

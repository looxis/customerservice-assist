<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The variants of one chosen product (PROJ-54), asked by its SKU. Whether the SKU is a number EOCS can know
 * is answered by the controller, not by an error: the form shows a hint instead of failing.
 */
class ProductVariantsRequest extends FormRequest
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
            'sku' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['sku' => 'SKU'];
    }
}

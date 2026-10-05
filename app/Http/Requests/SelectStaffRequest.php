<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectStaffRequest extends FormRequest
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
            'name' => ['required', 'string', Rule::in(app(StaffDirectory::class)->names())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte wähle einen Namen aus der Liste.',
            'name.in' => 'Dieser Name steht nicht auf der Liste.',
        ];
    }
}

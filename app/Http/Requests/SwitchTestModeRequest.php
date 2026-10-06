<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SwitchTestModeRequest extends FormRequest
{
    /**
     * Only admins may switch the test mode.
     */
    public function authorize(TestMode $testMode): bool
    {
        return $testMode->isAvailable($this);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'aktiv' => ['required', 'boolean'],
        ];
    }
}

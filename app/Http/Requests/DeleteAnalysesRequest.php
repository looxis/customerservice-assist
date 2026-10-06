<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAnalysesRequest extends FormRequest
{
    /**
     * Only admins may delete the analyses of a ticket.
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
        return [];
    }
}

<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;

class ExcludeLabelTicketRequest extends FormRequest
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
            'delete_in_zammad' => ['nullable', 'boolean'],
            'queue' => ['nullable', 'string', 'max:20'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Closes a ticket without a reply (PROJ-68). Only who has a personal Zammad token, because the change is made in
 * their name; not while a rewound ticket is shown in the test mode.
 */
class CloseTicketRequest extends FormRequest
{
    public function authorize(StaffDirectory $staff): bool
    {
        return $staff->zammadToken($staff->current($this)) !== null && ! $this->filled('stand');
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'grund' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['grund.max' => 'Der Grund darf höchstens 2000 Zeichen lang sein.'];
    }
}

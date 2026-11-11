<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Assigns a ticket or takes the assignment back (PROJ-81). Only who has a personal Zammad token, because the change
 * is made in their name; not while a rewound ticket is shown in the test mode.
 */
class AssignTicketRequest extends FormRequest
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
        $rules = ['notiz' => ['nullable', 'string', 'max:2000']];

        if ($this->routeIs('tickets.assign')) {
            $rules['mitarbeiter'] = ['required', 'string', Rule::in(app(StaffDirectory::class)->assignableNames())];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'notiz.max' => 'Die Notiz darf höchstens 2000 Zeichen lang sein.',
            'mitarbeiter.required' => 'Bitte einen Mitarbeiter auswählen.',
            'mitarbeiter.in' => 'Dieser Mitarbeiter kann nicht zugewiesen werden.',
        ];
    }
}

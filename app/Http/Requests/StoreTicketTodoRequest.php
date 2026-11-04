<?php

namespace App\Http\Requests;

use App\Todo\TodoRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A ToDo created at a ticket (PROJ-70). The token of the open dialog keeps a double click from creating it twice.
 */
class StoreTicketTodoRequest extends FormRequest
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
        return [...TodoRules::fields(), 'request_token' => ['nullable', 'string', 'max:60']];
    }

    public function messages(): array
    {
        return TodoRules::messages();
    }
}

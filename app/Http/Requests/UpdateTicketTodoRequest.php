<?php

namespace App\Http\Requests;

use App\Todo\TodoRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Change a ToDo (PROJ-70): text, assignee, due date, kind, notification.
 */
class UpdateTicketTodoRequest extends FormRequest
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
        return TodoRules::fields(keepDue: true);
    }

    public function messages(): array
    {
        return TodoRules::messages();
    }
}

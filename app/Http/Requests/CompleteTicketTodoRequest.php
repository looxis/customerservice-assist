<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Finish a ToDo (PROJ-70); "without_notification" skips the message to the customer.
 */
class CompleteTicketTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['without_notification' => ['nullable', 'boolean']];
    }
}

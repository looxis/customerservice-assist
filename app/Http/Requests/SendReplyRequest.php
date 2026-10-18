<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Antwort versenden" (PROJ-19). The recipient is not part of it: it is read from the ticket on the server.
 */
class SendReplyRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:300', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
            'text' => ['required', 'string', 'max:'.config('analysis.max_reply_length')],
            'signature' => ['nullable', 'string', 'max:600'],
            'after' => ['required', Rule::in(['close', 'reminder'])],
            'days' => ['required_if:after,reminder', 'nullable', 'integer', Rule::in([1, 3, 7])],
            'confirm_newer' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Bitte einen Betreff eingeben.',
            'subject.regex' => 'Der Betreff darf keine Zeilenumbrüche oder Steuerzeichen enthalten.',
            'text.required' => 'Der Antworttext ist leer.',
            'days.required_if' => 'Bitte 1, 3 oder 7 Tage wählen.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Senden" on the page "DHL Retoure" (PROJ-64): the label goes either as a reply to a ticket or in a new ticket to
 * the address typed in. The subject is built on the server.
 */
class SendReturnLabelRequest extends FormRequest
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
            'mode' => ['required', Rule::in(['reply', 'new'])],
            'ticket_number' => ['required_if:mode,reply', 'nullable', 'string', 'regex:/^\d{1,20}$/'],
            'email' => ['required_if:mode,new', 'nullable', 'string', 'max:200', 'email:rfc'],
            'text' => ['required', 'string', 'max:'.config('analysis.max_reply_length')],
            'signature' => ['nullable', 'string', 'max:600'],
            'after' => ['required', Rule::in(['close', 'reminder'])],
            'days' => ['required_if:after,reminder', 'nullable', 'integer', Rule::in([1, 3, 7])],
            'seen_article' => ['nullable', 'integer', 'min:0'],
            'confirm_newer' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Bitte wählen: Antwort auf ein Ticket oder neues Ticket.',
            'ticket_number.required_if' => 'Bitte die Ticketnummer eingeben.',
            'ticket_number.regex' => 'Die Ticketnummer ist ungültig.',
            'email.required_if' => 'Bitte die E-Mail-Adresse des Kunden eingeben.',
            'email.email' => 'Die E-Mail-Adresse ist ungültig.',
            'text.required' => 'Der Text ist leer.',
            'days.required_if' => 'Bitte 1, 3 oder 7 Tage wählen.',
        ];
    }
}

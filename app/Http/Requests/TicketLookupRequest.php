<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The ticket input accepts what Zammad's copy button produces
 * ("Ticket#2137942") and reduces it to the bare number.
 */
class TicketLookupRequest extends FormRequest
{
    public const string MESSAGE = 'Bitte eine Ticketnummer eingeben, z. B. Ticket#2137942.';

    /**
     * An invalid input goes back to the empty input page.
     *
     * @var string
     */
    protected $redirectRoute = 'tickets.analyze';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->query('ticket');

        if (is_string($raw) && preg_match('/^\s*(?:ticket\s*)?#?\s*(\d{1,20})\s*$/iu', $raw, $match)) {
            $this->merge(['ticket' => $match[1]]);
        }
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'ticket' => ['required', 'string', 'regex:/^\d{1,20}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticket.required' => self::MESSAGE,
            'ticket.string' => self::MESSAGE,
            'ticket.regex' => self::MESSAGE,
        ];
    }

    public function number(): string
    {
        return $this->validated('ticket');
    }
}

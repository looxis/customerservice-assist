<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The ticket input accepts what Zammad's copy button produces
 * ("Ticket#2137942") and reduces it to the bare number. It also accepts the
 * address of a ticket in Zammad (".../#ticket/zoom/38698"), which works even
 * when Zammad's search index lags behind.
 */
class TicketLookupRequest extends FormRequest
{
    public const string MESSAGE = 'Bitte eine Ticketnummer oder die Adresse des Tickets aus Zammad eingeben, z. B. Ticket#2137942.';

    public const string OTHER_HOST = 'Diese Adresse gehört nicht zu unserem Zammad. Bitte die Adresse des Tickets aus Zammad einfügen.';

    private ?int $zammadId = null;

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

            return;
        }

        if (is_string($raw) && preg_match('~^\s*(https?://[^/#\s]+)[^#\s]*#ticket/zoom/(\d{1,12})(?:[/?][^\s]*)?\s*$~i', $raw, $match)) {
            $this->zammadId = $this->isOurZammad($match[1]) ? (int) $match[2] : null;
            $this->merge(['ticket' => $this->zammadId === null ? 'fremd' : 'zammad-id']);
        }
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'ticket' => ['required', 'string', 'regex:/^(\d{1,20}|zammad-id)$/'],
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
            'ticket.regex' => $this->input('ticket') === 'fremd' ? self::OTHER_HOST : self::MESSAGE,
        ];
    }

    /**
     * The ticket number, or null when an address with the Zammad ID was given.
     */
    public function number(): ?string
    {
        return $this->zammadId === null ? $this->validated('ticket') : null;
    }

    public function zammadId(): ?int
    {
        return $this->zammadId;
    }

    private function isOurZammad(string $origin): bool
    {
        $ours = parse_url((string) config('services.zammad.url'), PHP_URL_HOST);

        return is_string($ours) && strcasecmp((string) parse_url($origin, PHP_URL_HOST), $ours) === 0;
    }
}

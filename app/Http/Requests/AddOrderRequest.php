<?php

namespace App\Http\Requests;

use App\Orders\OrderNumberDetector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Typing in an order number on the ticket page: cleaned up, checked against
 * the known formats and added to the orders already selected.
 */
class AddOrderRequest extends FormRequest
{
    public const string MESSAGE = 'Unbekanntes Format. Erwartet z. B. 402-4907715-1581912 oder 7JI-0WC1-6M49.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return [
            'bestellnummer' => ['required', 'string', 'max:40', function (string $attribute, mixed $value, Closure $fail): void {
                if (app(OrderNumberDetector::class)->normalize((string) $value) === null) {
                    $fail(self::MESSAGE);
                }
            }],
            'bestellungen' => ['sometimes', 'array', 'max:20'],
            'bestellungen.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bestellnummer.required' => self::MESSAGE,
            'bestellnummer.string' => self::MESSAGE,
            'bestellnummer.max' => self::MESSAGE,
        ];
    }

    /**
     * Back to the ticket with the selection unchanged.
     */
    protected function getRedirectUrl(): string
    {
        return route('tickets.show', ['number' => $this->route('number'), 'bestellungen' => $this->selection()]);
    }

    /**
     * The selection the page already had, without invalid entries.
     *
     * @return list<string>
     */
    public function selection(): array
    {
        $raw = $this->query('bestellungen', []);

        return is_array($raw) ? array_values(array_filter($raw, 'is_string')) : [];
    }

    public function orderNumber(): string
    {
        return app(OrderNumberDetector::class)->normalize($this->validated('bestellnummer'))->value;
    }
}

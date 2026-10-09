<?php

namespace App\Http\Requests;

use App\Analysis\ContextVariant;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class AnalyzeTicketRequest extends FormRequest
{
    /**
     * Fields for order data typed in by hand, with their German label for the model.
     */
    public const array MANUAL_ORDER_FIELDS = [
        'bestellung_nummer' => 'Bestellnummer',
        'bestellung_kanal' => 'Kanal',
        'bestellung_datum' => 'Bestelldatum',
        'bestellung_produkt' => 'Produkt',
        'bestellung_personalisierung' => 'Personalisierung',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string|In>>
     */
    public function rules(): array
    {
        $selector = app(KnowledgeSelector::class);

        return [
            'kundengruppe' => ['required', 'string', Rule::in(array_map(fn (CustomerGroup $group): string => $group->key, $selector->customerGroups()))],
            'produkte' => ['sometimes', 'array', 'max:10'],
            'produkte.*' => ['string', Rule::in(array_column($selector->products(), 'slug'))],
            'variante' => ['required', 'string', Rule::enum(ContextVariant::class)],
            'kontext' => ['nullable', 'string', 'max:'.config('analysis.max_context_length')],
            'stand' => ['nullable', 'integer'],
            'ohne_bestelldetails' => ['sometimes', 'boolean'],
            'bestellungen' => ['sometimes', 'array', 'max:10'],
            'bestellungen.*' => ['string', 'max:40'],
            'bestellung_nummer' => ['nullable', 'string', 'max:60'],
            'bestellung_kanal' => ['nullable', 'string', 'max:60'],
            'bestellung_datum' => ['nullable', 'string', 'max:30'],
            'bestellung_produkt' => ['nullable', 'string', 'max:200'],
            'bestellung_personalisierung' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['kundengruppe' => 'Kundengruppe', 'produkte.*' => 'Produkt', 'variante' => 'Ticketkontext', 'kontext' => 'Zusätzliche Informationen'];
    }

    public function variant(): ContextVariant
    {
        return ContextVariant::from($this->validated('variante'));
    }

    /**
     * @return array<string, string>
     */
    public function manualOrder(): array
    {
        $values = [];

        foreach (self::MANUAL_ORDER_FIELDS as $field => $label) {
            $values[$label] = (string) $this->validated($field, '');
        }

        return $values;
    }

    protected function getRedirectUrl(): string
    {
        return route('tickets.show', ['number' => $this->route('number'), 'bestellungen' => (array) $this->input('bestellungen', [])]).'#analyse';
    }
}

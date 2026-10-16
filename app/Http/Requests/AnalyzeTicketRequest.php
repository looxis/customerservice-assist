<?php

namespace App\Http\Requests;

use App\Analysis\AnalysisStore;
use App\Analysis\Clarification;
use App\Analysis\ContextVariant;
use App\Http\Requests\Concerns\ChecksProductChoice;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Validator;

class AnalyzeTicketRequest extends FormRequest
{
    use ChecksProductChoice;

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

    /**
     * @var array<string, mixed>|null
     */
    private ?array $parent = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Continuing a case after an internal clarification (PROJ-39): the form fields
     * the employee does not send again come from the earlier analysis.
     */
    protected function prepareForValidation(): void
    {
        $parent = $this->parentAnalysis();

        if ($parent === null) {
            return;
        }

        $inputs = (array) ($parent['inputs'] ?? []);
        $this->merge(array_filter(
            $inputs,
            fn (mixed $value, string $key): bool => ! $this->has($key) && $value !== null,
            ARRAY_FILTER_USE_BOTH,
        ));
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
            'zusatzwissen' => ['sometimes', 'array', 'max:20'],
            'zusatzwissen.*' => ['string', 'regex:/^[A-Z]+(?:-[A-Z]+)*-\d{3}$/'],
            'bestellung_nummer' => ['nullable', 'string', 'max:60'],
            'bestellung_kanal' => ['nullable', 'string', 'max:60'],
            'bestellung_datum' => ['nullable', 'string', 'max:30'],
            'bestellung_produkt' => ['nullable', 'string', 'max:200'],
            'bestellung_personalisierung' => ['nullable', 'string', 'max:2000'],
            ...$this->productRules(),
            'klaerung_analyse' => ['nullable', 'string', 'max:64'],
            'klaerung_option' => ['nullable', 'string', 'max:100'],
            'klaerung_text' => ['nullable', 'string', 'max:'.config('analysis.max_context_length')],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('klaerung_analyse')) {
                $this->checkProductChoice($validator);

                return;
            }

            $parent = $this->parentAnalysis();
            if ($parent === null) {
                $validator->errors()->add('klaerung_analyse', 'Dieser Vorschlag ist nicht der neueste und kann nicht fortgesetzt werden.');
            } elseif (! $this->filled('klaerung_option') && ! $this->filled('klaerung_text')) {
                $validator->errors()->add('klaerung_text', 'Bitte eine Option wählen oder das Ergebnis der internen Klärung eingeben.');
            } elseif ($this->filled('klaerung_option') && ! Clarification::hasOption($parent, (string) $this->input('klaerung_option'))) {
                $validator->errors()->add('klaerung_option', 'Diese Option gibt es nicht.');
            }
        }];
    }

    /**
     * The earlier clarifications of the case plus the new one, built from the stored analysis
     * (the option text never comes from the request), or null for an ordinary analysis.
     *
     * @return list<array{question: string, answer: string, by: string, at: string}>|null
     */
    public function decisions(string $by): ?array
    {
        $parent = $this->parentAnalysis();

        return $parent === null ? null : Clarification::decisions($parent, $this->filled('klaerung_option') ? (string) $this->input('klaerung_option') : null, (string) $this->input('klaerung_text', ''), $by);
    }

    /**
     * The stored analysis this request continues; only the newest analysis of the ticket can be continued.
     *
     * @return array<string, mixed>|null
     */
    public function parentAnalysis(): ?array
    {
        if ($this->parent !== null || ! is_string($this->input('klaerung_analyse')) || $this->input('klaerung_analyse') === '') {
            return $this->parent;
        }

        $store = app(AnalysisStore::class);
        $id = $this->input('klaerung_analyse');
        $stored = $store->result($id);

        if ($stored === null || ($stored['ticket'] ?? null) !== $this->route('number') || $store->latest((string) ($stored['scope'] ?? '')) !== $id) {
            return null;
        }

        return $this->parent = $stored;
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
        return route('tickets.show', ['number' => $this->route('number'), 'bestellungen' => (array) $this->input('bestellungen', [])]).($this->filled('klaerung_analyse') ? '#ablaeufe' : '#analyse');
    }
}

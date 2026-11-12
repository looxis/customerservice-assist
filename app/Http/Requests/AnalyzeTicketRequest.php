<?php

namespace App\Http\Requests;

use App\Analysis\AnalysisStore;
use App\Analysis\Clarification;
use App\Analysis\ComplaintDecision;
use App\Analysis\ContextVariant;
use App\Http\Requests\Concerns\ChecksProductChoice;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use App\Orders\OrderFormats;
use App\Zammad\RelatedTicket;
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
        if (is_string($this->input('reklamationsnummer'))) {
            $this->merge(['reklamationsnummer' => strtoupper(trim($this->input('reklamationsnummer')))]);
        }

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
            'kundengruppe' => ['required', 'string', Rule::in(array_map(fn (CustomerGroup $group): string => $group->key, $selector->selectableGroups()))],
            'produkte' => ['sometimes', 'array', 'max:10'],
            'produkte.*' => ['string', Rule::in(array_column($selector->products(), 'slug'))],
            'variante' => ['required', 'string', Rule::enum(ContextVariant::class)],
            'kontext' => ['nullable', 'string', 'max:'.config('analysis.max_context_length')],
            'stand' => ['nullable', 'integer'],
            'return_label' => ['nullable', 'uuid'],
            'formular' => ['nullable', Rule::in([ComplaintDecision::FORM, ComplaintDecision::FREE])],
            'reklamation_berechtigt' => ['nullable', Rule::in(ComplaintDecision::ANSWERS)],
            'reklamation_info' => ['nullable', 'string', 'max:1000'],
            'reklamation_grund' => ['nullable', 'string', 'max:1000'],
            'kulanz' => ['nullable', Rule::in(['ja', 'nein'])],
            'retoure' => ['nullable', Rule::in(['ja', 'nein'])],
            'reklamationsnummer' => ['nullable', 'string', 'max:60', 'regex:/^'.preg_quote(app(OrderFormats::class)->claimPrefix(), '/').'\d+-\S+$/i'],
            'versand' => ['nullable', Rule::in(['heute', 'morgen', 'zeitnah'])],
            'sendungsnummer_todo' => ['sometimes', 'boolean'],
            'ohne_bestelldetails' => ['sometimes', 'boolean'],
            'bestellungen' => ['sometimes', 'array', 'max:10'],
            'bestellungen.*' => ['string', 'max:40'],
            'related' => ['sometimes', 'array', 'max:'.(int) config('analysis.related_tickets.max')],
            'related.*' => ['string', 'regex:/^\d{1,20}$/'],
            'related_manual' => ['nullable', 'string', 'regex:/^\s*\d{1,20}\s*$/'],
            'related_set' => ['sometimes', 'boolean'],
            'related_source' => ['sometimes', 'array'],
            'related_source.*' => ['string', 'max:20'],
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
            if ($this->input('formular') === ComplaintDecision::FORM) {
                if (! in_array($this->input('reklamation_berechtigt'), ComplaintDecision::ANSWERS, true)) {
                    $validator->errors()->add('reklamation_berechtigt', 'Bitte angeben, ob die Reklamation berechtigt ist.');
                } elseif ($this->input('reklamation_berechtigt') === 'nein' && trim((string) $this->input('reklamation_grund', '')) === '') {
                    $validator->errors()->add('reklamation_grund', 'Bitte den Grund eingeben, warum die Reklamation nicht berechtigt ist.');
                } elseif ($this->input('reklamation_berechtigt') === 'info' && trim((string) $this->input('reklamation_info', '')) === '') {
                    $validator->errors()->add('reklamation_info', 'Bitte angeben, welche Informationen für die Entscheidung nötig sind.');
                }
            }

            if (count($this->relatedNumbers()) > (int) config('analysis.related_tickets.max')) {
                $validator->errors()->add('related', 'Höchstens '.config('analysis.related_tickets.max').' zusammengehörige Tickets.');
            }

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
        return ['kundengruppe' => 'Kundengruppe', 'produkte.*' => 'Produkt', 'variante' => 'Ticketkontext', 'kontext' => 'Zusätzliche Informationen', 'reklamation_grund' => 'Grund', 'reklamation_info' => 'Nötige Informationen', 'reklamationsnummer' => 'Reklamationsnummer'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $prefix = app(OrderFormats::class)->claimPrefix();

        return ['reklamationsnummer.regex' => "Die Reklamationsnummer hat den Aufbau {$prefix}1-… (Nummer, Bindestrich, Bestellnummer), zum Beispiel {$prefix}1-028-0861097-0545938."];
    }

    /**
     * The complaint answers of the form (PROJ-86), or null when the free-text form was used.
     */
    public function complaint(): ?ComplaintDecision
    {
        return ComplaintDecision::fromInput($this->validated());
    }

    /**
     * The DHL return label to attach: with the complaint form only when the complaint is justified and the
     * product is sent back, whatever else was sent.
     */
    public function returnLabelId(): ?string
    {
        $complaint = $this->complaint();
        $id = $this->validated('return_label');

        return filled($id) && ($complaint === null || $complaint->wantsReturnLabel()) ? (string) $id : null;
    }

    /**
     * The numbers of the related tickets to take along: the ticked ones and the one typed in, without this ticket (PROJ-52).
     *
     * @return list<string>
     */
    public function relatedNumbers(): array
    {
        $numbers = array_map('strval', array_filter((array) $this->input('related', []), fn (mixed $number): bool => is_string($number) && preg_match('/^\d{1,20}$/', $number) === 1));
        $manual = trim((string) $this->input('related_manual', ''));

        if ($manual !== '' && preg_match('/^\d{1,20}$/', $manual) === 1) {
            $numbers[] = $manual;
        }

        return array_values(array_filter(array_unique($numbers), fn (string $number): bool => $number !== (string) $this->route('number')));
    }

    /**
     * Where a chosen related ticket came from (as the form offered it); typed-in ones are "manual".
     */
    public function relatedSource(string $number): string
    {
        $source = $this->input("related_source.{$number}");

        return in_array($source, [RelatedTicket::LINKED, RelatedTicket::ORDER, RelatedTicket::CUSTOMER], true) && in_array($number, (array) $this->input('related', []), true) ? $source : RelatedTicket::MANUAL;
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

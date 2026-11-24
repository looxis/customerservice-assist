<?php

namespace App\Analysis;

/**
 * The form "Zahlung / Rechnung" in "Vorschlag erstellen" (PROJ-100), preselected for tickets of the category
 * `zahlung`. The employee names the concern; for a proof of payment also the payment method and the payment state
 * checked in EOCS (the invoice page of the order). The app turns the answers into fixed sentences for the language
 * model: a proof of payment for a Mollie order is never "added to the order", Mollie recognises the payment and
 * releases the order by itself (PLAYBOOK-060). A dunning letter of a supplier gets a short acknowledgement.
 */
final readonly class PaymentDecision
{
    public const string FORM = 'zahlung';

    public const string PROOF = 'nachweis';

    public const string QUESTION = 'frage';

    public const string INVOICE = 'rechnung';

    public const string REFUND = 'erstattung';

    public const string SUPPLIER = 'lieferant';

    public const array CONCERNS = [self::PROOF, self::QUESTION, self::INVOICE, self::REFUND, self::SUPPLIER];

    public const string MOLLIE = 'mollie';

    public const string ON_ACCOUNT = 'rechnung';

    public const array METHODS = [self::MOLLIE, self::ON_ACCOUNT];

    public const string PAID = 'bezahlt';

    public const string OPEN = 'offen';

    public const string CANCELLED = 'storniert';

    public const array STATES = [self::PAID, self::OPEN, self::CANCELLED];

    public const array YES_NO = ['ja', 'nein'];

    /**
     * The error messages for missing required answers, by field.
     *
     * @var array<string, string>
     */
    public const array MESSAGES = [
        'zahlung_anliegen' => 'Bitte das Anliegen angeben.',
        'zahlung_art' => 'Bitte die Zahlungsart der Bestellung angeben.',
        'zahlung_stand' => 'Bitte den Zahlungsstand laut EOCS angeben.',
        'zahlung_rechnung_anhang' => 'Bitte angeben, ob die Rechnung der Antwort beiliegt.',
        'zahlung_erstattung' => 'Bitte angeben, ob die Erstattung veranlasst ist.',
    ];

    /**
     * @param  'nachweis'|'frage'|'rechnung'|'erstattung'|'lieferant'  $concern
     * @param  'mollie'|'rechnung'|null  $method  Payment method of the order; required for a proof of payment, optional for a question.
     * @param  'bezahlt'|'offen'|'storniert'|null  $state  Payment state as checked in EOCS; required for a proof of payment, optional for a question.
     * @param  'ja'|'nein'|null  $invoiceAttached  Only for an invoice request: whether the invoice goes with the reply.
     * @param  'ja'|'nein'|null  $refundDone  Only for a refund: whether the refund has been made.
     * @param  string  $extraInfo  Free-text notes of the employee for the draft.
     */
    public function __construct(
        public string $concern,
        public ?string $method = null,
        public ?string $state = null,
        public ?string $invoiceAttached = null,
        public ?string $refundDone = null,
        public string $extraInfo = '',
    ) {}

    /**
     * The answers of a request, or null when another form was used or a required answer is missing.
     *
     * @param  array<string, mixed>  $input  Validated request fields or the stored inputs of an analysis.
     */
    public static function fromInput(array $input): ?self
    {
        $decision = self::read($input);

        return $decision?->missing() === null ? $decision : null;
    }

    /**
     * The field of the first required answer that is missing in a request of this form, or null when it is complete.
     *
     * @param  array<string, mixed>  $input
     */
    public static function missingIn(array $input): ?string
    {
        return self::read($input)?->missing() ?? (in_array($input['zahlung_anliegen'] ?? null, self::CONCERNS, true) ? null : 'zahlung_anliegen');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function read(array $input): ?self
    {
        $concern = $input['zahlung_anliegen'] ?? null;

        if (($input['formular'] ?? null) !== self::FORM || ! in_array($concern, self::CONCERNS, true)) {
            return null;
        }

        $pick = fn (string $key, array $allowed): ?string => in_array($input[$key] ?? null, $allowed, true) ? $input[$key] : null;
        $withState = in_array($concern, [self::PROOF, self::QUESTION], true);

        return new self(
            concern: $concern,
            method: $withState ? $pick('zahlung_art', self::METHODS) : null,
            state: $withState ? $pick('zahlung_stand', self::STATES) : null,
            invoiceAttached: $concern === self::INVOICE ? $pick('zahlung_rechnung_anhang', self::YES_NO) : null,
            refundDone: $concern === self::REFUND ? $pick('zahlung_erstattung', self::YES_NO) : null,
            extraInfo: trim((string) ($input['zahlung_zusatz'] ?? '')),
        );
    }

    /**
     * The field of the first required answer that is missing, or null when the answers are complete.
     */
    public function missing(): ?string
    {
        return match (true) {
            $this->concern === self::PROOF && $this->method === null => 'zahlung_art',
            $this->concern === self::PROOF && $this->state === null => 'zahlung_stand',
            $this->concern === self::INVOICE && $this->invoiceAttached === null => 'zahlung_rechnung_anhang',
            $this->concern === self::REFUND && $this->refundDone === null => 'zahlung_erstattung',
            default => null,
        };
    }

    /**
     * The fields to store with the analysis, so a later proposal can prefill the form.
     *
     * @return array<string, string|null>
     */
    public function storedInput(): array
    {
        return [
            'formular' => self::FORM,
            'zahlung_anliegen' => $this->concern,
            'zahlung_art' => $this->method,
            'zahlung_stand' => $this->state,
            'zahlung_rechnung_anhang' => $this->invoiceAttached,
            'zahlung_erstattung' => $this->refundDone,
            'zahlung_zusatz' => $this->extraInfo,
        ];
    }

    /**
     * The sentences for the language model, added to the information of the employee.
     */
    public function facts(): string
    {
        $lines = ['Angaben aus dem Formular „Zahlung / Rechnung“ (geprüfte Angaben des Mitarbeiters, sie gehen dem Wissen vor):'];

        array_push($lines, ...match ($this->concern) {
            self::PROOF => $this->proofFacts(),
            self::QUESTION => $this->questionFacts(),
            self::INVOICE => $this->invoiceFacts(),
            self::REFUND => $this->refundFacts(),
            self::SUPPLIER => $this->supplierFacts(),
        });

        if ($this->extraInfo !== '') {
            $lines[] = '- Zusätzliche Informationen des Mitarbeiters (vorrangig): '.$this->extraInfo;
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function proofFacts(): array
    {
        $lines = ['- Der Kunde hat einen Zahlungsnachweis bzw. eine Überweisungsbestätigung zu seiner Bestellung geschickt.'];

        if ($this->method === self::MOLLIE) {
            $lines[] = '- Die Bestellung wird über unseren Zahlungsdienstleister Mollie bezahlt; die Überweisung ging an Mollie, nicht an LOOXIS. LOOXIS hat keinen Zugriff auf das Konto von Mollie und sieht den Banktransfer nicht.';
            $lines[] = '- Inhalt des Entwurfs: Danke für die Nachricht. Ein Zahlungsnachweis ist nicht erforderlich: Mollie erkennt den Zahlungseingang automatisch, sofern IBAN, Betrag und Zahlungsreferenz genau der Zahlungsanweisung entsprechen, und gibt die Bestellung danach automatisch frei. Der Nachweis beschleunigt nichts.';
            $lines[] = match ($this->state) {
                self::PAID => '- Stand laut EOCS: Die Zahlung ist registriert und die Bestellung freigegeben. Sage das. `recommendation`: Antwort senden und das Ticket schließen.',
                self::OPEN => '- Stand laut EOCS: Die Zahlung ist noch nicht registriert. Bitte den Kunden, IBAN, Betrag und Zahlungsreferenz in seiner Überweisung mit der Zahlungsanweisung von Mollie zu dieser Bestellung abzugleichen; maßgeblich ist die Frist in der Zahlungsanweisung (keine Anzahl Tage nennen). Sobald Mollie die Zahlung registriert, wird die Bestellung automatisch freigegeben.',
                default => '- Stand laut EOCS: Die Bestellung wurde nicht rechtzeitig bezahlt und ist storniert bzw. die Frist ist abgelaufen. Erkläre: Nach Ablauf der Frist eingegangene Zahlungen zahlt Mollie an den Zahlenden zurück, in der Regel innerhalb von 1 bis 3 Werktagen. Bitte den Kunden, die Bestellung neu auszulösen. Keine manuelle Freigabe zusagen.',
            };
            $lines[] = '- Sage nie, der Beleg werde der Bestellung hinzugefügt, weitergeleitet oder verbucht, und nie, LOOXIS habe die Zahlung erhalten. Nenne keine Bankverbindung von LOOXIS.';

            return $lines;
        }

        $lines[] = '- Die Bestellung wird auf Rechnung bezahlt; die Überweisung geht an ein Geschäftskonto von LOOXIS.';
        $lines[] = match ($this->state) {
            self::PAID => '- Inhalt des Entwurfs: Danke für den Nachweis. Die Zahlung ist bereits verbucht. `recommendation`: Antwort senden und das Ticket schließen.',
            self::OPEN => '- Inhalt des Entwurfs: Danke für den Nachweis, wir ordnen die Zahlung zu. Sie ist noch nicht verbucht; die Zuordnung kann einige Werktage dauern. Keine schnellere Bearbeitung oder Freigabe zusagen.',
            default => '- Die Bestellung ist laut EOCS storniert. Sage dem Kunden nur, dass wir den Zahlungseingang und den Stand der Bestellung prüfen und uns melden. Keine Zusage zu Erstattung oder Freigabe; als interne Klärung vorsehen.',
        };
        $lines[] = '- Erwähne Mollie nicht.';

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function questionFacts(): array
    {
        $lines = ['- Der Kunde hat eine Frage zur Zahlung seiner Bestellung (z. B. wie oder wohin zahlen, Zahlungsart, Frist, Lastschrift). Beantworte sie nach dem Wissen.'];

        if ($this->method !== null) {
            $lines[] = $this->method === self::MOLLIE ? '- Zahlungsart der Bestellung: Mollie.' : '- Zahlungsart der Bestellung: Zahlung auf Rechnung.';
        }

        if ($this->state !== null) {
            $lines[] = match ($this->state) {
                self::PAID => '- Stand laut EOCS: bezahlt, Bestellung freigegeben.',
                self::OPEN => '- Stand laut EOCS: Zahlung noch nicht registriert, Bestellung offen.',
                default => '- Stand laut EOCS: nicht rechtzeitig bezahlt, Bestellung storniert bzw. Frist abgelaufen.',
            };
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function invoiceFacts(): array
    {
        $lines = ['- Der Kunde fordert eine Rechnung an oder möchte eine Rechnung korrigiert haben.'];
        $lines[] = $this->invoiceAttached === 'ja'
            ? '- Die Rechnung liegt der Antwort als Anhang bei. Sage das kurz.'
            : '- Die Rechnung liegt der Antwort nicht bei; wir prüfen das Anliegen intern. Kündige an, dass wir die Rechnung prüfen und uns melden. Sage nicht, dass eine Rechnung anhängt.';

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function refundFacts(): array
    {
        $lines = ['- Der Kunde fragt nach dem Stand einer zugesagten Erstattung.'];
        $lines[] = $this->refundDone === 'ja'
            ? '- Die Erstattung ist veranlasst. Sage das. Wann der Betrag gutgeschrieben wird, hängt von Bank bzw. Zahlungsart ab; nenne keine Anzahl Tage.'
            : '- Die Erstattung ist noch nicht veranlasst. Sage, dass wir sie prüfen und uns melden. Nenne kein Datum und keine Frist.';

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function supplierFacts(): array
    {
        return [
            '- Die Nachricht ist eine Mahnung oder Rechnung eines Lieferanten bzw. Dienstleisters an LOOXIS, keine Kundenanfrage.',
            '- Inhalt des Entwurfs: kurze, sachliche Eingangsbestätigung an den Absender: Wir haben die Nachricht erhalten und an unsere Buchhaltung weitergegeben. Höchstens drei Sätze zwischen Anrede und Schluss.',
            '- Keine Aussage zur Berechtigung der Forderung, kein Zahlungstermin, keine Rückfrage, keine Produkte, keine Bestellung, keine Platzhalter in eckigen Klammern. Regeln aus dem Wissen zu Kundenbestellungen setzt du hier nicht um.',
            '- `recommendation`: Nachricht an die Buchhaltung weitergeben, Antwort senden und das Ticket schließen. `actions`: keine.',
        ];
    }
}

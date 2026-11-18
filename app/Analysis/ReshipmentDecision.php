<?php

namespace App\Analysis;

use App\Orders\OrderFormats;

/**
 * The answers of the form "Neuversand" in "Vorschlag erstellen" (PROJ-93): a new shipment the employee has already
 * set up in EOCS, free of charge as goodwill or charged (paid, or with an invoice in the parcel). The form has no
 * ticket category and is only chosen by hand. The app turns the answers into short fixed sentences for the language
 * model; the new delivery address never goes to the model, only the placeholder that the app fills in afterwards.
 * The number of the new shipment is mostly a claim number ("R1-<order>", a charged claim in EOCS), else an order number.
 */
final readonly class ReshipmentDecision
{
    public const string FORM = 'neuversand';

    public const string ADDRESS_PLACEHOLDER = '[NEUE-LIEFERADRESSE]';

    public const string ORDER_PLACEHOLDER = '[AUFTRAGSNUMMER]';

    public const string PAID = 'ja';

    public const string INVOICE = 'rechnung';

    public const array CHARGED_ANSWERS = ['ja', 'nein'];

    public const array PAYMENT_ANSWERS = [self::PAID, self::INVOICE];

    /**
     * @param  bool  $charged  Whether the customer pays for the new shipment.
     * @param  'ja'|'rechnung'|null  $payment  Only for a charged shipment: already paid, or the invoice goes with the parcel.
     * @param  string  $orderNumber  The order number of the new shipment in EOCS; the draft names it.
     * @param  string  $address  The updated delivery address; reaches the model only as a placeholder.
     * @param  string  $extraInfo  Free-text notes of the employee for the draft.
     * @param  bool  $trackingTodo  Whether the ToDo "Sendungsnummer mitteilen" is preselected under the draft.
     */
    public function __construct(
        public bool $charged,
        public ?string $payment = null,
        public string $orderNumber = '',
        public string $address = '',
        public string $extraInfo = '',
        public bool $trackingTodo = true,
    ) {}

    /**
     * The answers of a request, or null when another form was used or the main question is unanswered.
     *
     * @param  array<string, mixed>  $input  Validated request fields or the stored inputs of an analysis.
     */
    public static function fromInput(array $input): ?self
    {
        if (($input['formular'] ?? null) !== self::FORM || ! in_array($input['neuversand_kostenpflichtig'] ?? null, self::CHARGED_ANSWERS, true)) {
            return null;
        }

        $charged = $input['neuversand_kostenpflichtig'] === 'ja';
        $payment = $input['neuversand_bezahlt'] ?? null;

        return new self(
            charged: $charged,
            payment: $charged && in_array($payment, self::PAYMENT_ANSWERS, true) ? $payment : null,
            orderNumber: trim((string) ($input['neuversand_auftrag'] ?? '')),
            address: trim(str_replace("\r\n", "\n", (string) ($input['neuversand_adresse'] ?? ''))),
            extraInfo: trim((string) ($input['neuversand_zusatz'] ?? '')),
            trackingTodo: filter_var($input['neuversand_todo'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * Whether a number is a claim number of EOCS ("R1-<order>"), as a new shipment is mostly set up as a claim.
     */
    public static function isClaimNumber(string $number): bool
    {
        return preg_match('/^'.preg_quote(app(OrderFormats::class)->claimPrefix(), '/').'\d+-\S+$/i', trim($number)) === 1;
    }

    public function withInvoice(): bool
    {
        return $this->charged && $this->payment === self::INVOICE;
    }

    /**
     * The fields to store with the analysis, so a later proposal can prefill the form.
     *
     * @return array<string, mixed>
     */
    public function storedInput(): array
    {
        return [
            'formular' => self::FORM,
            'neuversand_kostenpflichtig' => $this->charged ? 'ja' : 'nein',
            ...($this->charged ? ['neuversand_bezahlt' => $this->payment] : []),
            'neuversand_auftrag' => $this->orderNumber,
            'neuversand_adresse' => $this->address,
            'neuversand_zusatz' => $this->extraInfo,
            'neuversand_todo' => $this->trackingTodo,
        ];
    }

    /**
     * The values the app puts back into the draft in place of the placeholders.
     *
     * @return array<string, string>
     */
    public function placeholders(): array
    {
        return $this->address === '' ? [] : [self::ADDRESS_PLACEHOLDER => $this->address];
    }

    /**
     * The sentences for the language model, added to the information of the employee.
     */
    public function facts(): string
    {
        $number = $this->orderNumber !== '' ? $this->orderNumber : self::ORDER_PLACEHOLDER;
        $lines = ['Angaben aus dem Formular „Neuversand“ (geprüfte Entscheidungen und Fakten des Mitarbeiters):'];

        $lines[] = match (true) {
            ! $this->charged => "- Wir haben den Neuversand auf Kulanz kostenfrei veranlasst. Teile dem Kunden kurz mit, dass wir den Neuversand kulanzhalber kostenfrei veranlasst haben und er unter der Nummer {$number} läuft.",
            $this->withInvoice() => "- Der Neuversand ist kostenpflichtig und noch nicht bezahlt. Teile dem Kunden mit, dass der Neuversand jetzt unter der Nummer {$number} veranlasst wird und dem Paket eine Rechnung zur Zahlung des Neuversands beiliegt.",
            default => "- Der Neuversand ist kostenpflichtig und bereits bezahlt. Teile dem Kunden nur mit, dass wir den Neuversand veranlasst haben und er unter der Nummer {$number} läuft. Erwähne keine Kosten, keine Zahlung und keine Rechnung.",
        };

        if ($this->orderNumber === '') {
            $lines[] = '- Die Auftragsnummer des Neuversands ist noch nicht bekannt: Übernimm den Platzhalter '.self::ORDER_PLACEHOLDER.' unverändert in den Entwurf.';
        }

        $lines[] = '- Die Sendungsnummer wird dem Kunden nach dem Versand separat mitgeteilt: Sage das im Entwurf zu. Nenne keinen Versandtermin und keine Sendungsnummer.';

        if ($this->address !== '') {
            $lines[] = '- Der Neuversand geht an die aktualisierte Lieferadresse. Nenne sie im Entwurf zur Bestätigung, auf einer eigenen Zeile und genau als Platzhalter '.self::ADDRESS_PLACEHOLDER.' (die App setzt die Adresse ein).';
        }

        if ($this->extraInfo !== '') {
            $lines[] = '- PRIORITÄRE ZUSÄTZLICHE INFORMATIONEN des Mitarbeiters (berücksichtige sie im Entwurf, verbindlich und vor allgemeinem Wissen): '.$this->extraInfo;
        }

        $lines[] = '- Halte den Entwurf kurz: Es geht nur um die Mitteilung zum Neuversand und um die Punkte der Kundennachricht, die noch offen sind.';

        return implode("\n", $lines);
    }
}

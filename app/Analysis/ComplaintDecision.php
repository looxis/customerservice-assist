<?php

namespace App\Analysis;

/**
 * The answers of the complaint form in "Vorschlag erstellen" (PROJ-86). The form replaces the free text for a complaint
 * ticket; the app turns the answers into short fixed sentences for the language model. Only decisions and facts go
 * in, never a rule: the rules stay in the knowledge base.
 */
final readonly class ComplaintDecision
{
    public const string FORM = 'reklamation';

    public const string FREE = 'frei';

    public const array ANSWERS = ['ja', 'nein', 'unsicher', 'info'];

    /**
     * @param  'ja'|'nein'|'unsicher'|'info'  $justified  "unsicher": replaced without admitting a legal duty; "info": undecided, more information needed.
     * @param  'ja'|'nein'  $goodwill  Only meaningful when the complaint is not justified.
     * @param  'ja'|'nein'  $return  Whether the defective product is sent back; only meaningful when justified.
     * @param  'heute'|'morgen'|'zeitnah'  $shipping  When the new product is shipped; only meaningful when justified.
     */
    public function __construct(
        public string $justified,
        public string $reason,
        public string $goodwill,
        public string $return,
        public string $claimNumber,
        public string $shipping,
        public bool $trackingTodo,
        public string $missingInfo = '',
    ) {}

    /**
     * The complaint answers of a request, or null when the free-text form was used.
     *
     * @param  array<string, mixed>  $input  Validated request fields or the stored inputs of an analysis.
     */
    public static function fromInput(array $input): ?self
    {
        if (($input['formular'] ?? null) !== self::FORM || ! in_array($input['reklamation_berechtigt'] ?? null, self::ANSWERS, true)) {
            return null;
        }

        return new self(
            justified: $input['reklamation_berechtigt'],
            reason: trim((string) ($input['reklamation_grund'] ?? '')),
            goodwill: ($input['kulanz'] ?? null) === 'ja' ? 'ja' : 'nein',
            return: ($input['retoure'] ?? null) === 'ja' ? 'ja' : 'nein',
            claimNumber: trim((string) ($input['reklamationsnummer'] ?? '')),
            shipping: in_array($input['versand'] ?? null, ['heute', 'morgen', 'zeitnah'], true) ? $input['versand'] : 'zeitnah',
            trackingTodo: filter_var($input['sendungsnummer_todo'] ?? false, FILTER_VALIDATE_BOOLEAN),
            missingInfo: trim((string) ($input['reklamation_info'] ?? '')),
        );
    }

    public function isJustified(): bool
    {
        return $this->justified === 'ja';
    }

    /**
     * Whether the customer gets a replacement: justified, or replaced without admitting a legal duty.
     */
    public function isReplaced(): bool
    {
        return $this->isJustified() || $this->justified === 'unsicher';
    }

    /**
     * Whether a DHL return label belongs to this complaint: replaced and the product is sent back.
     */
    public function wantsReturnLabel(): bool
    {
        return $this->isReplaced() && $this->return === 'ja';
    }

    /**
     * The fields to store with the analysis: only those of the branch that was answered, so a later proposal can
     * prefill the form.
     *
     * @return array<string, string|bool>
     */
    public function storedInput(): array
    {
        $stored = ['formular' => self::FORM, 'reklamation_berechtigt' => $this->justified];

        if ($this->justified === 'info') {
            return [...$stored, 'reklamation_info' => $this->missingInfo];
        }

        if (! $this->isReplaced()) {
            return [...$stored, 'reklamation_grund' => $this->reason, 'kulanz' => $this->goodwill];
        }

        return [...$stored, 'retoure' => $this->return, 'reklamationsnummer' => $this->claimNumber, 'versand' => $this->shipping, 'sendungsnummer_todo' => $this->trackingTodo];
    }

    /**
     * The sentences for the language model, added to the information of the employee.
     */
    public function facts(): string
    {
        $lines = ['Angaben aus dem Reklamationsformular (geprüfte Entscheidungen und Fakten des Mitarbeiters):'];

        if ($this->justified === 'info') {
            $lines[] = '- Die Reklamation kann noch nicht entschieden werden: Es sind weitere Informationen nötig. Sage weder zu noch ab, kündige keine Neuproduktion und keinen Versand an.';
            $lines[] = '- Für die Entscheidung werden diese Informationen benötigt (frage den Kunden gezielt danach): '.$this->missingInfo;

            return implode("\n", $lines);
        }

        if (! $this->isReplaced()) {
            $lines[] = '- Die Reklamation wurde nach Prüfung nicht als berechtigt anerkannt.';
            $lines[] = '- Grund der Ablehnung: '.$this->reason;
            $lines[] = $this->goodwill === 'ja'
                ? '- Dem Kunden wird ein Kulanzangebot unterbreitet; Art und Umfang richten sich nach dem Wissen.'
                : '- Es wird kein Kulanzangebot unterbreitet.';

            return implode("\n", $lines);
        }

        $unsure = $this->justified === 'unsicher';
        $lines[] = $unsure
            ? '- Die Reklamation ist nicht abschließend geprüft oder unsicher. Der Austausch erfolgt trotzdem, ohne Anerkennung einer Rechtspflicht. Füge im Entwurf den Zusatz zur Kulanz ohne Rechtsanerkennung hinzu, sinngemäß nach dem Wissen („Austausch ohne Anerkennung einer Rechtspflicht, gesetzliche Rechte bleiben unberührt“).'
            : '- Die Reklamation wurde nach Prüfung als berechtigt anerkannt.';
        $lines[] = match (true) {
            $unsure && $this->return === 'ja' => '- Das beanstandete Produkt soll zur weiteren, detaillierten internen Prüfung zurückgesendet werden; der Kunde erhält dafür ein kostenfreies Retourenlabel. Füge den Zusatz zum Retourenlabel sinngemäß nach dem Wissen hinzu.',
            $this->return === 'ja' => '- Das defekte Produkt soll zurückgesendet werden. Erst nach dem Eingang der Rücksendung wird ersetzt: Nenne keinen Versandtermin für den Ersatz.',
            default => '- Das defekte Produkt muss nicht zurückgesendet werden.',
        };
        $lines[] = $this->claimNumber === ''
            ? '- Die Reklamationsnummer steht noch nicht fest: Verwende im Entwurf den Platzhalter [REKLAMATIONSNUMMER], nenne keine Nummer.'
            : '- Die Reklamationsnummer lautet '.$this->claimNumber.'.';

        if ($this->return !== 'ja' || $unsure) {
            $lines[] = match ($this->shipping) {
                'heute' => '- Der Versand der Neuproduktion erfolgt voraussichtlich heute: Du darfst „voraussichtlich heute noch“ schreiben.',
                'morgen' => '- Der Versand der Neuproduktion erfolgt voraussichtlich morgen: Schreibe „voraussichtlich morgen“.',
                default => '- Der Versand der Neuproduktion erfolgt zeitnah: Nenne keinen Tag und keine Uhrzeit, schreibe „zeitnah“ oder „in den nächsten Tagen“.',
            };
        }

        if ($this->trackingTodo) {
            $lines[] = '- Die Sendungsnummer wird dem Kunden nach dem Versand mitgeteilt: Sage das im Entwurf zu.';
        }

        return implode("\n", $lines);
    }
}

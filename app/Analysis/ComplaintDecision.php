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
     * @param  'ja'|'nein'|'nicht_vorhanden'|'erhalten'  $return  Whether the defective product is sent back; "nicht_vorhanden": the customer cannot return it (never delivered, missing or lost); "erhalten": the return has already arrived and was checked; only meaningful when justified.
     * @param  'heute'|'morgen'|'zeitnah'  $shipping  When the new product is shipped; only meaningful when justified.
     * @param  bool  $awaitReturn  Answer "nein" to "processed before the customer has returned the product": the customer is asked to send the product back first, the replacement follows at the earliest after receipt; claim number, shipping and ToDo are not asked then. Only meaningful when the product is returned.
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
        public string $goodwillKind = '',
        public bool $awaitReturn = false,
        public string $extraInfo = '',
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
            return: in_array($input['retoure'] ?? null, ['ja', 'nicht_vorhanden', 'erhalten'], true) ? $input['retoure'] : 'nein',
            claimNumber: trim((string) ($input['reklamationsnummer'] ?? '')),
            shipping: in_array($input['versand'] ?? null, ['heute', 'morgen', 'zeitnah'], true) ? $input['versand'] : 'zeitnah',
            trackingTodo: filter_var($input['sendungsnummer_todo'] ?? false, FILTER_VALIDATE_BOOLEAN),
            missingInfo: trim((string) ($input['reklamation_info'] ?? '')),
            goodwillKind: trim((string) ($input['kulanz_art'] ?? '')),
            extraInfo: trim((string) ($input['reklamation_zusatz'] ?? '')),
            awaitReturn: ($input['retoure'] ?? null) === 'ja' && ($input['retoure_vor_ersatz'] ?? null) === 'nein',
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
            return [...$stored, 'reklamation_grund' => $this->reason, 'kulanz' => $this->goodwill, 'kulanz_art' => $this->goodwillKind];
        }

        if ($this->awaitReturn) {
            return [...$stored, 'retoure' => $this->return, 'retoure_vor_ersatz' => 'nein', 'reklamation_zusatz' => $this->extraInfo];
        }

        return [...$stored, 'retoure' => $this->return, 'retoure_vor_ersatz' => 'ja', 'reklamationsnummer' => $this->claimNumber, 'versand' => $this->shipping, 'sendungsnummer_todo' => $this->trackingTodo, 'reklamation_zusatz' => $this->extraInfo];
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
            if ($this->goodwill !== 'ja') {
                $lines[] = '- Es wird kein Kulanzangebot unterbreitet.';
            } elseif ($this->goodwillKind === '') {
                $lines[] = '- Dem Kunden wird ein Kulanzangebot unterbreitet; Art und Umfang richten sich nach dem Wissen.';
            } else {
                $lines[] = '- Dem Kunden wird ein Kulanzangebot unterbreitet. Art des Angebots (Entscheidung des Mitarbeiters): '.$this->goodwillKind;
                $lines[] = '- Beschreibe im Entwurf kurz den Ablauf der Kulanz: Der Kunde bestellt das Produkt neu und teilt uns die neue Bestellnummer als Antwort auf diese E-Mail mit; nach dem Versand der neuen Bestellung erstatten wir ihm den vereinbarten Betrag auf Kulanz. Nenne die Art des Angebots wie angegeben, ohne Beträge oder Prozentsätze zu ergänzen oder zu ändern.';
            }

            return implode("\n", $lines);
        }

        $unsure = $this->justified === 'unsicher';
        $lines[] = $unsure
            ? '- Die Reklamation ist nicht abschließend geprüft oder unsicher. Der Austausch erfolgt trotzdem, ohne Anerkennung einer Rechtspflicht. Füge im Entwurf den Zusatz zur Kulanz ohne Rechtsanerkennung hinzu, sinngemäß nach POLICY-076 („Austausch ohne Anerkennung einer Rechtspflicht, gesetzliche Rechte bleiben unberührt“).'
            : '- Die Reklamation wurde nach Prüfung als berechtigt anerkannt.';
        if ($this->awaitReturn) {
            $lines[] = '- Das defekte Produkt soll zurückgesendet werden; der Kunde erhält dafür ein kostenfreies Retourenlabel.'.($unsure ? ' Füge den Zusatz zum Retourenlabel sinngemäß nach POLICY-076 hinzu.' : '');
            $lines[] = '- Der Ersatz wird erst nach dem Eingang der Rücksendung versendet. Bitte den Kunden im Entwurf sinngemäß: „Bitte senden Sie uns [das Produkt] zeitnah, innerhalb der nächsten Tage mit dem Retourenlabel zurück, sodass wir Ihre Reklamation weiter bearbeiten können. Ersatz können wir frühestens nach Erhalt Ihrer Rücksendung versenden.“ Nenne keinen Versandtermin, keine Reklamationsnummer und keine Sendungsnummer für den Ersatz.';

            $this->addExtraInfo($lines);

            return implode("\n", $lines);
        }

        $lines[] = match (true) {
            $unsure && $this->return === 'ja' => '- Das beanstandete Produkt soll zur weiteren, detaillierten internen Prüfung zurückgesendet werden; der Kunde erhält dafür ein kostenfreies Retourenlabel. Füge den Zusatz zum Retourenlabel sinngemäß nach POLICY-076 hinzu.',
            $this->return === 'erhalten' => '- Die Rücksendung des defekten Produkts ist bereits bei uns eingegangen und wurde geprüft. Schreibe im Entwurf sinngemäß: „Wir haben Ihre Rücksendung erhalten und geprüft.“ Bitte den Kunden nicht noch einmal um eine Rücksendung und erstelle kein Retourenlabel.',
            $this->return === 'nicht_vorhanden' => '- Das beanstandete Produkt liegt dem Kunden nicht vor (nicht geliefert, fehlt oder ging verloren), eine Rücksendung ist daher nicht möglich. Erwähne im Entwurf keine Rücksendung und kein Retourenlabel.',
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

        $this->addExtraInfo($lines);

        return implode("\n", $lines);
    }

    /**
     * The free-text additions of the employee for a replaced complaint: answers to questions of the customer, tips and the like.
     *
     * @param  list<string>  $lines
     */
    private function addExtraInfo(array &$lines): void
    {
        if ($this->extraInfo !== '') {
            $lines[] = '- PRIORITÄRE ZUSÄTZLICHE INFORMATIONEN des Mitarbeiters (beantworte damit Fragen des Kunden bzw. gib die Hinweise oder Tipps im Entwurf weiter, verbindlich und vor allgemeinem Wissen): '.$this->extraInfo;
        }
    }
}

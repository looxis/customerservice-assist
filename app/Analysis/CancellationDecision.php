<?php

namespace App\Analysis;

use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;

/**
 * The form "Storno / Änderung" in "Vorschlag erstellen" (PROJ-102), preselected for tickets of the categories
 * `storno` and `adressaenderung`. The employee names the outcome: cancellation or change done (an exception),
 * not possible because the order is already being processed, address changed, or address change no longer possible
 * because the parcel is on its way. The app turns the answer into fixed sentences for the language model; a new
 * address reaches the model only as a placeholder, tracking numbers and links come from EOCS.
 */
final readonly class CancellationDecision
{
    public const string FORM = 'storno';

    public const string DONE = 'erledigt';

    public const string NOT_POSSIBLE = 'nicht_moeglich';

    public const string ADDRESS_CHANGED = 'adresse_geaendert';

    public const string ADDRESS_SHIPPED = 'adresse_versendet';

    public const array OUTCOMES = [self::DONE, self::NOT_POSSIBLE, self::ADDRESS_CHANGED, self::ADDRESS_SHIPPED];

    public const string CANCELLATION = 'storno';

    public const string CHANGE = 'aenderung';

    public const array KINDS = [self::CANCELLATION, self::CHANGE];

    public const string ADDRESS_PLACEHOLDER = ReshipmentDecision::ADDRESS_PLACEHOLDER;

    public const string TRACKING_PLACEHOLDER = '[SENDUNGSNUMMER]';

    /**
     * The error messages for missing required answers, by field.
     *
     * @var array<string, string>
     */
    public const array MESSAGES = [
        'storno_ergebnis' => 'Bitte das Ergebnis angeben.',
        'storno_art' => 'Bitte angeben, ob es um ein Storno oder eine Änderung geht.',
        'storno_aenderung' => 'Bitte angeben, was geändert wurde.',
        'storno_adresse' => 'Bitte die neue Adresse angeben.',
    ];

    /**
     * @param  'erledigt'|'nicht_moeglich'|'adresse_geaendert'|'adresse_versendet'  $outcome
     * @param  'storno'|'aenderung'|null  $kind  Only for "done" and "not possible": cancellation or change.
     * @param  string  $change  Only for a change that was made: what was changed.
     * @param  string  $address  Only for a changed address; reaches the model only as a placeholder.
     * @param  string  $extraInfo  Free-text notes of the employee for the draft.
     * @param  list<string>|null  $shipments  Tracking lines from EOCS ("number – link"); null when only the stored answers are read.
     */
    public function __construct(
        public string $outcome,
        public ?string $kind = null,
        public string $change = '',
        public string $address = '',
        public string $extraInfo = '',
        public ?array $shipments = null,
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
        return self::read($input)?->missing() ?? (in_array($input['storno_ergebnis'] ?? null, self::OUTCOMES, true) ? null : 'storno_ergebnis');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function read(array $input): ?self
    {
        $outcome = $input['storno_ergebnis'] ?? null;

        if (($input['formular'] ?? null) !== self::FORM || ! in_array($outcome, self::OUTCOMES, true)) {
            return null;
        }

        $withKind = in_array($outcome, [self::DONE, self::NOT_POSSIBLE], true);
        $kind = $withKind && in_array($input['storno_art'] ?? null, self::KINDS, true) ? $input['storno_art'] : null;

        return new self(
            outcome: $outcome,
            kind: $kind,
            change: $outcome === self::DONE && $kind === self::CHANGE ? trim((string) ($input['storno_aenderung'] ?? '')) : '',
            address: $outcome === self::ADDRESS_CHANGED ? trim(str_replace("\r\n", "\n", (string) ($input['storno_adresse'] ?? ''))) : '',
            extraInfo: trim((string) ($input['storno_zusatz'] ?? '')),
        );
    }

    /**
     * The field of the first required answer that is missing, or null when the answers are complete.
     */
    public function missing(): ?string
    {
        return match (true) {
            in_array($this->outcome, [self::DONE, self::NOT_POSSIBLE], true) && $this->kind === null => 'storno_art',
            $this->outcome === self::DONE && $this->kind === self::CHANGE && $this->change === '' => 'storno_aenderung',
            $this->outcome === self::ADDRESS_CHANGED && $this->address === '' => 'storno_adresse',
            default => null,
        };
    }

    /**
     * The same answers with the tracking numbers and links of the loaded orders.
     *
     * @param  list<EocsOrder>  $orders
     */
    public function withShipments(array $orders): self
    {
        $lines = [];

        foreach ($orders as $order) {
            foreach ($order->shipments as $shipment) {
                /** @var EocsShipment $shipment */
                $number = trim((string) $shipment->trackingNumber);

                if ($number === '') {
                    continue;
                }

                $carrier = trim((string) $shipment->carrier);
                $url = $shipment->trackingUrl();
                $lines[] = 'Bestellung '.$order->externalNumber.': '.($carrier !== '' ? $carrier.' ' : '').'Sendungsnummer '.$number.($url !== null ? ', Sendungsverfolgung: '.$url : '');
            }
        }

        return new self($this->outcome, $this->kind, $this->change, $this->address, $this->extraInfo, $lines);
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
            'storno_ergebnis' => $this->outcome,
            'storno_art' => $this->kind,
            'storno_aenderung' => $this->change,
            'storno_adresse' => $this->address,
            'storno_zusatz' => $this->extraInfo,
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
        $lines = ['Angaben aus dem Formular „Storno / Änderung“ (geprüfte Entscheidungen und Fakten des Mitarbeiters, sie gehen dem Wissen vor):'];

        array_push($lines, ...match ($this->outcome) {
            self::DONE => $this->doneFacts(),
            self::NOT_POSSIBLE => $this->notPossibleFacts(),
            self::ADDRESS_CHANGED => $this->addressChangedFacts(),
            self::ADDRESS_SHIPPED => $this->addressShippedFacts(),
        });

        if ($this->extraInfo !== '') {
            $lines[] = '- Zusätzliche Informationen des Mitarbeiters (vorrangig): '.$this->extraInfo;
        }

        $lines[] = '- Der Entwurf behandelt nur dieses Anliegen. Gehe nicht auf Zahlungsabwicklung, Zahlungsstatus oder Zuständigkeiten für Zahlungen ein, es sei denn, der Kunde fragt ausdrücklich danach oder die Zusätzlichen Informationen sagen etwas dazu.';

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function doneFacts(): array
    {
        if ($this->kind === self::CHANGE) {
            return [
                '- Wir haben die gewünschte Änderung an der Bestellung vorgenommen: '.$this->change.'. Bestätige dem Kunden die Änderung kurz.',
                '- Eine Änderung ist nach der Freigabe in der Regel nicht möglich; hier hat es ausnahmsweise geklappt. Du darfst das freundlich erwähnen, ohne Gründe zu erfinden.',
                '- `recommendation`: Antwort senden und das Ticket schließen.',
            ];
        }

        return [
            '- Wir haben die Bestellung storniert. Bestätige dem Kunden die Stornierung kurz.',
            '- Eine Stornierung ist in der Regel nicht möglich; hier hat es ausnahmsweise geklappt (zum Beispiel weil die Bestellung noch nicht bezahlt und freigegeben war oder die Bearbeitung noch gestoppt werden konnte). Nenne einen Grund nur, wenn er in den Zusätzlichen Informationen steht; erfinde keinen.',
            '- Sage keine Erstattung, keinen Erstattungsweg und keine Frist zu, es sei denn, die Zusätzlichen Informationen nennen sie.',
            '- `recommendation`: Antwort senden und das Ticket schließen.',
        ];
    }

    /**
     * @return list<string>
     */
    private function notPossibleFacts(): array
    {
        $what = $this->kind === self::CHANGE ? 'eine Änderung' : 'eine Stornierung';

        return [
            "- Die Bestellung ist freigegeben und bereits in Bearbeitung; {$what} ist deshalb nicht mehr möglich. Sage das klar und freundlich.",
            '- Ist die Bestellung laut Bestelldaten schon verschickt, sage, dass sie bereits versendet ist.',
            '- Die Regeln zum Vertriebskanal aus dem Wissen gelten weiter (zum Beispiel bei Amazon: keine Erstattung, eine neue Bestellung ist jederzeit möglich; Prüfung eines Abbruchs erst ab dem dort genannten Auftragswert).',
        ];
    }

    /**
     * @return list<string>
     */
    private function addressChangedFacts(): array
    {
        return [
            '- Wir haben die Lieferadresse wie gewünscht geändert. Bestätige dem Kunden die Änderung und nenne die neue Adresse zur Kontrolle auf eigenen Zeilen, genau als Platzhalter '.self::ADDRESS_PLACEHOLDER.' (die App setzt die Adresse ein).',
            '- `recommendation`: Antwort senden und das Ticket schließen.',
        ];
    }

    /**
     * @return list<string>
     */
    private function addressShippedFacts(): array
    {
        $lines = [
            '- Die Bestellung ist bereits verschickt; die Lieferadresse können wir deshalb nicht mehr ändern. Sage das klar und freundlich.',
        ];

        if ($this->shipments === null || $this->shipments === []) {
            $lines[] = '- Nenne die Sendungsnummer genau als Platzhalter '.self::TRACKING_PLACEHOLDER.'. `recommendation`: Sendungsnummer und Link zur Sendungsverfolgung intern ergänzen, dann senden.';
        } else {
            $lines[] = '- Nenne dem Kunden noch einmal die Sendungsnummer und den Link zur Sendungsverfolgung (jeweils vollständig):';
            foreach ($this->shipments as $shipment) {
                $lines[] = '  - '.$shipment;
            }
        }

        $lines[] = '- Weise darauf hin: Über den Link zur Sendungsverfolgung kann der Kunde beim Paketdienst gegebenenfalls noch Zustelloptionen anpassen (zum Beispiel Ablageort, Wunschtermin oder Umleitung). Das liegt beim Paketdienst und nicht in unserer Hand; sage keine Umleitung zu.';

        return $lines;
    }
}

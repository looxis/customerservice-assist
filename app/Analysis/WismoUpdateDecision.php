<?php

namespace App\Analysis;

use App\Wismo\ShipmentSnapshot;

/**
 * The form "Aktualisierte WISMO-Infos" in "Vorschlag erstellen" (PROJ-101), preselected for tickets of the category
 * `wismo` and selectable on any other ticket (e.g. a ticket classified as complaint that is really about the parcel).
 * The app asks the carrier and Lettermint in code when the proposal is made and hands the model the shipment facts
 * with the reply rules of the automatic WISMO reply (PROJ-99). The draft is about the whereabouts of the parcel only.
 */
final readonly class WismoUpdateDecision
{
    public const string FORM = 'wismo_info';

    public const string NO_SHIPMENT = 'Keine Sendung mit Sendungsnummer gefunden. Bitte die Bestellung laden oder ein anderes Formular wählen.';

    /**
     * @param  string  $extraInfo  Free-text notes of the employee for the draft.
     * @param  ShipmentSnapshot|null  $snapshot  The shipment state asked when the proposal is made; null when only the stored answers are read.
     */
    public function __construct(
        public string $extraInfo = '',
        public ?ShipmentSnapshot $snapshot = null,
    ) {}

    /**
     * The answers of a request, or null when another form was used.
     *
     * @param  array<string, mixed>  $input  Validated request fields or the stored inputs of an analysis.
     */
    public static function fromInput(array $input): ?self
    {
        if (($input['formular'] ?? null) !== self::FORM) {
            return null;
        }

        return new self(extraInfo: trim((string) ($input['wismo_zusatz'] ?? '')));
    }

    public function withSnapshot(ShipmentSnapshot $snapshot): self
    {
        return new self($this->extraInfo, $snapshot);
    }

    /**
     * The fields to store with the analysis, so a later proposal can prefill the form.
     *
     * @return array<string, string>
     */
    public function storedInput(): array
    {
        return ['formular' => self::FORM, 'wismo_zusatz' => $this->extraInfo];
    }

    /**
     * The sentences for the language model, added to the information of the employee.
     */
    public function facts(): string
    {
        $lines = [
            'Angaben aus dem Formular „Aktualisierte WISMO-Infos“ (geprüfte Angaben, sie gehen dem Wissen vor):',
            '- Der Kunde bekommt aktualisierte Informationen zum Verbleib seiner Sendung. Der Entwurf behandelt nur Verbleib und Zustellung der Sendung; keine Aussagen zu Mängeln, Reklamation, Erstattung, Ersatz oder anderen Anliegen, auch wenn die Nachricht des Kunden sie nahelegt.',
            '- Fasse den Sendungsverlauf kurz zusammen und lege den nächsten Schritt zum Kunden. Nach einer Rücksendung bedeutet „zugestellt“, dass die Sendung wieder bei LOOXIS ist; nenne dann nie einen Empfänger.',
            '- Der aktuelle Sendungsstatus wurde soeben in der App abgefragt und steht unten; das Werkzeug zum Sendungsstatus wird nicht gebraucht.',
        ];

        foreach ($this->snapshot?->lines ?? [] as $line) {
            $lines[] = '- '.$line;
        }

        foreach ($this->snapshot?->rules() ?? [] as $rule) {
            $lines[] = '- Regel: '.$rule;
        }

        if ($this->snapshot !== null) {
            $lines[] = $this->snapshot->closable()
                ? '- `recommendation`: Antwort senden und das Ticket schließen.'
                : '- `recommendation`: Antwort senden, das Ticket bleibt offen'.($this->snapshot->unreachable ? ' (Status nicht für jede Sendung abrufbar, bitte selbst in der Sendungsverfolgung prüfen).' : ' (Sonderfall bei der Zustellung, bitte selbst in der Sendungsverfolgung prüfen).');
        }

        if ($this->extraInfo !== '') {
            $lines[] = '- Zusätzliche Informationen des Mitarbeiters (vorrangig): '.$this->extraInfo;
        }

        return implode("\n", $lines);
    }
}

<?php

namespace App\Analysis;

/**
 * The answers of the form for technical support in "Vorschlag erstellen" (PROJ-90): payment, password, login and
 * order problems in one of the shops. The form replaces the free text; the app turns the answers into short fixed
 * sentences for the language model. Only decisions and facts go in, never a rule: the rules stay in the knowledge base.
 */
final readonly class SupportDecision
{
    public const string FORM = 'support';

    public const string KNOWN = 'ja';

    public const string UNCLEAR = 'unklar';

    public const array ANSWERS = [self::KNOWN, self::UNCLEAR];

    /**
     * @param  'ja'|'unklar'  $solution  Whether a possible solution is known, or the customer has to tell us more first.
     * @param  string  $hint  Optional note of the employee on the solution; only meaningful when it is known.
     * @param  string  $missingInfo  What the customer is asked for; only meaningful when the solution is unclear.
     */
    public function __construct(
        public string $solution,
        public string $hint = '',
        public string $missingInfo = '',
    ) {}

    /**
     * The support answers of a request, or null when another form was used.
     *
     * @param  array<string, mixed>  $input  Validated request fields or the stored inputs of an analysis.
     */
    public static function fromInput(array $input): ?self
    {
        if (($input['formular'] ?? null) !== self::FORM || ! in_array($input['support_loesung'] ?? null, self::ANSWERS, true)) {
            return null;
        }

        return new self(
            solution: $input['support_loesung'],
            hint: trim((string) ($input['support_hinweis'] ?? '')),
            missingInfo: trim((string) ($input['support_info'] ?? '')),
        );
    }

    public function isKnown(): bool
    {
        return $this->solution === self::KNOWN;
    }

    /**
     * The fields to store with the analysis: only those of the answer that was given, so a later proposal can
     * prefill the form.
     *
     * @return array<string, string>
     */
    public function storedInput(): array
    {
        return ['formular' => self::FORM, 'support_loesung' => $this->solution, ...($this->isKnown() ? ['support_hinweis' => $this->hint] : ['support_info' => $this->missingInfo])];
    }

    /**
     * The sentences for the language model, added to the information of the employee.
     */
    public function facts(): string
    {
        $lines = ['Angaben aus dem Formular „Technischer Support“ (geprüfte Entscheidungen und Fakten des Mitarbeiters):'];
        $lines[] = '- Es handelt sich um eine technische Anfrage zu einem unserer Shops (z. B. Zahlung, Passwort, Anmeldung, Bestellvorgang). Maßgeblich ist das Wissen für den gewählten Verkaufskanal.';

        if (! $this->isKnown()) {
            $lines[] = '- Die Lösung ist noch unklar: Sage keine Lösung zu und nenne keine vermutete Ursache als Tatsache.';
            $lines[] = '- Für die Lösung werden diese Informationen benötigt (bitte den Kunden gezielt und freundlich darum): '.$this->missingInfo;

            return implode("\n", $lines);
        }

        $lines[] = '- Eine mögliche Lösung ist bekannt: Beschreibe sie dem Kunden Schritt für Schritt.';
        $lines[] = $this->hint === ''
            ? '- Die Lösung ergibt sich aus dem Wissen. Enthält das Wissen keine passende Lösung, erfinde keine und weise auf das fehlende Wissen hin.'
            : '- Hinweis des Mitarbeiters zur Lösung (halte dich daran und ergänze passendes Wissen, z. B. die richtige Adresse des Shops): '.$this->hint;

        return implode("\n", $lines);
    }
}

<?php

namespace App\Analysis;

use App\Orders\OrderNumberDetector;
use Illuminate\Support\HtmlString;

/**
 * Replaces contact data with named placeholders before anything goes to the
 * language model, and puts the original values back afterwards – only in
 * the app. One instance per analysis, so numbering stays consistent.
 */
class Pseudonymizer
{
    public const string DELIVERY_ADDRESS = '[LIEFERADRESSE]';

    private const string EMAIL = '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/u';

    private const string PHONE = '/(?<![\w+\-])(?<!\d\.)(?:\+|0)\d[\d \/\-()]{6,}\d(?![\w\-])(?!\.\d)/u';

    /**
     * Street and number in German, Dutch, French and Italian spelling
     * (e.g. "Musterstraße 12", "Zonnedauwlaan 8", "12 rue de la Paix", "Via Roma 5").
     */
    private const string STREET = '/\b(?:[A-ZÄÖÜ][\wäöüßéèêàç.\-]*(?:straße|strasse|str\.|weg|allee|platz|gasse|ring|damm|ufer|chaussee|steig|pfad|markt|straat|laan|plein|gracht|kade|dijk|singel|hof|dreef)\s+\d+\s?[a-zA-Z]?'
        .'|\d+[a-z]?,?\s+(?:rue|avenue|boulevard|chemin|allée|place|impasse)\s+[\wéèêàç\'\- ]{2,40}'
        .'|(?:Via|Viale|Piazza|Corso|Largo|Vicolo)\s+[A-ZÄÖÜ][\wàèéìòù\'\- ]{1,40}?,?\s+\d+[a-zA-Z]?)\b/u';

    /**
     * Postcode and town: German/French/Italian (5 digits) and Dutch (4 digits, 2 letters).
     */
    /**
     * Postal code and town on one line. Not a number that a label marks as
     * something else ("Artikel 11282 Zaubertasse", "Nachricht 82186") and not
     * a number followed by a unit ("12345 Stück").
     */
    private const string ZIP_CITY = '/(?<!Nr\. |Nr |Nr\.: |Artikel |Art\. |Nachricht |Menge |Anzahl |ID |ID: |Rechnung |Bestellung |Auftrag |Ticket |Position |Charge |\#|\-)'
        .'\b(?:\d{5}|\d{4}\s?[A-Z]{2})[ \t]+(?!(?:Stück|Stk|Euro|EUR|Exemplare|Tassen|Mal|Tage|Uhr|Bilder|Fotos|Artikel|Pakete)\b)[A-ZÄÖÜ][\wäöüßéèêàç\-]+(?:[ \t][A-ZÄÖÜ][\wäöüßéèêàç\-]+)?/u';

    /** @var array<string, string> Placeholder => original value. */
    private array $values = [];

    /** @var array<string, int> */
    private array $counters = [];

    /** @var list<string> Exact strings that stand for the delivery address. */
    private array $deliveryParts = [];

    public function __construct(private readonly OrderNumberDetector $orders) {}

    /**
     * Make the delivery address known: its lines are replaced by [LIEFERADRESSE]
     * wherever they appear, and the placeholder can be used in the reply.
     *
     * @param  list<string>  $lines  e.g. name, street, "12345 City", country
     */
    public function withDeliveryAddress(array $lines): self
    {
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));

        if ($lines !== []) {
            $this->values[self::DELIVERY_ADDRESS] = implode("\n", $lines);
            $this->deliveryParts = array_values(array_filter($lines, fn (string $line): bool => mb_strlen($line) >= 5 && preg_match('/\d/', $line)));
        }

        return $this;
    }

    public function hasDeliveryAddress(): bool
    {
        return isset($this->values[self::DELIVERY_ADDRESS]);
    }

    public function apply(string $text): string
    {
        foreach ($this->deliveryParts as $part) {
            $text = str_ireplace($part, self::DELIVERY_ADDRESS, $text);
        }

        $text = $this->replace(self::EMAIL, 'E-MAIL', $text);
        $text = $this->replace(self::PHONE, 'TELEFON', $text, fn (string $match): bool => $this->isPhone($match));
        $text = $this->replace(self::STREET, 'ADRESSE', $text);

        return $this->replace(self::ZIP_CITY, 'ADRESSE', $text);
    }

    /**
     * Put the original values back into a text from the model. Known
     * placeholders are marked as inserted by the app, unknown ones as
     * "please fill in". The text is escaped; the result is safe HTML.
     */
    public function restore(string $text): HtmlString
    {
        $html = e($text);

        $html = preg_replace_callback('/\[[A-ZÄÖÜ][A-ZÄÖÜ\-]*(?:_\d+)?\]/u', function (array $match): string {
            $placeholder = $match[0];

            if (! isset($this->values[$placeholder])) {
                return '<mark class="placeholder-missing" title="Bitte ausfüllen">'.$placeholder.'</mark>';
            }

            return '<mark class="placeholder-filled" title="Von der App eingesetzt">'.str_replace("\n", '<br>', e($this->values[$placeholder])).'</mark>';
        }, $html) ?? $html;

        return new HtmlString(nl2br($html, false));
    }

    /**
     * Plain text with the original values put back, e.g. for copying.
     */
    public function restorePlain(string $text): string
    {
        return strtr($text, $this->values);
    }

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * @param  array<string, string>  $values
     */
    public static function fromValues(array $values): self
    {
        $instance = app(self::class);
        $instance->values = $values;

        return $instance;
    }

    private function replace(string $pattern, string $kind, string $text, ?callable $accept = null): string
    {
        return preg_replace_callback($pattern, function (array $match) use ($kind, $accept): string {
            if ($accept !== null && ! $accept($match[0])) {
                return $match[0];
            }

            $existing = array_search($match[0], $this->values, true);

            if (is_string($existing) && str_starts_with($existing, "[{$kind}_")) {
                return $existing;
            }

            $this->counters[$kind] = ($this->counters[$kind] ?? 0) + 1;
            $placeholder = "[{$kind}_{$this->counters[$kind]}]";
            $this->values[$placeholder] = $match[0];

            return $placeholder;
        }, $text) ?? $text;
    }

    private function isPhone(string $candidate): bool
    {
        $digits = preg_replace('/\D/', '', $candidate) ?? '';

        return strlen($digits) >= 8 && strlen($digits) <= 15
            && $this->orders->normalize($candidate) === null;
    }
}

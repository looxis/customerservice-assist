<?php

namespace App\ProductForge;

use App\Settings\PatternBuilder;
use App\Settings\SettingsStore;

/**
 * Finds article numbers (SKUs) a customer names in a text, but only next to a keyword such as "SKU" or
 * "Artikelnummer": a bare number could be anything (postcode, amount). Keywords, the number of digits and the
 * highest number of hits come from the settings (PROJ-49). The result only proposes a product; the employee confirms it.
 */
class SkuDetector
{
    public function __construct(private readonly SettingsStore $settings) {}

    /**
     * @return list<string>
     */
    public static function defaultKeywords(): array
    {
        return [
            'SKU', 'Artikelnummer', 'Artikel-Nr.', 'Artikel-Nr', 'Artikel Nr.', 'Artikel Nr', 'Artikelnr.', 'Artikelnr', 'Art.-Nr.', 'Art.-Nr', 'Art. Nr.', 'Art. Nr', 'Art Nr', 'Art.Nr.', 'Artnr.', 'Artnr',
            'Produktnummer', 'Produkt-Nr.', 'Produkt-Nr', 'Produkt Nr.', 'Produkt Nr', 'Produktnr.', 'Produktnr', 'Item no.', 'Item no', 'Item number', 'Article no.', 'Article no', 'Article number',
        ];
    }

    /**
     * @param  list<string>  $texts
     * @param  int|null  $max  Replaces the saved highest number.
     * @return list<string> Distinct SKUs in order of appearance, as written.
     */
    public function in(array $texts, ?int $max = null): array
    {
        return $this->with($texts, (array) $this->settings->value('sku_keywords'), (int) $this->settings->value('sku_digits'), $max ?? (int) $this->settings->value('sku_max'));
    }

    /**
     * The same detection with given rules (to try out settings that are not saved yet).
     *
     * @param  list<string>  $texts
     * @param  list<string>  $keywords
     * @return list<string>
     */
    public function with(array $texts, array $keywords, int $digits, int $max): array
    {
        if ($keywords === []) {
            return [];
        }

        usort($keywords, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $number = '(?<!\d)\d{'.max(1, $digits).'}(?!\d)';
        $pattern = '/(?<![\p{L}\d])(?:'.implode('|', PatternBuilder::phrases($keywords)).')(?!\p{L})\s*[:.#\-–]?\s*('.$number.'(?:\s*(?:,|\/|und|u\.|and|&)\s*'.$number.')*)/iu';
        $found = [];

        foreach ($texts as $text) {
            if (preg_match_all($pattern, $text, $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $group) {
                preg_match_all('/\d{'.max(1, $digits).'}/', $group, $numbers);

                foreach ($numbers[0] as $found_number) {
                    $found[$found_number] = $found_number;
                }
            }
        }

        return array_slice(array_values($found), 0, max(1, $max));
    }
}

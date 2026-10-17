<?php

namespace App\ProductForge;

/**
 * Finds article numbers (SKUs) a customer names in a text, but only next to a keyword such as "SKU" or
 * "Artikelnummer": a bare five-digit number could be anything (postcode, amount). Article numbers have
 * five digits. The result only proposes a product; the employee confirms it.
 */
class SkuDetector
{
    private const string KEYWORD = '(?:sku|art(?:ikel)?[\s.\-]*(?:nr|nummer|no)\b\.?|artnr\.?|produkt[\s.\-]*(?:nr|nummer)\b\.?|item\s*(?:no|number)\b\.?|article\s*(?:no|number)\b\.?)';

    /**
     * @param  list<string>  $texts
     * @return list<string> At most $max distinct SKUs in order of appearance, as written.
     */
    public function in(array $texts, int $max = 3): array
    {
        $found = [];
        $pattern = '/(?<![\p{L}\d])'.self::KEYWORD.'\s*[:.#\-–]?\s*((?<!\d)\d{5}(?!\d)(?:\s*(?:,|\/|und|u\.|and|&)\s*(?<!\d)\d{5}(?!\d))*)/iu';

        foreach ($texts as $text) {
            if (preg_match_all($pattern, $text, $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $group) {
                preg_match_all('/\d{5}/', $group, $numbers);

                foreach ($numbers[0] as $number) {
                    $found[$number] = $number;
                }
            }
        }

        return array_slice(array_values($found), 0, $max);
    }
}

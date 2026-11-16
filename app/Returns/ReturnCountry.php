<?php

namespace App\Returns;

/**
 * The countries a return label can be ordered from (PROJ-64): DHL has one return receiver for each of them, all
 * leading to the same warehouse. All are in the EU, so no customs documents are needed.
 */
enum ReturnCountry: string
{
    case Germany = 'DE';
    case Austria = 'AT';
    case Belgium = 'BE';
    case France = 'FR';
    case Italy = 'IT';
    case Luxembourg = 'LU';
    case Netherlands = 'NL';

    public function label(): string
    {
        return match ($this) {
            self::Germany => 'Deutschland',
            self::Austria => 'Österreich',
            self::Belgium => 'Belgien',
            self::France => 'Frankreich',
            self::Italy => 'Italien',
            self::Luxembourg => 'Luxemburg',
            self::Netherlands => 'Niederlande',
        };
    }

    /**
     * The country as DHL's Returns API names it (ISO 3166-1 alpha-3, lower case).
     */
    public function dhlCode(): string
    {
        return match ($this) {
            self::Germany => 'deu',
            self::Austria => 'aut',
            self::Belgium => 'bel',
            self::France => 'fra',
            self::Italy => 'ita',
            self::Luxembourg => 'lux',
            self::Netherlands => 'nld',
        };
    }

    public function isDomestic(): bool
    {
        return $this === self::Germany;
    }

    public function postalCodePattern(): string
    {
        return match ($this) {
            self::Germany, self::France, self::Italy => '/^\d{5}$/',
            self::Austria, self::Belgium, self::Luxembourg => '/^\d{4}$/',
            self::Netherlands => '/^\d{4} ?[A-Za-z]{2}$/',
        };
    }

    public function postalCodeHint(): string
    {
        return match ($this) {
            self::Germany, self::France, self::Italy => 'Die Postleitzahl muss in '.$this->label().' aus genau fünf Ziffern bestehen.',
            self::Austria, self::Belgium, self::Luxembourg => 'Die Postleitzahl muss in '.$this->label().' aus genau vier Ziffern bestehen.',
            self::Netherlands => 'Die Postleitzahl muss in den Niederlanden aus vier Ziffern und zwei Buchstaben bestehen (z. B. 1234 AB).',
        };
    }

    /**
     * The language of the texts for the customer when the customer's own language is not known.
     */
    public function language(): ReturnLanguage
    {
        return match ($this) {
            self::Germany, self::Austria => ReturnLanguage::German,
            self::France => ReturnLanguage::French,
            self::Italy => ReturnLanguage::Italian,
            self::Netherlands => ReturnLanguage::Dutch,
            self::Belgium, self::Luxembourg => ReturnLanguage::English,
        };
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(fn (self $country): string => $country->value, self::cases());
    }

    /**
     * Code and name, for the selection in the form.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(self::codes(), array_map(fn (self $country): string => $country->label(), self::cases()));
    }

    /**
     * "Deutschland, Österreich, … und Niederlande", for hints.
     */
    public static function sentence(): string
    {
        $names = array_values(self::options());
        $last = array_pop($names);

        return implode(', ', $names).' und '.$last;
    }
}

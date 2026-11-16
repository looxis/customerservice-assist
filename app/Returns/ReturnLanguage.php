<?php

namespace App\Returns;

/**
 * The languages the fixed texts of a return label exist in (PROJ-64).
 */
enum ReturnLanguage: string
{
    case German = 'de';
    case English = 'en';
    case French = 'fr';
    case Italian = 'it';
    case Dutch = 'nl';

    public function label(): string
    {
        return match ($this) {
            self::German => 'Deutsch',
            self::English => 'Englisch',
            self::French => 'Französisch',
            self::Italian => 'Italienisch',
            self::Dutch => 'Niederländisch',
        };
    }

    /**
     * From the German name of a language as the language model gives it ("Französisch"); null for any other language.
     */
    public static function fromName(?string $name): ?self
    {
        $name = mb_strtolower(trim((string) $name));

        if ($name === '') {
            return null;
        }

        foreach (self::cases() as $language) {
            if (str_starts_with($name, mb_strtolower($language->label()))) {
                return $language;
            }
        }

        return match (true) {
            str_starts_with($name, 'holländisch'), str_starts_with($name, 'flämisch') => self::Dutch,
            default => null,
        };
    }

    /**
     * Code and name, for the selection on the page.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(array_map(fn (self $language): string => $language->value, self::cases()), array_map(fn (self $language): string => $language->label(), self::cases()));
    }
}

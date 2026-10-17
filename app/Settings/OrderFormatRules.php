<?php

namespace App\Settings;

use App\Orders\OrderFormats;
use App\Orders\OrderNumberFormat;
use Illuminate\Support\Str;

/**
 * Cleans and checks the list of order number formats of the settings page (PROJ-49): every format against its own
 * examples, and the formats against each other so one number belongs to exactly one format.
 */
final class OrderFormatRules
{
    public const array NEEDS = ['digit', 'letter', 'lower', 'upper'];

    public const int MAX_FORMATS = 20;

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<string>}
     */
    public static function normalize(mixed $input): array
    {
        $formats = [];
        $keys = [];
        $notes = [];

        foreach (is_array($input) ? $input : [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = trim((string) ($entry['name'] ?? ''));
            $layout = preg_replace('/\s+/u', '', (string) ($entry['layout'] ?? '')) ?? '';

            if ($name === '' && $layout === '') {
                continue;
            }

            $key = trim((string) ($entry['key'] ?? '')) ?: Str::slug($name) ?: 'format';

            while (isset($keys[$key])) {
                $key .= '-2';
            }

            $keys[$key] = true;
            $letters = $entry['letters'] ?? null;

            $formats[] = [
                'key' => $key,
                'name' => $name,
                'layout' => $layout,
                'prefix' => trim((string) ($entry['prefix'] ?? '')) ?: null,
                'upper' => $letters !== null ? $letters === 'upper' : self::truthy($entry['upper'] ?? false),
                'needs' => array_values(array_intersect(self::NEEDS, array_map('strval', (array) ($entry['needs'] ?? [])))),
                'internal' => self::truthy($entry['internal'] ?? false),
                'amazon' => self::truthy($entry['amazon'] ?? false),
                'examples' => self::lines($entry['examples'] ?? []),
                'counter_examples' => self::lines($entry['counter_examples'] ?? []),
            ];
        }

        return [$formats, $notes];
    }

    /**
     * @return list<string>
     */
    public static function errors(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return ['Es muss mindestens ein Format geben.'];
        }

        $errors = count($value) > self::MAX_FORMATS ? ['Höchstens '.self::MAX_FORMATS.' Formate.'] : [];
        $names = [];
        $amazon = 0;

        foreach ($value as $index => $data) {
            $label = 'Format '.($index + 1).' „'.($data['name'] ?: '–').'“';
            $amazon += $data['amazon'] ? 1 : 0;

            if ($data['name'] === '' || mb_strlen($data['name']) > 60) {
                $errors[] = "{$label}: Der Name fehlt oder ist länger als 60 Zeichen.";
            }

            if (isset($names[mb_strtolower($data['name'])])) {
                $errors[] = "{$label}: Der Name kommt schon vor.";
            }

            $names[mb_strtolower($data['name'])] = true;

            if (preg_match('/^[#?*A-Za-z0-9\/.-]{4,40}$/', $data['layout']) !== 1) {
                $errors[] = "{$label}: Der Aufbau besteht aus 4 bis 40 Zeichen: „#“ Ziffer, „?“ Buchstabe, „*“ Buchstabe oder Ziffer, sonst fester Text (Buchstaben, Ziffern, „-“, „/“, „.“).";

                continue;
            }

            if (preg_match('/^[*?]+$/', $data['layout']) === 1 && count($data['needs']) < 2) {
                $errors[] = "{$label}: Ein Aufbau nur aus „*“ und „?“ erfasst fast jede Zahl. Bitte mindestens zwei Haken unter „Muss enthalten“ setzen oder festen Text ergänzen.";
            }

            if ($data['prefix'] !== null && preg_match('/^[A-Za-z0-9-]{1,20}$/', $data['prefix']) !== 1) {
                $errors[] = "{$label}: Die Vorsilbe besteht aus höchstens 20 Buchstaben, Ziffern oder „-“.";
            }

            if (count($data['examples']) < 1 || count($data['examples']) > 5) {
                $errors[] = "{$label}: Bitte 1 bis 5 Beispiele eintragen, die passen müssen.";
            }

            if (count($data['counter_examples']) > 5) {
                $errors[] = "{$label}: Höchstens 5 Beispiele, die nicht passen dürfen.";
            }
        }

        if ($amazon !== 1) {
            $errors[] = 'Genau ein Format muss als „Amazon“ gekennzeichnet sein (die Amazon-Nachricht nutzt seinen Aufbau).';
        }

        return $errors === [] ? self::exampleErrors($value) : $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $value
     * @return list<string>
     */
    private static function exampleErrors(array $value): array
    {
        $errors = [];
        $formats = OrderFormats::from($value);

        foreach ($formats as $index => $format) {
            $label = 'Format '.($index + 1).' „'.$format->name.'“';

            foreach ($format->examples as $example) {
                if ($format->matches($example) === null) {
                    $errors[] = "{$label}: Das Beispiel „{$example}“ passt nicht zum Aufbau und den Haken.";

                    continue;
                }

                foreach (array_slice($formats, 0, $index) as $earlier) {
                    if ($earlier->matches($example) !== null) {
                        $errors[] = "{$label}: Das Beispiel „{$example}“ passt schon zum früheren Format „{$earlier->name}“ (Überschneidung).";
                    }
                }
            }

            foreach ($format->counterExamples as $counter) {
                if ($format->matches($counter) !== null) {
                    $errors[] = "{$label}: Das Beispiel „{$counter}“ darf nicht passen, tut es aber.";
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private static function lines(mixed $input): array
    {
        $lines = is_array($input) ? $input : preg_split('/\R/u', (string) $input);

        return array_values(array_unique(array_filter(array_map(fn (mixed $line): string => trim((string) $line), $lines ?: []), fn (string $line): bool => $line !== '')));
    }

    private static function truthy(mixed $value): bool
    {
        return $value === true || $value === 1 || in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @param  list<array<string, mixed>>  $value
     * @return list<OrderNumberFormat>
     */
    public static function formats(array $value): array
    {
        return OrderFormats::from($value);
    }
}

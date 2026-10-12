<?php

namespace App\Knowledge;

/**
 * Reads the structured `when` condition of a document (PROJ-36) and says what
 * is wrong with it. A condition is a mapping: every key must hold (AND); a
 * list of values for one key means "one of these"; `any_of` holds a list of
 * conditions of which one must hold; `not` holds a condition that must not
 * hold. Numbers are written as a value or as `min`/`max`.
 */
final class WhenSchema
{
    private const array OPERATORS = ['any_of', 'not'];

    /**
     * @param  array<string, array<string, mixed>>  $keys  The "knowledge.retrieval.when_keys" configuration.
     * @param  array<string, list<string>>  $values  Allowed values for keys with a fixed list, by the name used in `values`.
     */
    public function __construct(private readonly array $keys, private readonly array $values) {}

    /**
     * Everything wrong with the condition, in plain German; empty when it is fine.
     *
     * @return list<string>
     */
    public function errors(mixed $when): array
    {
        if (! is_array($when) || $when === [] || array_is_list($when)) {
            return ['`when` muss eine Zuordnung aus Schlüsseln und Werten sein und darf nicht leer sein.'];
        }

        return $this->condition($when);
    }

    /**
     * Every key used anywhere in the condition (also inside `any_of` and `not`).
     *
     * @return list<string>
     */
    public function usedKeys(mixed $when): array
    {
        if (! is_array($when) || array_is_list($when)) {
            return [];
        }

        $used = [];

        foreach ($when as $key => $value) {
            if ($key === 'any_of' && is_array($value)) {
                foreach ($value as $alternative) {
                    array_push($used, ...$this->usedKeys($alternative));
                }
            } elseif ($key === 'not') {
                array_push($used, ...$this->usedKeys($value));
            } elseif (isset($this->keys[$key])) {
                $used[] = (string) $key;
            }
        }

        return array_values(array_unique($used));
    }

    /**
     * @param  array<array-key, mixed>  $condition
     * @return list<string>
     */
    private function condition(array $condition): array
    {
        if ($condition === [] || array_is_list($condition)) {
            return ['Eine Bedingung in `when` darf nicht leer sein und muss aus Schlüsseln und Werten bestehen.'];
        }

        $errors = [];

        foreach ($condition as $key => $value) {
            $key = (string) $key;

            if ($key === 'any_of') {
                if (! is_array($value) || ! array_is_list($value) || $value === []) {
                    $errors[] = '`any_of` braucht eine Liste von Bedingungen.';

                    continue;
                }

                foreach ($value as $alternative) {
                    array_push($errors, ...(is_array($alternative) ? $this->condition($alternative) : ['Jeder Eintrag in `any_of` muss eine Bedingung sein.']));
                }
            } elseif ($key === 'not') {
                array_push($errors, ...(is_array($value) ? $this->condition($value) : ['`not` braucht eine Bedingung.']));
            } elseif (! isset($this->keys[$key])) {
                $errors[] = "Unbekannter Schlüssel `{$key}` in `when`. Bekannt: ".implode(', ', [...array_keys($this->keys), ...self::OPERATORS]).'.';
            } else {
                array_push($errors, ...$this->value($key, $this->keys[$key], $value));
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    private function value(string $key, array $definition, mixed $value): array
    {
        return match ($definition['type']) {
            'bool' => is_bool($value) ? [] : ["`{$key}` in `when` muss `true` oder `false` sein."],
            'number' => $this->number($key, $value),
            'list' => $this->listValue($key, $definition, $value),
            default => $this->slugs($key, $value),
        };
    }

    /**
     * @return list<string>
     */
    private function number(string $key, mixed $value): array
    {
        if (is_int($value) || is_float($value)) {
            return $value < 0 ? ["`{$key}` in `when` darf nicht negativ sein."] : [];
        }

        if (! is_array($value) || $value === [] || array_is_list($value) || array_diff(array_keys($value), ['min', 'max']) !== []) {
            return ["`{$key}` in `when` braucht eine Zahl oder eine Angabe mit `min` und/oder `max`."];
        }

        foreach ($value as $bound) {
            if (! is_int($bound) && ! is_float($bound)) {
                return ["`min` und `max` bei `{$key}` müssen Zahlen sein."];
            }

            if ($bound < 0) {
                return ["`min` und `max` bei `{$key}` dürfen nicht negativ sein."];
            }
        }

        if (isset($value['min'], $value['max']) && $value['min'] > $value['max']) {
            return ["`min` ist größer als `max` bei `{$key}`: die Bedingung kann nie zutreffen."];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    private function listValue(string $key, array $definition, mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        if ($items === [] || ! array_is_list($items) || array_filter($items, fn (mixed $item): bool => ! is_string($item) || trim($item) === '') !== []) {
            return ["`{$key}` in `when` braucht einen Wert oder eine Liste von Werten."];
        }

        $allowed = $this->values[$definition['values'] ?? ''] ?? null;

        if ($allowed === null) {
            return [];
        }

        $unknown = array_diff($items, $allowed);

        return $unknown === [] ? [] : ['Unbekannter Wert `'.implode('`, `', $unknown)."` bei `{$key}` in `when`. Erlaubt: ".implode(', ', $allowed).'.'];
    }

    /**
     * @return list<string>
     */
    private function slugs(string $key, mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        if ($items === [] || ! array_is_list($items) || array_filter($items, fn (mixed $item): bool => ! is_string($item) || preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $item) !== 1) !== []) {
            return ["`{$key}` in `when` braucht einen Slug oder eine Liste von Slugs (Kleinbuchstaben, Ziffern, Bindestriche)."];
        }

        return [];
    }
}

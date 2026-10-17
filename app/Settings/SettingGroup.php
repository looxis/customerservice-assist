<?php

namespace App\Settings;

use Closure;

/**
 * One group of settings (PROJ-27): its title, its default, how an input is cleaned and checked.
 * The page, the storage, the history and the reset work the same for every group.
 */
final readonly class SettingGroup
{
    public const string TEXTS = 'texts';

    public const string LINES = 'lines';

    public const string HOSTS = 'hosts';

    public const string NUMBER = 'number';

    public const int MAX_ENTRIES = 100;

    /**
     * @param  Closure(): mixed  $default
     * @param  self::TEXTS|self::LINES|self::HOSTS|self::NUMBER  $kind  LINES are entries of several lines each (signatures).
     */
    public function __construct(
        public string $key,
        public string $page,
        public string $title,
        public string $hint,
        public string $kind,
        private Closure $default,
        public int $minLength = 0,
        public int $maxLength = 300,
        public bool $allowEmpty = true,
        public bool $wildcard = false,
        public bool $marker = false,
        public int $max = 5,
    ) {}

    public function default(): mixed
    {
        return ($this->default)();
    }

    /**
     * The cleaned input and what was cleaned away.
     *
     * @return array{0: mixed, 1: list<string>}
     */
    public function normalize(mixed $input): array
    {
        return match ($this->kind) {
            self::NUMBER => [is_numeric($input) ? (int) $input : $input, []],
            self::HOSTS => $this->hosts($input),
            default => $this->texts($input),
        };
    }

    /**
     * Messages for an unusable (already normalized) value; empty when it is fine.
     *
     * @return list<string>
     */
    public function errors(mixed $value): array
    {
        return match ($this->kind) {
            self::NUMBER => is_int($value) && $value >= 0 && $value <= $this->max ? [] : ["Bitte eine Zahl von 0 bis {$this->max} eingeben."],
            self::HOSTS => $this->hostErrors($value),
            default => $this->textErrors($value),
        };
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function texts(mixed $input): array
    {
        $entries = [];
        $seen = [];
        $notes = [];

        foreach (is_array($input) ? $input : [] as $entry) {
            if (! is_string($entry)) {
                continue;
            }

            $text = $this->kind === self::LINES
                ? implode("\n", array_values(array_filter(array_map('trim', preg_split('/\R/u', $entry) ?: []), fn (string $line): bool => $line !== '')))
                : trim(preg_replace('/\s+/u', ' ', $entry) ?? $entry);

            if ($text === '') {
                continue;
            }

            if (isset($seen[mb_strtolower($text)])) {
                $notes[] = 'Doppelter Eintrag entfernt: '.mb_substr($text, 0, 60);

                continue;
            }

            $seen[mb_strtolower($text)] = true;
            $entries[] = $text;
        }

        return [$entries, $notes];
    }

    /**
     * @return array{0: list<array{host: string, mode: string}>, 1: list<string>}
     */
    private function hosts(mixed $input): array
    {
        $byHost = [];
        $notes = [];

        foreach (is_array($input) ? $input : [] as $entry) {
            $host = is_array($entry) ? mb_strtolower(trim((string) ($entry['host'] ?? ''))) : '';
            $mode = is_array($entry) && ($entry['mode'] ?? null) === 'clickable' ? 'clickable' : (is_array($entry) && ($entry['mode'] ?? null) === 'text' ? 'text' : ($host === '' ? 'text' : 'invalid'));

            if ($host === '' && $mode !== 'invalid') {
                continue;
            }

            if (isset($byHost[$host])) {
                $notes[] = "„{$host}“ steht mehrfach; es gilt „als Text anzeigen“, soweit eines davon so gewählt ist.";
                $byHost[$host]['mode'] = $byHost[$host]['mode'] === 'text' || $mode === 'text' ? 'text' : $mode;

                continue;
            }

            $byHost[$host] = ['host' => $host, 'mode' => $mode];
        }

        return [array_values($byHost), $notes];
    }

    /**
     * @return list<string>
     */
    private function textErrors(mixed $value): array
    {
        if (! is_array($value)) {
            return ['Ungültige Eingabe.'];
        }

        $errors = [];

        if (count($value) > self::MAX_ENTRIES) {
            $errors[] = 'Höchstens '.self::MAX_ENTRIES.' Einträge.';
        }

        if (! $this->allowEmpty && $value === []) {
            $errors[] = 'Diese Liste darf nicht leer sein.';
        }

        foreach ($value as $index => $entry) {
            $label = 'Eintrag '.($index + 1);
            $length = mb_strlen((string) $entry);

            if ($length > $this->maxLength) {
                $errors[] = "{$label}: höchstens {$this->maxLength} Zeichen.";
            }

            if ($this->minLength > 0 && $this->fixed((string) $entry) < $this->minLength) {
                $errors[] = "{$label}: mindestens {$this->minLength} Zeichen".($this->wildcard ? ' außerhalb von „…“' : '').', sonst würden harmlose Mails erfasst.';
            }

            if ($this->marker && preg_match('/^[A-Za-z0-9_-]{3,60}$/', (string) $entry) !== 1) {
                $errors[] = "{$label}: nur Buchstaben, Ziffern, „-“ und „_“ (3 bis 60 Zeichen).";
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function hostErrors(mixed $value): array
    {
        if (! is_array($value)) {
            return ['Ungültige Eingabe.'];
        }

        $errors = count($value) > self::MAX_ENTRIES ? ['Höchstens '.self::MAX_ENTRIES.' Einträge.'] : [];

        foreach ($value as $index => $entry) {
            $label = 'Eintrag '.($index + 1);
            $host = (string) ($entry['host'] ?? '');

            if (($entry['mode'] ?? null) === 'invalid') {
                $errors[] = "{$label}: bitte „als Text anzeigen“ oder „klickbar lassen“ wählen.";
            }

            if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\*)$/', $host) !== 1 || str_contains(substr($host, 0, -1), '*')) {
                $errors[] = "{$label}: „{$host}“ ist keine gültige Adresse (nur der Rechnername, z. B. looxis.de; „*“ nur am Ende als Länderendung).";
            }
        }

        return $errors;
    }

    private function fixed(string $entry): int
    {
        return $this->wildcard ? PatternBuilder::fixedCharacters($entry) : mb_strlen(preg_replace('/\s+/u', '', $entry) ?? '');
    }
}

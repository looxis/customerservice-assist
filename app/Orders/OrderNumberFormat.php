<?php

namespace App\Orders;

/**
 * One order number format (PROJ-49): how numbers of a channel look, and what else has to hold. Built from the
 * settings (or their defaults); the single place that knows how a format is recognised.
 */
final readonly class OrderNumberFormat
{
    /**
     * @param  string  $layout  Short notation: "#" digit, "?" letter, "*" letter or digit, anything else is fixed text.
     * @param  list<string>  $needs  What the number must contain: digit, letter, lower, upper.
     * @param  bool  $upper  Only capital letters (typed numbers are converted).
     * @param  bool  $internal  The internal EOCS number: loaded only on a click and looked up by its id.
     * @param  list<string>  $examples  Numbers that must match.
     * @param  list<string>  $counterExamples  Numbers that must not match.
     * @param  string  $claimPrefix  The letters in front of the claim number ("R" for "R1-302-…").
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $layout,
        public ?string $prefix = null,
        public bool $upper = false,
        public array $needs = [],
        public bool $internal = false,
        public bool $amazon = false,
        public array $examples = [],
        public array $counterExamples = [],
        public string $claimPrefix = 'R',
    ) {}

    public function label(): string
    {
        return $this->name;
    }

    /**
     * Pattern for finding the format in running text; group 1 is the number. The number must not touch other
     * letters, digits or hyphens (the internal number also not "." or ",", so amounts and dates stay out).
     */
    public function pattern(): string
    {
        $edge = $this->internal ? '[\w.,-]' : '[\w-]';
        $prefix = $this->prefix === null ? '' : '(?i:'.preg_quote($this->prefix, '/').')?';
        $claim = '(?:(?<claim>(?i:'.preg_quote($this->claimPrefix, '/').')\d{1,3})-)?';

        return '/(?<!'.$edge.')'.$claim.$prefix.'(?<number>'.$this->fragment().')(?!'.$edge.')/u';
    }

    /**
     * The pattern of the number alone, without boundaries and group.
     */
    public function fragment(): string
    {
        $letter = $this->upper ? 'A-Z' : 'A-Za-z';
        $body = '';

        foreach (mb_str_split($this->layout) as $character) {
            $body .= match ($character) {
                '#' => '\d',
                '?' => "[{$letter}]",
                '*' => "[{$letter}0-9]",
                default => preg_quote($character, '/'),
            };
        }

        return $body;
    }

    /**
     * Whether a number that fits the pattern also has what the format demands.
     */
    public function accepts(string $number): bool
    {
        $checks = [
            'digit' => '/\d/',
            'letter' => '/[A-Za-z]/',
            'lower' => '/[a-z]/',
            'upper' => '/[A-Z]/',
        ];

        foreach ($this->needs as $need) {
            if (isset($checks[$need]) && preg_match($checks[$need], $number) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a whole text is a number of this format (a typed or pasted number; prefix and case handled).
     */
    public function matches(string $input): ?string
    {
        return $this->parse($input)['number'] ?? null;
    }

    /**
     * Like matches(), and also tells the claim number ("R1-302-…" => 1) when one is written in front.
     *
     * @return array{number: string, claim: int|null}|null
     */
    public function parse(string $input): ?array
    {
        $value = preg_replace('/[\s\x{00A0}#]+/u', '', $input) ?? '';
        $claim = null;

        if (preg_match('/^'.preg_quote($this->claimPrefix, '/').'(\d{1,3})-(.+)$/iu', $value, $parts) === 1) {
            $claim = (int) $parts[1];
            $value = $parts[2];
        }

        if ($this->prefix !== null) {
            $value = preg_replace('/^'.preg_quote($this->prefix, '/').'/i', '', $value) ?? $value;
        }

        if ($this->upper) {
            $value = strtoupper($value);
        }

        return preg_match($this->pattern(), $value, $match) === 1 && $match['number'] === $value && $this->accepts($value) ? ['number' => $value, 'claim' => $claim] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'layout' => $this->layout,
            'prefix' => $this->prefix,
            'upper' => $this->upper,
            'needs' => $this->needs,
            'internal' => $this->internal,
            'amazon' => $this->amazon,
            'examples' => $this->examples,
            'counter_examples' => $this->counterExamples,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $claimPrefix = 'R'): self
    {
        return new self(
            (string) $data['key'],
            (string) $data['name'],
            (string) $data['layout'],
            ($data['prefix'] ?? null) === null || $data['prefix'] === '' ? null : (string) $data['prefix'],
            (bool) ($data['upper'] ?? false),
            array_values((array) ($data['needs'] ?? [])),
            (bool) ($data['internal'] ?? false),
            (bool) ($data['amazon'] ?? false),
            array_values((array) ($data['examples'] ?? [])),
            array_values((array) ($data['counter_examples'] ?? [])),
            $claimPrefix,
        );
    }
}

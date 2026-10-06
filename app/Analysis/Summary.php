<?php

namespace App\Analysis;

use Carbon\CarbonImmutable;

/**
 * Summary of the thread before the last customer message. Kept as readable
 * text with the original values (contact data is replaced again before every
 * use), together with what it covers.
 */
final readonly class Summary
{
    public const array SECTIONS = [
        'facts' => 'Sachverhalt',
        'customer_wish' => 'Kundenwunsch',
        'measures' => 'Bisherige Maßnahmen und Zusagen',
        'decisions' => 'Entscheidungen',
        'corrections' => 'Korrekturen',
        'open_questions' => 'Offene Fragen',
    ];

    public function __construct(
        public string $text,
        public ?string $until,
        public string $fingerprint,
        public bool $edited,
        public string $model,
        public string $promptVersion,
        public CarbonImmutable $createdAt,
    ) {}

    public function isStale(string $fingerprint): bool
    {
        return $this->fingerprint !== $fingerprint;
    }

    public function withText(string $text): self
    {
        return new self(trim($text), $this->until, $this->fingerprint, true, $this->model, $this->promptVersion, $this->createdAt);
    }

    /**
     * @param  array<string, string|null>  $sections
     */
    public static function textFrom(array $sections): string
    {
        $parts = [];

        foreach (self::SECTIONS as $key => $label) {
            $content = trim((string) ($sections[$key] ?? ''));
            $parts[] = "## {$label}\n".($content === '' ? '–' : $content);
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text, 'until' => $this->until, 'fingerprint' => $this->fingerprint, 'edited' => $this->edited,
            'model' => $this->model, 'prompt_version' => $this->promptVersion, 'created_at' => $this->createdAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['text'], $data['until'], $data['fingerprint'], (bool) $data['edited'], $data['model'], $data['prompt_version'], CarbonImmutable::parse($data['created_at']));
    }
}

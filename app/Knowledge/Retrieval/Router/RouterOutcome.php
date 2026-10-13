<?php

namespace App\Knowledge\Retrieval\Router;

/**
 * What the router did in one analysis (PROJ-37), for the retrieval, the log and the figures.
 */
final readonly class RouterOutcome
{
    public const string EXECUTED = 'executed';

    public const string SKIPPED = 'skipped';

    public const string DISABLED = 'disabled';

    public const string FAILED = 'failed';

    /**
     * @param  list<RouterAddition>  $additions  Taken over, most important first.
     * @param  array<string, mixed>  $details  Pool, proposals, categories, cut, model, tokens… as stored in the log.
     */
    public function __construct(
        public string $status,
        public string $reason,
        public array $additions = [],
        public array $details = [],
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public int $durationMs = 0,
    ) {}

    public static function disabled(string $reason): self
    {
        return new self(self::DISABLED, $reason);
    }

    public function failed(): bool
    {
        return $this->status === self::FAILED;
    }

    public function executed(): bool
    {
        return $this->status === self::EXECUTED;
    }

    /**
     * @return list<string>
     */
    public function additionIds(): array
    {
        return array_map(fn (RouterAddition $addition): string => (string) $addition->document->id, $this->additions);
    }

    /**
     * Estimated cost in US dollars when a price is configured for the model; otherwise null.
     */
    public function cost(): ?float
    {
        $price = config('knowledge.router.prices.'.($this->details['model'] ?? ''));

        if (! is_array($price) || ! isset($price['input'], $price['output'])) {
            return null;
        }

        return round(($this->inputTokens * (float) $price['input'] + $this->outputTokens * (float) $price['output']) / 1_000_000, 6);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
            'additions' => array_map(fn (RouterAddition $addition): array => ['id' => $addition->document->id, 'strength' => $addition->strength, 'rank' => $addition->rank, 'reason' => $addition->reason], $this->additions),
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'duration_ms' => $this->durationMs,
            'cost' => $this->cost(),
            ...$this->details,
        ];
    }
}

<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;

/**
 * A ticket that may belong to the same case as the one on the screen (PROJ-52): only what is needed to offer it.
 */
final readonly class RelatedTicket
{
    public const string LINKED = 'linked';

    public const string ORDER = 'order';

    public const string CUSTOMER = 'customer';

    public const string MANUAL = 'manual';

    public function __construct(
        public int $id,
        public string $number,
        public string $title,
        public string $state,
        public int $articleCount,
        public ?CarbonImmutable $createdAt,
        public string $source = self::LINKED,
    ) {}

    public function from(string $source): self
    {
        return new self($this->id, $this->number, $this->title, $this->state, $this->articleCount, $this->createdAt, $source);
    }

    /**
     * @return array{number: string, title: string, state: string, articles: int, date: string|null, source: string}
     */
    public function toArray(): array
    {
        return ['number' => $this->number, 'title' => $this->title, 'state' => $this->state, 'articles' => $this->articleCount, 'date' => $this->createdAt?->toIso8601String(), 'source' => $this->source];
    }
}

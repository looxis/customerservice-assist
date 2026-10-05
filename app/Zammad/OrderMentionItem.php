<?php

namespace App\Zammad;

final readonly class OrderMentionItem
{
    public function __construct(
        public string $name,
        public ?string $asin = null,
        public ?string $sku = null,
        public ?int $quantity = null,
    ) {}

    public function key(): string
    {
        return $this->asin ?? $this->sku ?? $this->name;
    }
}

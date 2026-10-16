<?php

namespace App\ProductForge;

/**
 * One candidate of the product search; carries identity only, no content.
 */
final readonly class ForgeSearchHit
{
    /**
     * @param  array{id: int, name: string, sku: ?string}|null  $master
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $sku,
        public string $type,
        public string $status,
        public ?array $master,
    ) {}

    /**
     * @return array{id: int, name: string, sku: ?string, type: string, status: string, master: array{id: int, name: string, sku: ?string}|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'type' => $this->type,
            'status' => $this->status,
            'master' => $this->master,
        ];
    }
}

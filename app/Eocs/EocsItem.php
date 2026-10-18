<?php

namespace App\Eocs;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * An item (product master data) of EOCS (docs/items-api.md). Its number "item_id" is the SKU that
 * ProductForge takes over. Only used when a product is not yet maintained in ProductForge: description
 * and bullet points ("short_description") are all the app takes from here.
 */
final readonly class EocsItem
{
    public function __construct(
        public int $itemId,
        public string $name,
        public ?string $description,
        public ?string $shortDescription,
        public bool $active,
        public ?string $productType,
        public CarbonImmutable $fetchedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data  One entry of "data" of the items API.
     */
    public static function fromApi(array $data, ?CarbonImmutable $fetchedAt = null): ?self
    {
        if (! is_int($data['item_id'] ?? null) || ! is_string($data['name'] ?? null)) {
            return null;
        }

        return new self(
            $data['item_id'],
            trim($data['name']),
            is_string($data['description'] ?? null) ? $data['description'] : null,
            is_string($data['short_description'] ?? null) ? $data['short_description'] : null,
            ($data['deactivated_at'] ?? null) === null,
            is_string($data['product_type']['name'] ?? null) ? $data['product_type']['name'] : null,
            $fetchedAt ?? CarbonImmutable::now(),
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function fromSnapshot(array $snapshot): ?self
    {
        try {
            return new self(
                (int) $snapshot['item_id'],
                (string) $snapshot['name'],
                isset($snapshot['description']) ? (string) $snapshot['description'] : null,
                isset($snapshot['short_description']) ? (string) $snapshot['short_description'] : null,
                (bool) ($snapshot['active'] ?? true),
                isset($snapshot['product_type']) ? (string) $snapshot['product_type'] : null,
                CarbonImmutable::parse((string) $snapshot['fetched_at']),
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'item_id' => $this->itemId,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->shortDescription,
            'active' => $this->active,
            'product_type' => $this->productType,
            'fetched_at' => $this->fetchedAt->toIso8601String(),
        ];
    }

    /**
     * The bullet points as plain lines (the markup of EOCS is dropped).
     */
    public function bulletPoints(): ?string
    {
        return self::plain($this->shortDescription);
    }

    public function plainDescription(): ?string
    {
        return self::plain($this->description);
    }

    /**
     * @return array{id: int, name: string, sku: string, type: string, status: string, master: null}
     */
    public function identity(): array
    {
        return ['id' => $this->itemId, 'name' => $this->name, 'sku' => (string) $this->itemId, 'type' => $this->productType ?? 'standalone', 'status' => $this->active ? 'active' : 'inactive', 'master' => null];
    }

    private static function plain(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = preg_replace('/<(br|\/p|\/div|\/li)\b[^>]*>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace("/[ \t\x{00A0}]+\n/u", "\n", $text) ?? $text);

        return $text === '' ? null : $text;
    }
}

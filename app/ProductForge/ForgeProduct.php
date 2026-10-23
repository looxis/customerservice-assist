<?php

namespace App\ProductForge;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * One product as ProductForge delivers it (docs/productforge-product-api.md).
 * The content is already resolved there (master/variant inheritance, knowledge
 * origins); nothing is inherited or combined again in this app.
 */
final readonly class ForgeProduct
{
    public const array TEXT_FIELDS = ['technical_data', 'included_in_delivery', 'care_instructions', 'warnings'];

    /**
     * @param  array{id: int, name: string, sku: ?string, type: ?string, status: ?string}|null  $master
     * @param  array<string, mixed>  $content
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $sku,
        public string $type,
        public string $status,
        public ?array $master,
        public string $resolvedVia,
        public ?string $requestedSku,
        public ?string $historicalSkuValidUntil,
        public array $content,
        public string $dataVersion,
        public CarbonImmutable $fetchedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The "data" object of a detail response.
     */
    public static function fromResponse(array $data, ?CarbonImmutable $fetchedAt = null): self
    {
        $product = $data['product'] ?? null;
        $resolution = $data['resolution'] ?? [];
        $content = $data['content'] ?? null;

        if (! is_array($product) || ! is_int($product['id'] ?? null) || ! is_string($product['name'] ?? null)
            || ! is_array($resolution) || ! is_array($content) || ! is_string($data['data_version'] ?? null)) {
            throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse);
        }

        self::assertContentShape($content);

        $master = $product['master'] ?? null;

        return new self(
            id: $product['id'],
            name: $product['name'],
            sku: is_string($product['sku'] ?? null) ? $product['sku'] : null,
            type: is_string($product['type'] ?? null) ? $product['type'] : 'standalone',
            status: is_string($product['status'] ?? null) ? $product['status'] : 'active',
            master: is_array($master) && is_int($master['id'] ?? null) ? [
                'id' => $master['id'],
                'name' => is_string($master['name'] ?? null) ? $master['name'] : '',
                'sku' => is_string($master['sku'] ?? null) ? $master['sku'] : null,
                'type' => is_string($master['type'] ?? null) ? $master['type'] : null,
                'status' => is_string($master['status'] ?? null) ? $master['status'] : null,
            ] : null,
            resolvedVia: is_string($resolution['resolved_via'] ?? null) ? $resolution['resolved_via'] : 'id',
            requestedSku: is_string($resolution['requested_sku'] ?? null) ? $resolution['requested_sku'] : null,
            historicalSkuValidUntil: is_string($resolution['historical_sku_valid_until'] ?? null) ? $resolution['historical_sku_valid_until'] : null,
            content: $content,
            dataVersion: $data['data_version'],
            fetchedAt: $fetchedAt ?? CarbonImmutable::now(),
        );
    }

    /**
     * Restores a stored data package without asking ProductForge again.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        try {
            return new self(
                id: (int) $snapshot['id'],
                name: (string) $snapshot['name'],
                sku: isset($snapshot['sku']) ? (string) $snapshot['sku'] : null,
                type: (string) ($snapshot['type'] ?? 'standalone'),
                status: (string) ($snapshot['status'] ?? 'active'),
                master: is_array($snapshot['master'] ?? null) ? $snapshot['master'] : null,
                resolvedVia: (string) ($snapshot['resolved_via'] ?? 'id'),
                requestedSku: isset($snapshot['requested_sku']) ? (string) $snapshot['requested_sku'] : null,
                historicalSkuValidUntil: isset($snapshot['historical_sku_valid_until']) ? (string) $snapshot['historical_sku_valid_until'] : null,
                content: is_array($snapshot['content'] ?? null) ? $snapshot['content'] : [],
                dataVersion: (string) ($snapshot['data_version'] ?? ''),
                fetchedAt: CarbonImmutable::parse((string) $snapshot['fetched_at']),
            );
        } catch (Throwable) {
            throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse);
        }
    }

    /**
     * The complete retrieved data package, kept as a snapshot of the analysis.
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'type' => $this->type,
            'status' => $this->status,
            'master' => $this->master,
            'resolved_via' => $this->resolvedVia,
            'requested_sku' => $this->requestedSku,
            'historical_sku_valid_until' => $this->historicalSkuValidUntil,
            'data_version' => $this->dataVersion,
            'fetched_at' => $this->fetchedAt->toIso8601String(),
            'content' => $this->content,
        ];
    }

    /**
     * Effective text of a text field (technical_data, included_in_delivery, care_instructions, warnings).
     */
    public function text(string $field): ?string
    {
        return self::clean($this->content[$field]['combined'] ?? null);
    }

    /**
     * Effective description of one channel ("shop" or "amazon"); never the other channel's text.
     */
    public function description(string $channel): ?string
    {
        return self::clean($this->content['description'][$channel]['combined'] ?? null);
    }

    /**
     * Effective bullet points of one channel in position order; empty positions are left out.
     *
     * @return list<string>
     */
    public function bulletPoints(string $channel): array
    {
        $positions = $this->content['bullet_points']['positions'] ?? [];
        $texts = [];

        foreach (is_array($positions) ? $positions : [] as $position) {
            $text = is_array($position) ? self::clean($position[$channel] ?? null) : null;

            if ($text !== null) {
                $texts[] = $text;
            }
        }

        return $texts;
    }

    /**
     * Effective user manual (content.user_manual): how the product is used. Missing in older stored
     * data packages, then null.
     */
    public function userManual(): ?string
    {
        $manual = is_array($this->content['user_manual'] ?? null) ? $this->content['user_manual'] : [];

        return self::clean($manual['combined'] ?? null);
    }

    /**
     * @return array{combined: ?string, origin_text: ?string, own_text: ?string, own_text_shared: ?string, own_text_product_only: ?string, origin: ?array<string, mixed>, holder: ?array<string, mixed>}
     */
    public function serviceKnowledge(): array
    {
        $knowledge = is_array($this->content['customer_service_knowledge'] ?? null) ? $this->content['customer_service_knowledge'] : [];

        return [
            'combined' => self::clean($knowledge['combined'] ?? null),
            'origin_text' => self::clean($knowledge['origin_text'] ?? null),
            'own_text' => self::clean($knowledge['own_text'] ?? null),
            'own_text_shared' => self::clean($knowledge['own_text_shared'] ?? null),
            'own_text_product_only' => self::clean($knowledge['own_text_product_only'] ?? null),
            'origin' => is_array($knowledge['origin'] ?? null) ? $knowledge['origin'] : null,
            'holder' => is_array($knowledge['holder'] ?? null) ? $knowledge['holder'] : null,
        ];
    }

    public function hasServiceKnowledge(): bool
    {
        return $this->serviceKnowledge()['combined'] !== null;
    }

    public function isHistoricalSku(): bool
    {
        return $this->resolvedVia === 'historical_sku';
    }

    /**
     * Identity as shown in the product list (without content).
     *
     * @return array{id: int, name: string, sku: ?string, type: string, status: string, master: ?array<string, mixed>}
     */
    public function identity(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'sku' => $this->sku, 'type' => $this->type, 'status' => $this->status, 'master' => $this->master];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private static function assertContentShape(array $content): void
    {
        foreach ([...self::TEXT_FIELDS, 'description', 'bullet_points', 'customer_service_knowledge', 'user_manual'] as $field) {
            if (array_key_exists($field, $content) && ! is_array($content[$field])) {
                throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse);
            }
        }
    }

    private static function clean(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}

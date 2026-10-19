<?php

namespace App\Eocs;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * The variants of one master product of EOCS (docs/items-api.md, "Master-Produkte und Varianten"):
 * name, SKU and whether still active, nothing else. "chosen" are the SKUs of the products of the case
 * that belong to this master. Names are third-party text and are reduced to one line.
 */
final readonly class EocsVariantList
{
    /**
     * @param  list<array{sku: int, name: string, active: bool}>  $variants  The variants shown, at most the limit.
     * @param  list<int>  $chosen  SKUs of the products of the case that belong to this master.
     */
    public function __construct(
        public int $masterId,
        public string $masterName,
        public array $variants,
        public int $omitted,
        public array $chosen,
        public CarbonImmutable $fetchedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $master  An item of EOCS that is a master, with its "variants".
     * @param  list<int>  $chosen
     */
    public static function fromMaster(array $master, array $chosen, int $limit, ?CarbonImmutable $fetchedAt = null): ?self
    {
        if (! is_int($master['id'] ?? null) || ! is_array($master['variants'] ?? null)) {
            return null;
        }

        $variants = [];

        foreach ($master['variants'] as $row) {
            $name = is_array($row) && is_string($row['name'] ?? null) ? self::oneLine($row['name']) : '';

            if (! is_int($row['item_id'] ?? null) || $name === '' || isset($variants[$row['item_id']])) {
                continue;
            }

            $variants[$row['item_id']] = ['sku' => $row['item_id'], 'name' => $name, 'active' => ($row['deactivated_at'] ?? null) === null];
        }

        if ($variants === []) {
            return null;
        }

        $all = array_values($variants);

        return new self(
            $master['id'],
            is_string($master['name'] ?? null) ? self::oneLine($master['name']) : 'Master',
            array_slice($all, 0, max(1, $limit)),
            max(0, count($all) - max(1, $limit)),
            $chosen,
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
                (int) $snapshot['master_id'],
                (string) $snapshot['master_name'],
                array_values(array_map(fn (mixed $row): array => ['sku' => (int) $row['sku'], 'name' => (string) $row['name'], 'active' => (bool) $row['active']], (array) $snapshot['variants'])),
                (int) ($snapshot['omitted'] ?? 0),
                array_values(array_map('intval', (array) ($snapshot['chosen'] ?? []))),
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
            'master_id' => $this->masterId,
            'master_name' => $this->masterName,
            'variants' => $this->variants,
            'omitted' => $this->omitted,
            'chosen' => $this->chosen,
            'fetched_at' => $this->fetchedAt->toIso8601String(),
        ];
    }

    public function withChosen(int $sku): self
    {
        return in_array($sku, $this->chosen, true) ? $this : new self($this->masterId, $this->masterName, $this->variants, $this->omitted, [...$this->chosen, $sku], $this->fetchedAt);
    }

    public function isTruncated(): bool
    {
        return $this->omitted > 0;
    }

    /**
     * The variants other than the chosen products, as the form shows them.
     *
     * @return list<array{sku: int, name: string, active: bool}>
     */
    public function others(): array
    {
        return array_values(array_filter($this->variants, fn (array $variant): bool => ! in_array($variant['sku'], $this->chosen, true)));
    }

    /**
     * Single line, no control characters, so a name can never become an instruction of its own.
     */
    private static function oneLine(string $text): string
    {
        $text = preg_replace('/[\p{C}\s]+/u', ' ', $text) ?? $text;

        return mb_substr(trim($text), 0, 200);
    }
}

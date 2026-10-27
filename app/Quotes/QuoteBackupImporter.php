<?php

namespace App\Quotes;

use App\Models\QuoteArticle;
use App\Models\QuoteOffer;
use InvalidArgumentException;

/**
 * Reads the backup file of the old browser calculator (LOOXIS_Rabattrechner_Backup_*.json) into the database.
 * Repeatable: articles are matched by SKU, offers by number. Costs of an article only count when the old
 * calculator had them confirmed ("purchaseSet" / "machineSet"); automatic suggestions are left out.
 */
final class QuoteBackupImporter
{
    private const string COST_PREFIX = 'looxis_chatbot_shop_cost_';

    private const string OFFERS_KEY = 'looxis_chatbot_saved_offers_v1';

    /**
     * @return array{articles: int, with_costs: int, offers: int}
     */
    public function import(string $json, string $importedBy = 'Import'): array
    {
        $data = json_decode($json, true);

        if (! is_array($data) || ($data['app'] ?? null) !== 'looxis-rabattrechner' || ! is_array($data['products'] ?? null)) {
            throw new InvalidArgumentException('Das ist keine Sicherung des Rabattrechners.');
        }

        $storage = is_array($data['storage'] ?? null) ? $data['storage'] : [];
        $articles = 0;
        $withCosts = 0;

        foreach ($data['products'] as $product) {
            if (! is_array($product) || ($product['sku'] ?? '') === '' || ! is_numeric($product['vk'] ?? null) || ($product['exact'] ?? true) === false) {
                continue;
            }

            $costs = $this->costs($storage[self::COST_PREFIX.($product['id'] ?? $product['sku'])] ?? null, $importedBy);

            QuoteArticle::updateOrCreate(['sku' => (string) $product['sku']], [
                'name' => (string) $product['name'],
                'category' => $product['category'] ?? null,
                'shop_price_gross' => (float) $product['vk'],
                'active' => ($product['active'] ?? true) !== false,
                ...$costs,
            ]);

            $articles++;
            $withCosts += ($costs['purchase_cost'] ?? null) !== null && ($costs['machine_minutes'] ?? null) !== null ? 1 : 0;
        }

        return ['articles' => $articles, 'with_costs' => $withCosts, 'offers' => $this->offers($storage[self::OFFERS_KEY] ?? null, $importedBy)];
    }

    /**
     * @return array<string, mixed>
     */
    private function costs(mixed $raw, string $importedBy): array
    {
        $cost = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($cost)) {
            return [];
        }

        $purchaseSet = ($cost['purchaseSet'] ?? false) === true && is_numeric($cost['costBase'] ?? null);
        $machineSet = ($cost['machineSet'] ?? false) === true && is_numeric($cost['machineMin'] ?? null);

        $rawArticles = $purchaseSet ? array_values(array_map(fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'qty' => (float) ($row['qty'] ?? 1),
            'unit_cost' => (float) ($row['unitCost'] ?? 0),
        ], array_filter((array) ($cost['rawArticles'] ?? []), 'is_array'))) : null;

        return [
            'raw_articles' => $rawArticles,
            'purchase_cost' => $purchaseSet ? round((float) $cost['costBase'], 2) : null,
            'machine_minutes' => $machineSet ? (float) $cost['machineMin'] : null,
            'labor_minutes' => (float) ($cost['laborMin'] ?? 3),
            'hour_rate' => (float) ($cost['hourRate'] ?? 60),
            'machine_rate' => (float) ($cost['machineRate'] ?? 60),
            'pack_cost' => (float) ($cost['packCost'] ?? 0),
            'ship_cost' => (float) ($cost['shipCost'] ?? 0),
            'other_cost' => (float) ($cost['otherCost'] ?? 0),
            'target_margin' => (float) ($cost['targetMargin'] ?? 30),
            'vat_rate' => (float) ($cost['vatRate'] ?? 19),
            'costs_updated_by' => $importedBy,
        ];
    }

    private function offers(mixed $raw, string $importedBy): int
    {
        $offers = is_string($raw) ? json_decode($raw, true) : null;
        $count = 0;

        foreach (is_array($offers) ? $offers : [] as $offer) {
            if (! is_array($offer) || ($offer['number'] ?? '') === '' || ! is_array($offer['items'] ?? null)) {
                continue;
            }

            QuoteOffer::updateOrCreate(['number' => (string) $offer['number']], [
                'created_on' => $offer['created'] ?? now()->toDateString(),
                'valid_until' => $offer['validUntil'] ?? now()->addDays(7)->toDateString(),
                'company' => $offer['company'] ?? null,
                'contact' => $offer['contact'] ?? null,
                'email' => $offer['email'] ?? null,
                'street' => $offer['street'] ?? null,
                'city' => $offer['city'] ?? null,
                'note' => $offer['note'] ?? null,
                'items' => array_values(array_map(fn (array $item): array => [
                    'sku' => (string) ($item['sku'] ?? ''),
                    'name' => (string) ($item['name'] ?? ''),
                    'qty' => (int) ($item['qty'] ?? 1),
                    'discount_pct' => (float) ($item['discountPct'] ?? 0),
                    'unit_gross' => (float) ($item['unitGross'] ?? 0),
                    'vat' => (float) ($item['vat'] ?? 19),
                ], array_filter($offer['items'], 'is_array'))),
                'created_by' => $importedBy,
            ]);

            $count++;
        }

        return $count;
    }
}

<?php

namespace App\ProductForge;

use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsItem;

/**
 * Builds the data package for one analysis. Only the chosen products are fetched; whatever the earlier
 * step of the same case already holds is carried over unchanged (no silent update, one retrieval time
 * per product). A fresh analysis always fetches again. Nothing is searched fuzzily: an unknown SKU or
 * id stays unknown.
 */
class ProductResolver
{
    /**
     * @var array<int, ForgeProduct>
     */
    private array $retrieved = [];

    public function __construct(private readonly ProductForgeClient $client, private readonly EocsClient $eocs) {}

    /**
     * @param  list<array{id: int, sku: string|null}>  $selection  The products that concern the case; "sku" is the SKU a position was found by.
     * @param  array<string, mixed>|null  $carried  Stored package of the earlier step; only used when the case is continued.
     * @param  array<string, mixed>|null  $previous  Stored package of the last analysis, only to point out changed data.
     * @param  list<int>  $eocsSelection  Numbers (SKU) of EOCS items for products that ProductForge does not have.
     */
    public function resolve(array $selection, ?array $carried, ?array $previous = null, array $eocsSelection = []): ProductPackage
    {
        $carriedPackage = ProductPackage::fromStored($carried);
        $products = $carriedPackage->products;
        $selected = [];
        $reused = [];
        $problems = [];
        $seen = [];

        foreach ($selection as $entry) {
            $id = (int) $entry['id'];

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $selected[] = $id;

            if ($carried !== null && $carriedPackage->find($id) !== null) {
                $reused[] = $id;

                continue;
            }

            try {
                $product = $this->fetch($id, $entry['sku'] ?? null);
            } catch (ProductForgeException $exception) {
                $problems[] = ['id' => $id, 'sku' => $entry['sku'] ?? null, 'name' => null, 'problem' => $exception->problem->value];
                $selected = array_values(array_diff($selected, [$id]));

                continue;
            }

            $products = [...array_values(array_filter($products, fn (ForgeProduct $other): bool => $other->id !== $product->id)), $product];
        }

        $changed = [];

        foreach (ProductPackage::fromStored($previous)->products as $before) {
            $now = in_array($before->id, $reused, true) ? null : ($this->retrieved[$before->id] ?? null);

            if ($now !== null && $now->dataVersion !== $before->dataVersion) {
                $changed[] = $before->id;
            }
        }

        $items = $carriedPackage->eocsItems;
        $eocsSelected = [];
        $eocsReused = [];

        foreach (array_values(array_unique(array_map('intval', $eocsSelection))) as $number) {
            if ($carried !== null && $carriedPackage->findEocs($number) !== null) {
                $eocsSelected[] = $number;
                $eocsReused[] = $number;

                continue;
            }

            try {
                $item = $this->eocs->item((string) $number);
            } catch (EocsException) {
                $problems[] = ['id' => $number, 'sku' => (string) $number, 'name' => null, 'problem' => 'eocs_unavailable'];

                continue;
            }

            if ($item === null) {
                $problems[] = ['id' => $number, 'sku' => (string) $number, 'name' => null, 'problem' => 'eocs_not_found'];

                continue;
            }

            $eocsSelected[] = $number;
            $items = [...array_values(array_filter($items, fn (EocsItem $other): bool => $other->itemId !== $number)), $item];
        }

        return new ProductPackage($products, $selected, $problems, $reused, $changed, $items, $eocsSelected, $eocsReused);
    }

    /**
     * @throws ProductForgeException
     */
    private function fetch(int $id, ?string $sku): ForgeProduct
    {
        if (isset($this->retrieved[$id])) {
            return $this->retrieved[$id];
        }

        if ($sku !== null && $sku !== '' && (bool) config('services.productforge.order_lookup')) {
            $product = $this->client->bySku($sku);

            if ($product->id === $id) {
                return $this->retrieved[$id] = $product;
            }
        }

        return $this->retrieved[$id] = $this->client->byId($id);
    }
}

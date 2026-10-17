<?php

namespace App\ProductForge;

use App\Eocs\EocsItem;

/**
 * The data package of one analysis: every product retrieved from ProductForge (complete content, each
 * with its own retrieval time), which of them concern the case, and what could not be retrieved.
 * It is stored with the analysis so a continuation can choose other fields from it without asking again.
 */
final readonly class ProductPackage
{
    /**
     * @param  list<ForgeProduct>  $products  Everything retrieved or carried over.
     * @param  list<int>  $selected  Ids of the products that concern the case.
     * @param  list<array{id: int|null, sku: string|null, name: string|null, problem: string}>  $problems
     * @param  list<int>  $reused  Ids carried over from the earlier step of the case (not retrieved again).
     * @param  list<int>  $changed  Ids whose data_version differs from the last analysis of the ticket.
     * @param  list<EocsItem>  $eocsItems  Items of EOCS for products that ProductForge does not have (yet).
     * @param  list<int>  $eocsSelected  Numbers (SKU) of the EOCS items that concern the case.
     * @param  list<int>  $eocsReused  Numbers of the EOCS items carried over from the earlier step.
     */
    public function __construct(
        public array $products = [],
        public array $selected = [],
        public array $problems = [],
        public array $reused = [],
        public array $changed = [],
        public array $eocsItems = [],
        public array $eocsSelected = [],
        public array $eocsReused = [],
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>|null  $stored
     */
    public static function fromStored(?array $stored): self
    {
        if ($stored === null) {
            return self::empty();
        }

        $products = [];

        foreach ((array) ($stored['products'] ?? []) as $snapshot) {
            try {
                $products[] = ForgeProduct::fromSnapshot((array) $snapshot);
            } catch (ProductForgeException) {
                continue;
            }
        }

        return new self(
            $products,
            array_values(array_map('intval', (array) ($stored['selected'] ?? []))),
            array_values((array) ($stored['problems'] ?? [])),
            array_values(array_map('intval', (array) ($stored['reused'] ?? []))),
            array_values(array_map('intval', (array) ($stored['changed'] ?? []))),
            array_values(array_filter(array_map(fn (mixed $snapshot): ?EocsItem => is_array($snapshot) ? EocsItem::fromSnapshot($snapshot) : null, (array) ($stored['eocs_items'] ?? [])))),
            array_values(array_map('intval', (array) ($stored['eocs_selected'] ?? []))),
            array_values(array_map('intval', (array) ($stored['eocs_reused'] ?? []))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toStored(): array
    {
        return [
            'products' => array_map(fn (ForgeProduct $product): array => $product->toSnapshot(), $this->products),
            'selected' => $this->selected,
            'problems' => $this->problems,
            'reused' => $this->reused,
            'changed' => $this->changed,
            'eocs_items' => array_map(fn (EocsItem $item): array => $item->toSnapshot(), $this->eocsItems),
            'eocs_selected' => $this->eocsSelected,
            'eocs_reused' => $this->eocsReused,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->products === [] && $this->eocsItems === [] && $this->problems === [];
    }

    public function find(int $id): ?ForgeProduct
    {
        foreach ($this->products as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    public function findEocs(int $itemId): ?EocsItem
    {
        foreach ($this->eocsItems as $item) {
            if ($item->itemId === $itemId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * The EOCS items (products not yet in ProductForge) that concern the case.
     *
     * @return list<EocsItem>
     */
    public function affectedEocs(): array
    {
        $affected = [];

        foreach ($this->eocsSelected as $id) {
            $item = $this->findEocs($id);

            if ($item !== null) {
                $affected[] = $item;
            }
        }

        return $affected;
    }

    /**
     * The products that concern the case, in the order of the selection.
     *
     * @return list<ForgeProduct>
     */
    public function affected(): array
    {
        $affected = [];

        foreach ($this->selected as $id) {
            $product = $this->find($id);

            if ($product !== null) {
                $affected[] = $product;
            }
        }

        return $affected;
    }

    public function hasServiceKnowledge(): bool
    {
        foreach ($this->affected() as $product) {
            if ($product->hasServiceKnowledge()) {
                return true;
            }
        }

        return false;
    }

    /**
     * ok = everything selected is there, partial = something is missing, failed = nothing usable, none = not used.
     */
    public function status(): string
    {
        if ($this->selected === [] && $this->eocsSelected === [] && $this->problems === []) {
            return 'none';
        }

        if ($this->affected() === [] && $this->affectedEocs() === []) {
            return 'failed';
        }

        return $this->problems === [] && $this->affectedEocs() === [] ? 'ok' : 'partial';
    }

    /**
     * The name of the affected product(s), used when the employee named no knowledge area.
     */
    public function displayName(): ?string
    {
        $names = [...array_map(fn (ForgeProduct $product): string => $product->name, $this->affected()), ...array_map(fn (EocsItem $item): string => $item->name, $this->affectedEocs())];

        return $names === [] ? null : mb_substr(implode(', ', $names), 0, 120);
    }

    /**
     * @param  list<int>  $selected
     */
    public function withSelected(array $selected): self
    {
        return new self($this->products, $selected, $this->problems, $this->reused, $this->changed, $this->eocsItems, $this->eocsSelected, $this->eocsReused);
    }
}

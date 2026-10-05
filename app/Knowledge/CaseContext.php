<?php

namespace App\Knowledge;

/**
 * What the knowledge is selected by: the customer group and the products
 * concerned. No products means "no product reference / unclear".
 */
final readonly class CaseContext
{
    /** @var list<string> */
    public array $products;

    /**
     * @param  list<string>  $products  Product slugs; order and duplicates do not matter.
     */
    public function __construct(public CustomerGroup $customerGroup, array $products = [])
    {
        $products = array_values(array_unique(array_filter(array_map('trim', $products), fn (string $product): bool => $product !== '')));
        sort($products);

        $this->products = $products;
    }

    public function label(): string
    {
        $products = $this->products === [] ? 'kein Produktbezug' : implode(', ', $this->products);

        return "{$this->customerGroup->label}; Produkte: {$products}";
    }
}

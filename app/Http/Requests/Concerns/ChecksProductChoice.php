<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

/**
 * The product answer of the proposal form (PROJ-42): at least one of a product from the knowledge base,
 * a product without knowledge (typed name) or an explicit "no product". The last excludes the others.
 */
trait ChecksProductChoice
{
    /**
     * @return array<string, list<string>>
     */
    protected function productRules(): array
    {
        return [
            'kein_produktbezug' => ['sometimes', 'boolean'],
            'produkt_ohne_wissen' => ['nullable', 'string', 'max:120'],
            'forge_produkte' => ['sometimes', 'array', 'max:'.(int) config('analysis.product_info.max_selected')],
            'forge_produkte.*' => ['integer', 'min:1'],
            'forge_sku' => ['sometimes', 'array', 'max:'.(int) config('analysis.product_info.max_selected')],
            'forge_sku.*' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function checkProductChoice(Validator $validator): void
    {
        $hasProduct = array_values(array_filter((array) $this->input('produkte', []), 'is_string')) !== [];
        $hasName = trim((string) $this->input('produkt_ohne_wissen', '')) !== '';
        $hasCatalogProduct = $this->forgeSelection() !== [];
        $none = $this->boolean('kein_produktbezug');

        if (! $hasProduct && ! $hasName && ! $hasCatalogProduct && ! $none) {
            $validator->errors()->add('produkte', 'Bitte ein Produkt wählen, ein Produkt ohne hinterlegtes Wissen nennen oder „Kein Produktbezug“ bestätigen.');
        } elseif ($none && ($hasProduct || $hasName || $hasCatalogProduct)) {
            $validator->errors()->add('kein_produktbezug', 'Entweder Produkte angeben oder „Kein Produktbezug“ wählen, nicht beides.');
        }
    }

    /**
     * The typed name of a product without knowledge, or null.
     */
    public function unknownProduct(): ?string
    {
        $name = trim((string) $this->input('produkt_ohne_wissen', ''));

        return $name === '' || $this->boolean('kein_produktbezug') ? null : $name;
    }

    /**
     * The products of the catalog (ProductForge) that concern the case; "sku" is the SKU an order
     * position was found by, sent unchanged.
     *
     * @return list<array{id: int, sku: string|null}>
     */
    public function forgeSelection(): array
    {
        $skus = (array) $this->input('forge_sku', []);
        $selection = [];

        foreach ((array) $this->input('forge_produkte', []) as $id) {
            if (is_numeric($id) && (int) $id > 0 && ! isset($selection[(int) $id])) {
                $sku = $skus[(int) $id] ?? null;
                $selection[(int) $id] = ['id' => (int) $id, 'sku' => is_string($sku) && $sku !== '' ? $sku : null];
            }
        }

        return array_values($selection);
    }
}

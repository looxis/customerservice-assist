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
        ];
    }

    protected function checkProductChoice(Validator $validator): void
    {
        $hasProduct = array_values(array_filter((array) $this->input('produkte', []), 'is_string')) !== [];
        $hasName = trim((string) $this->input('produkt_ohne_wissen', '')) !== '';
        $none = $this->boolean('kein_produktbezug');

        if (! $hasProduct && ! $hasName && ! $none) {
            $validator->errors()->add('produkte', 'Bitte ein Produkt wählen, ein Produkt ohne hinterlegtes Wissen nennen oder „Kein Produktbezug“ bestätigen.');
        } elseif ($none && ($hasProduct || $hasName)) {
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
}

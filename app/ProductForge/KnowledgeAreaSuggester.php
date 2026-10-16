<?php

namespace App\ProductForge;

use App\Knowledge\KnowledgeSuggester;

/**
 * Proposes the area of the Markdown knowledge (its product slug, e.g. "Essbare Fotos") for a catalog
 * product. No SKU table: the name of the product (and of its master) is matched against the order
 * keywords and customer terms the knowledge files already carry. The result only prefills the form.
 */
class KnowledgeAreaSuggester
{
    public function __construct(private readonly KnowledgeSuggester $suggester) {}

    /**
     * @param  array{name: string, sku: string|null, master?: array{name?: string}|null}  $product
     * @return list<string>
     */
    public function slugsFor(array $product): array
    {
        $names = array_values(array_filter([$product['name'], $product['master']['name'] ?? null], fn (?string $name): bool => is_string($name) && trim($name) !== ''));

        $fromKeywords = $this->suggester->products([['article_number' => $product['sku'], 'description' => implode(' ', $names)]]);
        $fromTerms = array_keys($this->suggester->productsFromText($names));

        return array_values(array_unique([...$fromKeywords, ...$fromTerms]));
    }
}

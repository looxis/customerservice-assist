<?php

namespace App\Knowledge;

use App\Analysis\GuidedForms;
use Illuminate\Support\Collection;

/**
 * The case patterns of the guided fact-finding (PROJ-103): playbooks with a recognition text that fit the customer
 * group, the products and the kind of concern. Same scope rule as the knowledge retrieval, no language model.
 */
class CasePatternFinder
{
    public function __construct(
        private readonly KnowledgeLibrary $library,
        private readonly KnowledgeSelector $selector,
    ) {}

    /**
     * The knowledge categories whose patterns fit: those of the chosen guided form, else those of the ticket's
     * category. Null offers every pattern (no category known, "sonstiges"); an empty list only patterns without a
     * category.
     *
     * @return list<string>|null
     */
    public function knowledgeCategories(?string $ticketCategory, ?string $form): ?array
    {
        $ticketCategories = GuidedForms::categoriesOf($form);

        if ($ticketCategories === []) {
            $ticketCategories = $ticketCategory === null || $ticketCategory === '' ? [] : [$ticketCategory];
        }

        if ($ticketCategories === []) {
            return null;
        }

        $map = (array) config('knowledge.pattern_categories', []);
        $categories = [];

        foreach ($ticketCategories as $category) {
            $mapped = $map[$category] ?? '*';

            if ($mapped === '*') {
                return null;
            }

            array_push($categories, ...(array) $mapped);
        }

        return array_values(array_unique($categories));
    }

    /**
     * The fitting patterns, those bound to a chosen product first, then by ID.
     *
     * @param  list<string>  $products
     * @param  list<string>|null  $categories  From knowledgeCategories().
     * @return list<KnowledgeDocument>
     */
    public function find(CustomerGroup $group, array $products, ?array $categories): array
    {
        return $this->patterns()
            ->filter(fn (KnowledgeDocument $document): bool => $this->exclusionReason($document, $group, $products, $categories) === null)
            ->sortBy(fn (KnowledgeDocument $document): string => ($this->isForProduct($document, $products) ? '0' : '1').$document->id)
            ->values()
            ->all();
    }

    /**
     * Why a pattern does not fit the case, or null when it does; also null for an ID that is no pattern any more,
     * which callers check with pattern().
     *
     * @param  list<string>  $products
     * @param  list<string>|null  $categories
     */
    public function exclusionReason(KnowledgeDocument $document, CustomerGroup $group, array $products, ?array $categories): ?string
    {
        $scope = $this->selector->scopeExclusionReason($document, $group, $products);

        if ($scope !== null) {
            return $scope;
        }

        $own = $document->categories();

        if ($categories !== null && $own !== [] && array_intersect($own, $categories) === []) {
            return 'passt nicht zur Art des Anliegens';
        }

        return null;
    }

    /**
     * Whether the pattern is bound to one of the chosen products.
     *
     * @param  list<string>  $products
     */
    public function isForProduct(KnowledgeDocument $document, array $products): bool
    {
        return array_intersect($document->boundProducts(), $products) !== [];
    }

    /**
     * A usable pattern by its ID, or null.
     */
    public function pattern(string $id): ?KnowledgeDocument
    {
        return $this->patterns()->first(fn (KnowledgeDocument $document): bool => $document->id === $id);
    }

    /**
     * @return Collection<int, KnowledgeDocument>
     */
    private function patterns(): Collection
    {
        return $this->library->usable()->filter(fn (KnowledgeDocument $document): bool => $document->isCasePattern())->values();
    }
}

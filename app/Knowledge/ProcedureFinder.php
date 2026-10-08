<?php

namespace App\Knowledge;

/**
 * Internal procedures for a case (PROJ-30): suggested after the analysis
 * from its category and recommended actions, within the same scope rules as
 * the knowledge selection; all others can be picked by hand.
 */
class ProcedureFinder
{
    public function __construct(
        private readonly KnowledgeLibrary $library,
        private readonly KnowledgeSelector $selector,
    ) {}

    /**
     * Procedures for the recommended actions, in the order of the actions,
     * within an action by ID; each only once.
     *
     * @param  list<string>  $products
     * @param  list<string>  $actions
     * @return list<KnowledgeDocument>
     */
    public function suggested(CustomerGroup $group, array $products, ?string $category, array $actions): array
    {
        $procedures = $this->procedures();
        $suggested = [];

        foreach ($actions as $action) {
            foreach ($procedures as $procedure) {
                if (isset($suggested[$procedure->id]) || ! in_array($action, $procedure->actions(), true)) {
                    continue;
                }

                $categories = $procedure->categories();

                if ($categories !== [] && ! in_array($category, $categories, true)) {
                    continue;
                }

                if ($this->selector->scopeExclusionReason($procedure, $group, $products) === null) {
                    $suggested[$procedure->id] = $procedure;
                }
            }
        }

        return array_values($suggested);
    }

    /**
     * All usable procedures grouped by action (in the order of the action
     * list), each with a note when it does not fit the customer group or the
     * chosen products.
     *
     * @param  list<string>  $products
     * @return array<string, list<array{document: KnowledgeDocument, note: string|null}>>
     */
    public function catalogue(CustomerGroup $group, array $products): array
    {
        $grouped = [];

        foreach (array_keys(config('knowledge.actions')) as $action) {
            foreach ($this->procedures() as $procedure) {
                if (in_array($action, $procedure->actions(), true)) {
                    $grouped[$action][] = ['document' => $procedure, 'note' => match (true) {
                        $group->exclusionReason($procedure) !== null => 'gilt nicht für diese Kundengruppe',
                        $this->selector->scopeExclusionReason($procedure, $group, $products) !== null => 'gilt nur für: '.implode(', ', $procedure->boundProducts()),
                        default => null,
                    }];
                }
            }
        }

        return $grouped;
    }

    /**
     * A usable procedure by its ID, or null.
     */
    public function find(string $id): ?KnowledgeDocument
    {
        return collect($this->procedures())->first(fn (KnowledgeDocument $document): bool => $document->id === $id);
    }

    /**
     * @return list<KnowledgeDocument>
     */
    private function procedures(): array
    {
        return $this->library->usable()
            ->filter(fn (KnowledgeDocument $document): bool => $document->type === 'procedure')
            ->sortBy(fn (KnowledgeDocument $document): string => (string) $document->id)
            ->values()
            ->all();
    }
}

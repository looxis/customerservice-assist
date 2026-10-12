<?php

namespace App\Knowledge\Retrieval;

use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeSelector;
use Illuminate\Support\Collection;

/**
 * Checks beforehand that the mandatory knowledge of every customer group (and
 * every product) fits the budget (PROJ-36), so a document that makes it too
 * big is noticed when it is added, not by a customer. Mandatory knowledge is
 * core knowledge and binding knowledge without trigger, condition and action.
 * Documents without a class are left out here: they only count as core during
 * the transition and are listed as "not classified yet".
 */
class MandatoryScope
{
    public function __construct(
        private readonly KnowledgeLibrary $library,
        private readonly KnowledgeSelector $selector,
    ) {}

    /**
     * Usable documents that have no `retrieval` class yet.
     *
     * @return Collection<int, KnowledgeDocument>
     */
    public function unclassified(): Collection
    {
        return $this->library->usable()
            ->reject(fn (KnowledgeDocument $document): bool => in_array($document->type, config('knowledge.selection.excluded_types'), true))
            ->filter(fn (KnowledgeDocument $document): bool => $document->retrieval() === null)
            ->values();
    }

    /**
     * @return list<string> Plain German messages, one per exceeded budget.
     */
    public function violations(): array
    {
        $budget = config('knowledge.retrieval.budget');
        $messages = [];
        $products = [[], ...array_map(fn (array $product): array => [$product['slug']], $this->selector->products())];

        foreach ($this->selector->customerGroups() as $group) {
            foreach ($products as $chosen) {
                $documents = array_values(array_filter(
                    array_map(fn ($entry): KnowledgeDocument => $entry->document, $this->selector->scope(new CaseContext($group, $chosen))['selected']),
                    fn (KnowledgeDocument $document): bool => $this->isMandatory($document),
                ));
                $general = $this->characters(array_filter($documents, fn (KnowledgeDocument $document): bool => $document->boundProducts() === []));
                $bound = $this->characters(array_filter($documents, fn (KnowledgeDocument $document): bool => $document->boundProducts() !== []));
                $label = $group->label.($chosen === [] ? '' : ', Produkt '.$chosen[0]);

                if ($general > $budget['core']) {
                    $messages["general|{$group->key}"] = "Kundengruppe {$group->label}: Das Pflichtwissen ohne Produktbezug umfasst ".$this->number($general).' Zeichen (Grenze '.$this->number($budget['core']).').';
                }

                if ($chosen !== [] && $bound > $budget['core_per_product']) {
                    $messages["product|{$group->key}|{$chosen[0]}"] = "{$label}: Das Pflichtwissen zum Produkt umfasst ".$this->number($bound).' Zeichen (Grenze '.$this->number($budget['core_per_product']).').';
                }

                if ($general + $bound > $budget['target']) {
                    $messages["total|{$group->key}|".implode(',', $chosen)] = "{$label}: Das Pflichtwissen umfasst zusammen ".$this->number($general + $bound).' Zeichen und liegt über dem Zielumfang '.$this->number($budget['target']).'.';
                }
            }
        }

        return array_values($messages);
    }

    private function isMandatory(KnowledgeDocument $document): bool
    {
        if (in_array($document->type, ['example-good', 'example-bad'], true)) {
            return false;
        }

        return match ($document->retrieval()) {
            'core' => true,
            'binding' => $document->triggers() === [] && $document->when() === [] && $document->actions() === [],
            default => false,
        };
    }

    /**
     * @param  iterable<KnowledgeDocument>  $documents
     */
    private function characters(iterable $documents): int
    {
        $sum = 0;

        foreach ($documents as $document) {
            $sum += $document->length();
        }

        return $sum;
    }

    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}

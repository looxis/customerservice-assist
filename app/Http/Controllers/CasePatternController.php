<?php

namespace App\Http\Controllers;

use App\Http\Requests\CasePatternRequest;
use App\Knowledge\CasePatternFinder;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\SalesChannels;
use Illuminate\Http\JsonResponse;

/**
 * The case patterns of the guided fact-finding (PROJ-103) for the scope the form shows right now. Only the knowledge
 * files are read: no language model, no outside system.
 */
class CasePatternController extends Controller
{
    public function __invoke(CasePatternRequest $request, CasePatternFinder $finder, KnowledgeSelector $selector, SalesChannels $channels): JsonResponse
    {
        $channel = $request->validated('verkaufskanal');
        $group = $this->group($selector, $channels, (string) $request->validated('kundengruppe', ''), is_string($channel) ? $channel : null);
        $products = $channels->withoutProducts(is_string($channel) ? $channel : null) ? [] : array_values(array_intersect((array) $request->validated('produkte', []), array_column($selector->products(), 'slug')));
        $categories = $finder->knowledgeCategories($request->validated('kategorie'), $request->validated('formular'));
        $names = array_column($selector->products(), 'name', 'slug');

        $dropped = [];

        foreach ((array) $request->validated('gewaehlt', []) as $id) {
            $pattern = $finder->pattern($id);
            $reason = $pattern === null ? 'nicht mehr im Wissen' : $finder->exclusionReason($pattern, $group, $products, $categories);

            if ($reason !== null) {
                $dropped[$id] = ['title' => $pattern?->title ?? $id, 'reason' => $reason];
            }
        }

        return response()->json([
            'patterns' => array_map(fn (KnowledgeDocument $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'recognize' => $document->recognize(),
                'for_product' => $finder->isForProduct($document, $products),
                'products' => array_values(array_map(fn (string $slug): string => $names[$slug] ?? $slug, array_intersect($document->boundProducts(), $products))),
                'checks' => $document->checks(),
                'customer_check' => $document->customerCheck(),
            ], $finder->find($group, $products, $categories)),
            'dropped' => (object) $dropped,
        ]);
    }

    private function group(KnowledgeSelector $selector, SalesChannels $channels, string $key, ?string $channel): CustomerGroup
    {
        $group = $selector->customerGroup($key)
            ?? collect($selector->selectableGroups())->first(fn (CustomerGroup $item): bool => $item->isUnclear());

        return $channels->applyTo($group, $channel);
    }
}

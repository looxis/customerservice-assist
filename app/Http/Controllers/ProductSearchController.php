<?php

namespace App\Http\Controllers;

use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsItem;
use App\Http\Requests\ProductSearchRequest;
use App\ProductForge\ForgeProduct;
use App\ProductForge\ForgeSearchHit;
use App\ProductForge\KnowledgeAreaSuggester;
use App\ProductForge\ProductForgeClient;
use App\ProductForge\ProductForgeException;
use Illuminate\Http\JsonResponse;

/**
 * The product search of the proposal form (PROJ-38). The browser never talks to ProductForge: the app
 * asks with its own token and only passes on identity and what exists, not the product content.
 */
class ProductSearchController extends Controller
{
    public function __invoke(ProductSearchRequest $request, ProductForgeClient $client, KnowledgeAreaSuggester $areas, EocsClient $eocs): JsonResponse
    {
        if (! $client->isConfigured()) {
            return response()->json(['enabled' => false, 'hits' => [], 'error' => null]);
        }

        try {
            if ($request->filled('id')) {
                $product = $client->byId((int) $request->validated('id'));

                return response()->json(['enabled' => true, 'product' => $this->details($product, $areas), 'error' => null]);
            }

            $page = $client->search((string) $request->validated('q'), (int) $request->validated('seite', 1), 20, 'all');
        } catch (ProductForgeException $exception) {
            return response()->json(['enabled' => true, 'hits' => [], 'product' => null, 'error' => $exception->problem->message()], $exception->problem->value === 'not_found' ? 404 : 200);
        }

        if ($page->hits === [] && $page->page === 1) {
            return $this->fromEocs((string) $request->validated('q'), $eocs, $areas);
        }

        return response()->json([
            'enabled' => true,
            'hits' => array_map(fn (ForgeSearchHit $hit): array => [...$hit->toArray(), 'origin' => 'forge', 'key' => 'forge:'.$hit->id, 'knowledge_slugs' => $areas->slugsFor($hit->toArray())], $page->hits),
            'total' => $page->total,
            'page' => $page->page,
            'last_page' => $page->lastPage,
            'error' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function details(ForgeProduct $product, KnowledgeAreaSuggester $areas): array
    {
        return [
            ...$product->identity(),
            'has_service_knowledge' => $product->hasServiceKnowledge(),
            'knowledge_slugs' => $areas->slugsFor($product->identity()),
        ];
    }

    /**
     * ProductForge found nothing: the items of EOCS with that name or number are offered instead, marked
     * as "only in EOCS". Without a match the answer says so and nothing is guessed.
     */
    private function fromEocs(string $term, EocsClient $eocs, KnowledgeAreaSuggester $areas): JsonResponse
    {
        try {
            $items = $eocs->searchItems($term);
        } catch (EocsException $exception) {
            return response()->json(['enabled' => true, 'hits' => [], 'total' => 0, 'page' => 1, 'last_page' => 1, 'error' => 'In ProductForge wurde nichts gefunden, und EOCS ist nicht erreichbar.']);
        }

        return response()->json([
            'enabled' => true,
            'hits' => array_map(fn (EocsItem $item): array => [...$item->identity(), 'origin' => 'eocs', 'key' => 'eocs:'.$item->itemId, 'knowledge_slugs' => $areas->slugsFor($item->identity())], $items),
            'total' => count($items),
            'page' => 1,
            'last_page' => 1,
            'from_eocs' => true,
            'error' => null,
        ]);
    }
}

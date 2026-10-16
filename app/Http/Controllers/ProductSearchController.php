<?php

namespace App\Http\Controllers;

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
    public function __invoke(ProductSearchRequest $request, ProductForgeClient $client, KnowledgeAreaSuggester $areas): JsonResponse
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

        return response()->json([
            'enabled' => true,
            'hits' => array_map(fn (ForgeSearchHit $hit): array => [...$hit->toArray(), 'knowledge_slugs' => $areas->slugsFor($hit->toArray())], $page->hits),
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
}

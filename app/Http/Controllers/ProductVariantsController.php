<?php

namespace App\Http\Controllers;

use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsProblem;
use App\Eocs\EocsVariantList;
use App\Http\Requests\ProductVariantsRequest;
use App\ProductForge\ProductPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * The other variants of a chosen product for the proposal form (PROJ-54). The browser never talks to EOCS:
 * the app asks with its own token and passes on name, SKU and status only. A short cache spares EOCS when a
 * product is chosen and removed again; the proposal itself always asks fresh.
 */
class ProductVariantsController extends Controller
{
    public function __invoke(ProductVariantsRequest $request, EocsClient $eocs): JsonResponse
    {
        $sku = (string) $request->validated('sku');

        if (preg_match('/^\d{1,12}$/', $sku) !== 1) {
            return $this->answer(false, [], 0, ProductPackage::variantProblemMessage('no_sku'));
        }

        $key = "product-variants:{$sku}";
        $found = Cache::get($key);

        if ($found === null) {
            try {
                $lists = $eocs->variantsOf($sku);
            } catch (EocsException $exception) {
                return $this->answer(false, [], 0, ProductPackage::variantProblemMessage($exception->problem === EocsProblem::Misconfigured ? 'misconfigured' : 'unavailable'));
            }

            $found = ['known' => $lists !== null, 'lists' => array_map(fn (EocsVariantList $list): array => $list->toSnapshot(), $lists ?? [])];

            // An unknown SKU is not remembered: it may be created in EOCS a moment later.
            if ($found['known']) {
                Cache::put($key, $found, now()->addMinutes((int) config('analysis.product_info.variants_cache_minutes')));
            }
        }

        if (! $found['known']) {
            return $this->answer(false, [], 0, ProductPackage::variantProblemMessage('not_found'));
        }

        $variants = [];
        $omitted = 0;

        foreach ($found['lists'] as $snapshot) {
            $list = EocsVariantList::fromSnapshot($snapshot);

            foreach ($list?->variants ?? [] as $variant) {
                if ((string) $variant['sku'] !== $sku) {
                    $variants[$variant['sku']] = $variant;
                }
            }

            $omitted += $list?->omitted ?? 0;
        }

        return $this->answer(true, array_values($variants), $omitted, null);
    }

    /**
     * @param  list<array{sku: int, name: string, active: bool}>  $variants
     */
    private function answer(bool $checked, array $variants, int $omitted, ?string $error): JsonResponse
    {
        return response()->json(['checked' => $checked, 'variants' => $variants, 'omitted' => $omitted, 'error' => $error]);
    }
}

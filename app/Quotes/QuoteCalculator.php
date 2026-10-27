<?php

namespace App\Quotes;

use App\Models\QuoteArticle;

/**
 * The arithmetic of the offer calculator (PROJ-34). The page repeats these formulas in the browser for the
 * live display; what is saved is always calculated here.
 */
final class QuoteCalculator
{
    /**
     * Internal cost of one piece: purchase, labour, machine, packaging, shipping, other.
     */
    public static function unitCost(QuoteArticle $article): float
    {
        return (float) $article->purchase_cost
            + $article->labor_minutes / 60 * $article->hour_rate
            + (float) $article->machine_minutes / 60 * $article->machine_rate
            + $article->pack_cost
            + $article->ship_cost
            + $article->other_cost;
    }

    /**
     * Shop price check: net price, cost, profit, margin, break-even and the lowest price for the target margin.
     *
     * @return array{net: float, cost: float, profit: float, margin: float, break_even_gross: float, target_gross: float}
     */
    public static function shopPrice(QuoteArticle $article): array
    {
        $vat = $article->vat_rate / 100;
        $net = $article->shop_price_gross / (1 + $vat);
        $cost = self::unitCost($article);
        $profit = $net - $cost;
        $targetShare = 1 - $article->target_margin / 100;

        return [
            'net' => $net,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $net > 0 ? $profit / $net * 100 : 0.0,
            'break_even_gross' => $cost * (1 + $vat),
            'target_gross' => $targetShare > 0 ? $cost / $targetShare * (1 + $vat) : 0.0,
        ];
    }

    /**
     * An order of one article with a discount on the shop price.
     *
     * @return array{customer_gross: float, customer_net: float, revenue: float, costs: float, profit: float, margin: float, max_discount_target: float, max_discount_break_even: float}
     */
    public static function discount(QuoteArticle $article, int $quantity, float $discountPct, float $extraCosts = 0.0): array
    {
        $vat = $article->vat_rate / 100;
        $customerGross = $article->shop_price_gross * (1 - $discountPct / 100);
        $customerNet = $customerGross / (1 + $vat);
        $unitCost = self::unitCost($article);
        $revenue = $customerNet * $quantity;
        $costs = $unitCost * $quantity + $extraCosts;
        $profit = $revenue - $costs;
        $targetShare = 1 - $article->target_margin / 100;
        $targetGross = $targetShare > 0 ? $unitCost / $targetShare * (1 + $vat) : 0.0;
        $base = $article->shop_price_gross;

        return [
            'customer_gross' => $customerGross,
            'customer_net' => $customerNet,
            'revenue' => $revenue,
            'costs' => $costs,
            'profit' => $profit,
            'margin' => $revenue > 0 ? $profit / $revenue * 100 : 0.0,
            'max_discount_target' => $base > 0 ? max(0.0, (1 - $targetGross / $base) * 100) : 0.0,
            'max_discount_break_even' => $base > 0 ? max(0.0, (1 - $unitCost * (1 + $vat) / $base) * 100) : 0.0,
        ];
    }

    /**
     * Totals of an offer from its items (quantity, unit_gross, vat in percent).
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{net: float, vat: float, gross: float}
     */
    public static function offerTotals(array $items): array
    {
        $gross = 0.0;
        $net = 0.0;

        foreach ($items as $item) {
            $line = round((float) ($item['unit_gross'] ?? 0), 2) * (int) ($item['qty'] ?? 0);
            $vat = (float) ($item['vat'] ?? 19) / 100;
            $gross += $line;
            $net += $line / (1 + $vat);
        }

        return ['net' => round($net, 2), 'vat' => round($gross - $net, 2), 'gross' => round($gross, 2)];
    }

    /**
     * Purchase cost of an article from its base articles (quantity × price each).
     *
     * @param  list<array<string, mixed>>  $rawArticles
     */
    public static function purchaseCost(array $rawArticles): float
    {
        return round(array_sum(array_map(fn (array $row): float => (float) ($row['qty'] ?? 0) * (float) ($row['unit_cost'] ?? 0), $rawArticles)), 2);
    }
}

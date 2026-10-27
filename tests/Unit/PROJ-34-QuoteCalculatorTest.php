<?php

use App\Models\QuoteArticle;
use App\Quotes\QuoteCalculator;

function costedArticle(): QuoteArticle
{
    // 3D Glasfoto im Herz (M): shop price 79 €, purchase 6.01 €, machine 5 minutes, labour 3 minutes at 60 €/h.
    return new QuoteArticle([
        'shop_price_gross' => 79.0, 'purchase_cost' => 6.01, 'machine_minutes' => 5, 'labor_minutes' => 3, 'hour_rate' => 60,
        'machine_rate' => 60, 'pack_cost' => 0, 'ship_cost' => 0, 'other_cost' => 0, 'target_margin' => 30, 'vat_rate' => 19,
    ]);
}

test('the unit cost adds purchase, labour and machine time', function () {
    expect(QuoteCalculator::unitCost(costedArticle()))->toEqualWithDelta(6.01 + 3.0 + 5.0, 0.0001);
});

test('the shop price check gives net, profit, margin and the lowest price for the target margin', function () {
    $check = QuoteCalculator::shopPrice(costedArticle());

    expect($check['net'])->toEqualWithDelta(79 / 1.19, 0.0001)
        ->and($check['cost'])->toEqualWithDelta(14.01, 0.0001)
        ->and($check['profit'])->toEqualWithDelta(79 / 1.19 - 14.01, 0.0001)
        ->and($check['break_even_gross'])->toEqualWithDelta(14.01 * 1.19, 0.0001)
        ->and($check['target_gross'])->toEqualWithDelta(14.01 / 0.7 * 1.19, 0.0001);
});

test('a discount order gives revenue, costs, profit and the discount limits', function () {
    $order = QuoteCalculator::discount(costedArticle(), 30, 10, 20.0);

    expect($order['customer_gross'])->toEqualWithDelta(71.1, 0.0001)
        ->and($order['revenue'])->toEqualWithDelta(71.1 / 1.19 * 30, 0.0001)
        ->and($order['costs'])->toEqualWithDelta(14.01 * 30 + 20, 0.0001)
        ->and($order['profit'])->toEqualWithDelta($order['revenue'] - $order['costs'], 0.0001)
        ->and($order['max_discount_target'])->toEqualWithDelta((1 - 14.01 / 0.7 * 1.19 / 79) * 100, 0.0001)
        ->and($order['max_discount_break_even'])->toBeGreaterThan($order['max_discount_target']);
});

test('offer totals are calculated from the gross prices', function () {
    $totals = QuoteCalculator::offerTotals([
        ['qty' => 10, 'unit_gross' => 71.10, 'vat' => 19],
        ['qty' => 2, 'unit_gross' => 9.90, 'vat' => 7],
    ]);

    expect($totals['gross'])->toBe(730.8)
        ->and($totals['net'])->toBe(round(711 / 1.19 + 19.8 / 1.07, 2))
        ->and($totals['vat'])->toBe(round(730.8 - ($totals['net']), 2));
});

test('the purchase cost is quantity times price of every base article', function () {
    expect(QuoteCalculator::purchaseCost([['id' => 'a', 'qty' => 2, 'unit_cost' => 1.45], ['id' => 'b', 'qty' => 1, 'unit_cost' => 0.18]]))->toBe(3.08);
});

test('costs are complete only with purchase cost and machine time', function () {
    expect(costedArticle()->hasCompleteCosts())->toBeTrue()
        ->and(new QuoteArticle(['purchase_cost' => 1.0]))->hasCompleteCosts()->toBeFalse();
});

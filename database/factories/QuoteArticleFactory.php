<?php

namespace Database\Factories;

use App\Models\QuoteArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteArticle>
 */
class QuoteArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => (string) fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->words(3, true),
            'category' => 'Thermosublimation: Lebensmittelbehälter',
            'shop_price_gross' => 79.0,
            'active' => true,
            'raw_articles' => null,
            'purchase_cost' => null,
            'machine_minutes' => null,
        ];
    }

    /**
     * Costs entered: purchase 6.01, machine 5 minutes.
     */
    public function withCosts(float $purchase = 6.01, float $machineMinutes = 5): static
    {
        return $this->state([
            'raw_articles' => [['id' => '10485', 'qty' => 1, 'unit_cost' => $purchase]],
            'purchase_cost' => $purchase,
            'machine_minutes' => $machineMinutes,
        ]);
    }
}

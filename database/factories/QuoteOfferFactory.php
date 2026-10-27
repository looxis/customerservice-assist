<?php

namespace Database\Factories;

use App\Models\QuoteOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteOffer>
 */
class QuoteOfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'AN-'.now()->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'created_on' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'company' => fake()->company(),
            'contact' => fake()->name(),
            'email' => fake()->safeEmail(),
            'items' => [['sku' => '11244', 'name' => '3D Glasfoto im Herz (M)', 'qty' => 10, 'discount_pct' => 10.0, 'unit_gross' => 71.1, 'vat' => 19.0]],
            'created_by' => 'Nele',
        ];
    }
}

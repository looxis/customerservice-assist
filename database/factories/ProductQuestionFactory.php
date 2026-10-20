<?php

namespace Database\Factories;

use App\Models\ProductQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductQuestion>
 */
class ProductQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => (string) fake()->unique()->numberBetween(2100000, 2199999),
            'product_key' => 'pf:2001',
            'product_name' => 'Demo Tortenaufleger',
            'topic' => 'vegan',
            'question' => 'Kundin fragt, ob der Aufleger vegan ist.',
            'customer_group' => 'private-looxis-de',
            'test_run' => false,
        ];
    }
}

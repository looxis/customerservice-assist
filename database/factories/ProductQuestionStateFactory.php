<?php

namespace Database\Factories;

use App\Models\ProductQuestionState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductQuestionState>
 */
class ProductQuestionStateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_key' => 'pf:2001',
            'topic' => 'vegan',
            'status' => 'done',
            'changed_by' => 'Etienne',
            'changed_at' => now(),
        ];
    }
}

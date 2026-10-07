<?php

namespace Database\Factories;

use App\Models\KnowledgeGap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeGap>
 */
class KnowledgeGapFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => (string) fake()->numberBetween(2100000, 2199999),
            'staff_name' => 'Nele',
            'customer_group' => 'unclear',
            'products' => [],
            'content' => ['missing' => 'Regel zu Teillieferungen', 'solution' => null, 'comment' => null],
            'status' => 'open',
        ];
    }
}

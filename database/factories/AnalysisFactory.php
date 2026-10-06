<?php

namespace Database\Factories;

use App\Models\Analysis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'ticket_number' => (string) fake()->numberBetween(2100000, 2199999),
            'scope_key' => fn (array $attributes): string => $attributes['ticket_number'],
            'status' => 'completed',
            'staff_name' => fake()->randomElement(['Cara', 'Etienne', 'Nele']),
            'customer_group' => 'unclear',
            'products' => [],
            'variant' => 'verlauf',
            'category' => 'complaint',
            'confidence' => 'MITTEL',
            'actions' => [],
            'knowledge_ids' => [],
            'knowledge_fingerprints' => [],
            'model' => 'gpt-5.5',
            'prompt_version' => 'analysis-test',
            'content' => ['ticket' => '', 'result' => []],
        ];
    }
}

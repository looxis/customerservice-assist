<?php

namespace Database\Factories;

use App\Models\TicketSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSummary>
 */
class TicketSummaryFactory extends Factory
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
            'scope_key' => fn (array $attributes): string => $attributes['ticket_number'],
            'content' => [],
        ];
    }
}

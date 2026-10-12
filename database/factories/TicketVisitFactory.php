<?php

namespace Database\Factories;

use App\Models\TicketVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketVisit>
 */
class TicketVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $opened = now()->subMinutes(fake()->numberBetween(1, 5000));

        return [
            'ticket_number' => (string) fake()->unique()->numberBetween(2100000, 2199999),
            'customer_name' => fake()->name(),
            'title' => fake()->sentence(4),
            'first_opened_at' => $opened,
            'first_opened_by' => 'Nele',
            'last_opened_at' => $opened,
            'last_opened_by' => 'Nele',
            'opened_count' => 1,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\TicketCaseChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketCaseChoice>
 */
class TicketCaseChoiceFactory extends Factory
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
            'customer_group' => 'unclear',
            'products' => [],
            'staff_name' => 'Nele',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\CustomerGroupMemory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerGroupMemory>
 */
class CustomerGroupMemoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_key' => 'customer-'.fake()->numberBetween(1, 99999),
            'customer_group' => 'reseller',
        ];
    }
}

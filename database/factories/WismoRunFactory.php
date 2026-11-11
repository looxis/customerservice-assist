<?php

namespace Database\Factories;

use App\Models\WismoRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WismoRun>
 */
class WismoRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zammad_id' => fake()->unique()->numberBetween(1000, 999999),
            'ticket_number' => (string) fake()->unique()->numberBetween(2100000, 2199999),
            'mode' => 'on',
            'result' => WismoRun::SENT,
            'order_number' => '028-1058128-5149960',
            'carrier' => 'DHL',
            'tracking_number' => '00340434171079146248',
            'processed_at' => now(),
        ];
    }
}

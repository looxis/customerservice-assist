<?php

namespace Database\Factories;

use App\Models\ClaimReplyRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaimReplyRun>
 */
class ClaimReplyRunFactory extends Factory
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
            'result' => ClaimReplyRun::SENT,
            'order_number' => '028-1058128-5149960',
            'days_since_shipped' => 5,
            'photo_count' => 0,
            'scenario' => ClaimReplyRun::NO_PHOTO,
            'processed_at' => now(),
        ];
    }
}

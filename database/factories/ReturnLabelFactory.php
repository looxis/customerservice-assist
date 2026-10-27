<?php

namespace Database\Factories;

use App\Models\ReturnLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnLabel>
 */
class ReturnLabelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => (string) fake()->numberBetween(2100000, 2149999),
            'order_number' => '402-'.fake()->numerify('#######').'-'.fake()->numerify('#######'),
            'eocs_order_id' => fake()->numberBetween(1000, 999999),
            'shipment_no' => fake()->numerify('############'),
            'status' => ReturnLabel::CREATED,
            'fingerprint' => hash('sha256', fake()->uuid()),
            'created_by' => 'Nele',
            'file_path' => null,
            'file_sha256' => null,
        ];
    }
}

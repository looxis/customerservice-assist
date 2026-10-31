<?php

namespace Database\Factories;

use App\Analysis\ProcessingState;
use App\Models\TrackedTicket;
use App\Zammad\SyncStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedTicket>
 */
class TrackedTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $updated = now()->subMinutes(fake()->numberBetween(1, 5000));

        return [
            'zammad_id' => fake()->unique()->numberBetween(10000, 99999),
            'number' => (string) fake()->unique()->numberBetween(2100000, 2199999),
            'title' => fake()->sentence(4),
            'state' => 'open',
            'is_closed' => false,
            'group_name' => 'allg. Kunden',
            'owner_zammad_id' => null,
            'owner_name' => null,
            'zammad_created_at' => $updated,
            'zammad_updated_at' => $updated,
            'last_activity_at' => $updated,
            'sync_status' => SyncStatus::Synced,
            'synced_at' => $updated,
            'processing_state' => ProcessingState::NotAnalyzed,
        ];
    }

    public function closed(): static
    {
        return $this->state(['state' => 'closed', 'is_closed' => true]);
    }

    public function ownedBy(int $zammadId, string $name = 'Jemand Anderes'): static
    {
        return $this->state(['owner_zammad_id' => $zammadId, 'owner_name' => $name]);
    }
}

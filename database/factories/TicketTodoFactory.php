<?php

namespace Database\Factories;

use App\Models\TicketTodo;
use App\Todo\TodoKind;
use App\Todo\TodoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketTodo>
 */
class TicketTodoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zammad_id' => fake()->numberBetween(10000, 99999),
            'ticket_number' => (string) fake()->numberBetween(2100000, 2199999),
            'description' => fake()->sentence(6),
            'assignee' => 'Nele',
            'due_on' => null,
            'kind' => TodoKind::Direct,
            'status' => TodoStatus::Open,
            'source' => 'manual',
            'created_by' => 'Nele',
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => ['status' => TodoStatus::Done, 'completed_by' => 'Nele', 'completed_at' => now()]);
    }

    public function waiting(string $for = 'Versand der Ersatzbestellung'): static
    {
        return $this->state(fn (): array => ['kind' => TodoKind::Waiting, 'waiting_for' => $for]);
    }
}

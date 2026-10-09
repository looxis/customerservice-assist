<?php

namespace Database\Factories;

use App\Models\MessageTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTranslation>
 */
class MessageTranslationFactory extends Factory
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
            'article_id' => fake()->unique()->numberBetween(1, 999999),
            'fingerprint' => hash('sha256', 'text'),
            'status' => 'translated',
            'language' => 'Italienisch',
            'content' => ['text' => 'Guten Tag'],
            'staff_name' => 'Nele',
            'model' => 'gpt-5.4-mini',
            'prompt_version' => 'translation-test',
        ];
    }
}

<?php

namespace App\Analysis\Agents;

use App\Analysis\Summary;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Condenses the thread before the last customer message (PROJ-9, stage 1).
 */
class SummaryAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private readonly string $prompt) {}

    public function instructions(): Stringable|string
    {
        return $this->prompt;
    }

    public function schema(JsonSchema $schema): array
    {
        $fields = [];

        foreach (Summary::SECTIONS as $key => $label) {
            $fields[$key] = $schema->string()->description($label)->required();
        }

        return $fields;
    }
}

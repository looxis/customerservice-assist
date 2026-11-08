<?php

namespace App\Labeling;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Proposes one category for the first customer message of a ticket (PROJ-78).
 */
class LabelAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private readonly string $prompt) {}

    public function instructions(): Stringable|string
    {
        return $this->prompt;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->enum(array_column(LabelCategory::cases(), 'value'))->required(),
            'unclear' => $schema->boolean()->required(),
            'multiple_concerns' => $schema->boolean()->required(),
            'confidence' => $schema->number()->description('0 bis 1')->required(),
            'reason' => $schema->string()->description('Ein Satz auf Deutsch')->required(),
            'evidence' => $schema->string()->description('Wörtliche Stelle aus der Nachricht, leer, wenn keine passt')->required(),
        ];
    }
}

<?php

namespace App\Analysis\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Translates messages into German, each returned under its own ID (PROJ-28).
 */
class TranslationAgent implements Agent, HasStructuredOutput
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
            'messages' => $schema->array()->items($schema->object([
                'id' => $schema->string()->description('ID der Nachricht wie in der Überschrift')->required(),
                'language' => $schema->string()->description('Sprache des Originals auf Deutsch, z. B. Italienisch')->required(),
                'is_german' => $schema->boolean()->description('true, wenn das Original bereits deutsch ist')->required(),
                'translation' => $schema->string()->description('Deutsche Übersetzung; leer, wenn das Original deutsch ist')->required(),
            ])->withoutAdditionalProperties())->required(),
        ];
    }
}

<?php

namespace App\Analysis\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Translates a prepared ToDo reply and its signature into the language of the customer's last message (PROJ-95).
 */
class ReplyTranslationAgent implements Agent, HasStructuredOutput
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
            'language' => $schema->string()->description('Sprache der Kundennachricht auf Deutsch, z. B. Italienisch')->required(),
            'is_german' => $schema->boolean()->description('true, wenn die Kundennachricht deutsch oder ihre Sprache nicht erkennbar ist')->required(),
            'reply' => $schema->string()->description('Übersetzte Antwort; leer, wenn is_german true ist')->required(),
            'signature' => $schema->string()->description('Übersetzte Signatur; leer, wenn is_german true ist')->required(),
        ];
    }
}

<?php

namespace App\Wismo;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Writes the reply to "Where is my order?" from the facts of the order and the shipment (PROJ-82).
 */
class WismoAgent implements Agent, HasStructuredOutput
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
            'reply' => $schema->string()->description('Antworttext an den Kunden in dessen Sprache, ohne Grußformel und Signatur')->required(),
            'reply_de' => $schema->string()->description('Dieselbe Antwort auf Deutsch für interne Mitarbeiter')->required(),
        ];
    }
}

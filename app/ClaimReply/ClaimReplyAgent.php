<?php

namespace App\ClaimReply;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Writes the automatic first reply to a complaint: asks for photos or a description, or confirms receipt (PROJ-85).
 */
class ClaimReplyAgent implements Agent, HasStructuredOutput
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
            'scenario' => $schema->string()->enum(['no_photo', 'photo_no_description', 'photo_with_description'])->description('Welcher Fall vorliegt')->required(),
            'reply' => $schema->string()->description('Antworttext an den Kunden in dessen Sprache, ohne Grußformel und Signatur')->required(),
            'reply_de' => $schema->string()->description('Dieselbe Antwort auf Deutsch für interne Mitarbeiter')->required(),
        ];
    }
}

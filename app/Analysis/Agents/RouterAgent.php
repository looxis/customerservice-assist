<?php

namespace App\Analysis\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Picks additional knowledge documents from a short catalogue for a customer request (PROJ-37).
 * It only returns IDs, a rank, a reason and case types; the app checks every ID.
 */
class RouterAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  list<string>  $categories
     */
    public function __construct(
        private readonly string $prompt,
        private readonly array $categories,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->prompt;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'additions' => $schema->array()->items($schema->object([
                'id' => $schema->string()->description('ID eines Dokuments aus der Liste')->required(),
                'fit' => $schema->string()->enum(['clear', 'possible'])->description('clear = die Anfrage beschreibt selbst, was das Dokument behandelt; possible = nur ähnliches Thema oder Vermutung')->required(),
                'rank' => $schema->integer()->description('1 = wichtigste Ergänzung')->required(),
                'basis' => $schema->string()->enum(['current', 'earlier'])->description('current = aktuelle Nachricht (auch bei Rückbezug), earlier = nur früherer Verlauf')->required(),
                'reason' => $schema->string()->description('Ein Satz auf Deutsch als Einschätzung')->required(),
                'evidence' => $schema->string()->description('Wörtlicher Satz oder Wendung aus der Kundenanfrage; Pflicht bei Dokumenten mit Vermerk Unterdrückt, sonst leer')->required(),
            ])->withoutAdditionalProperties())->required(),
            'categories' => $schema->array()->items($schema->object([
                'key' => $schema->string()->enum($this->categories)->required(),
                'reason' => $schema->string()->description('Kurze Begründung auf Deutsch')->required(),
            ])->withoutAdditionalProperties())->required(),
        ];
    }
}

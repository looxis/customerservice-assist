<?php

namespace App\Analysis\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Assesses a case and drafts the reply in a fixed structure (PROJ-9, stage 2).
 * The value lists come from the configuration and the selected knowledge.
 */
class CaseAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  list<string>  $categories
     * @param  list<string>  $actions
     * @param  list<string>  $knowledgeIds
     */
    public function __construct(
        private readonly string $prompt,
        private readonly array $categories,
        private readonly array $actions,
        private readonly array $knowledgeIds,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->prompt;
    }

    public function schema(JsonSchema $schema): array
    {
        $knowledgeId = $schema->string();

        if ($this->knowledgeIds !== []) {
            $knowledgeId = $knowledgeId->enum($this->knowledgeIds);
        }

        return [
            'summary' => $schema->object([
                'incident' => $schema->string()->description('Was ist passiert?')->required(),
                'customer_wish' => $schema->string()->description('Was möchte der Kunde?')->required(),
            ])->withoutAdditionalProperties()->required(),
            'category' => $schema->string()->enum($this->categories)->required(),
            'case_pattern' => $schema->string()->description('Fallmuster, soweit bestimmbar')->nullable()->required(),
            'assessment' => $schema->string()->enum(['berechtigt', 'unberechtigt', 'unklar'])->description('Nur bei Reklamationen')->nullable()->required(),
            'next_step' => $schema->string()->enum(['reply', 'internal_first', 'info_only'])->description('reply: ein Antwortentwurf ist sinnvoll. internal_first: erst eine interne Klärung, noch kein Antwortentwurf. info_only: nur Auskunft für den Mitarbeiter (nur wenn kein Antwortentwurf gewünscht ist).')->required(),
            'recommendation' => $schema->string()->description('Empfohlene Maßnahme in höchstens zwei Sätzen, ohne Schrittfolgen')->required(),
            'actions' => $schema->array()->items($schema->string()->enum($this->actions))->required(),
            'authority' => $schema->object([
                'agent_may_decide' => $schema->boolean()->required(),
                'approval_by' => $schema->string()->nullable()->required(),
                'permission_id' => $schema->string()->nullable()->required(),
            ])->withoutAdditionalProperties()->required(),
            'reasoning' => $schema->string()->required(),
            'knowledge_gaps' => $schema->array()->items($schema->object([
                'topic' => $schema->string()->description('Thema der fehlenden Regel')->required(),
                'question' => $schema->string()->description('Frage, die das Unternehmenswissen beantworten müsste')->required(),
            ])->withoutAdditionalProperties())->required(),
            'knowledge_ids' => $schema->array()->items($knowledgeId)->required(),
            'confidence' => $schema->object([
                'level' => $schema->string()->enum(['HOCH', 'MITTEL', 'NIEDRIG'])->required(),
                'reasons' => $schema->array()->items($schema->string())->required(),
            ])->withoutAdditionalProperties()->required(),
            'internal_steps' => $schema->array()->items($schema->string())->description('Konkrete interne Arbeitsschritte für diesen Fall')->required(),
            'decision' => $schema->object([
                'question' => $schema->string()->description('Die interne Entscheidung oder Auskunft, die zuerst nötig ist')->required(),
                'why' => $schema->string()->description('Warum sie die Kundenantwort wesentlich ändert')->required(),
                'options' => $schema->array()->items($schema->object([
                    'key' => $schema->string()->description('Kurzer eindeutiger Schlüssel in Kleinbuchstaben')->required(),
                    'label' => $schema->string()->required(),
                    'consequence' => $schema->string()->description('Was daraus für die Antwort folgt und worauf es beruht')->required(),
                ])->withoutAdditionalProperties())->required(),
                'knowledge_ids' => $schema->array()->items($knowledgeId)->description('Nur wenn eine Wissensregel die Entscheidung trägt')->required(),
            ])->withoutAdditionalProperties()->nullable()->required(),
            'reply' => $schema->object([
                'language' => $schema->string()->description('Sprache des Antwortentwurfs, z. B. Deutsch')->required(),
                'text' => $schema->string()->required(),
            ])->withoutAdditionalProperties()->nullable()->required(),
        ];
    }
}

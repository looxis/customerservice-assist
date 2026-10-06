<?php

namespace App\Analysis;

/**
 * Checks a structured answer against the value lists. Unknown knowledge IDs,
 * actions or a category outside the list are dropped and reported, never
 * passed on silently.
 */
class ResultValidator
{
    private const array REQUIRED = ['summary', 'category', 'recommendation', 'actions', 'authority', 'reasoning', 'missing_information', 'knowledge_ids', 'confidence', 'internal_todos', 'reply'];

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $knowledgeIds  IDs of the knowledge that was sent.
     * @return array{result: array<string, mixed>, notes: list<string>}
     *
     * @throws AnalysisException when the structure is unusable.
     */
    public function check(array $data, array $knowledgeIds): array
    {
        foreach (self::REQUIRED as $field) {
            if (! array_key_exists($field, $data)) {
                throw new AnalysisException(AnalysisProblem::InvalidResult, "missing {$field}");
            }
        }

        if (! is_string($data['reply']['text'] ?? null) || trim($data['reply']['text']) === '') {
            throw new AnalysisException(AnalysisProblem::InvalidResult, 'empty reply');
        }

        $notes = [];

        $data['knowledge_gaps'] = array_values(array_filter(
            is_array($data['knowledge_gaps'] ?? null) ? $data['knowledge_gaps'] : [],
            fn (mixed $gap): bool => is_array($gap) && is_string($gap['topic'] ?? null) && trim($gap['topic']) !== '',
        ));

        if (! in_array($data['category'], config('knowledge.categories'), true)) {
            $notes[] = "Unbekannte Kategorie „{$data['category']}“ verworfen.";
            $data['category'] = null;
        }

        $actions = array_values(array_filter((array) $data['actions'], 'is_string'));
        $unknownActions = array_diff($actions, array_keys(config('knowledge.actions')));

        foreach ($unknownActions as $action) {
            $notes[] = "Unbekannter Vorgang „{$action}“ verworfen.";
        }

        $data['actions'] = array_values(array_unique(array_diff($actions, $unknownActions)));

        $ids = array_values(array_filter((array) $data['knowledge_ids'], 'is_string'));

        foreach (array_diff($ids, $knowledgeIds) as $id) {
            $notes[] = "Unbekannte Quelle „{$id}“ entfernt.";
        }

        $data['knowledge_ids'] = array_values(array_unique(array_intersect($ids, $knowledgeIds)));

        if (! in_array($data['assessment'] ?? null, ['berechtigt', 'unberechtigt', 'unklar', null], true)) {
            $data['assessment'] = null;
        }

        if (! in_array($data['confidence']['level'] ?? null, ['HOCH', 'MITTEL', 'NIEDRIG'], true)) {
            $data['confidence']['level'] = 'NIEDRIG';
            $notes[] = 'Confidence fehlte und wurde auf NIEDRIG gesetzt.';
        }

        return ['result' => $data, 'notes' => $notes];
    }
}

<?php

namespace App\Analysis;

/**
 * Checks a structured answer against the value lists. Unknown knowledge IDs,
 * actions or a category outside the list are dropped and reported, never
 * passed on silently.
 */
class ResultValidator
{
    private const array REQUIRED = ['summary', 'category', 'recommendation', 'actions', 'authority', 'reasoning', 'knowledge_ids', 'confidence', 'internal_steps', 'reply'];

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $knowledgeIds  IDs of the knowledge that was sent.
     * @param  bool  $replyWanted  False only for a question without ticket where no reply draft was asked for (PROJ-40).
     * @return array{result: array<string, mixed>, notes: list<string>}
     *
     * @throws AnalysisException when the structure is unusable.
     */
    public function check(array $data, array $knowledgeIds, bool $replyWanted = true): array
    {
        foreach (self::REQUIRED as $field) {
            if (! array_key_exists($field, $data)) {
                throw new AnalysisException(AnalysisProblem::InvalidResult, "missing {$field}");
            }
        }

        $notes = [];
        $data['next_step'] = in_array($data['next_step'] ?? null, ['internal_first', 'info_only'], true) ? $data['next_step'] : 'reply';
        $data['internal_steps'] = array_values(array_filter(array_map(fn (mixed $step): string => is_string($step) ? trim($step) : '', (array) $data['internal_steps']), fn (string $step): bool => $step !== ''));
        $data['decision'] = $this->decision($data['decision'] ?? null, $knowledgeIds, $notes, $data['internal_steps']);

        if ($replyWanted && $data['next_step'] === 'info_only') {
            $data['next_step'] = 'reply';
        }

        if ($data['next_step'] === 'internal_first') {
            if ($data['decision'] === null && $data['internal_steps'] === []) {
                throw new AnalysisException(AnalysisProblem::InvalidResult, 'internal_first without decision or steps');
            }

            $data['reply'] = null;
        } elseif (! $replyWanted) {
            if (is_array($data['reply'] ?? null) && trim((string) ($data['reply']['text'] ?? '')) !== '') {
                $notes[] = 'Es war kein Antwortentwurf gewünscht; der Entwurf der KI wurde nicht übernommen.';
            }

            $data['next_step'] = 'info_only';
            $data['reply'] = null;
        } elseif (! is_string($data['reply']['text'] ?? null) || trim($data['reply']['text']) === '') {
            throw new AnalysisException(AnalysisProblem::InvalidResult, 'empty reply');
        }

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

    /**
     * The choice offered to the employee: a formal check only. Unknown knowledge IDs are dropped
     * (none are needed when the case itself settles the question); with fewer than two usable
     * options the question becomes an internal step instead.
     *
     * @param  list<string>  $knowledgeIds
     * @param  list<string>  $notes
     * @param  list<string>  $steps
     * @return array{question: string, why: string, options: list<array{key: string, label: string, consequence: string}>, knowledge_ids: list<string>}|null
     */
    private function decision(mixed $decision, array $knowledgeIds, array &$notes, array &$steps): ?array
    {
        if (! is_array($decision)) {
            return null;
        }

        $question = is_string($decision['question'] ?? null) ? trim($decision['question']) : '';
        $options = [];

        foreach ((array) ($decision['options'] ?? []) as $option) {
            $key = is_array($option) && is_string($option['key'] ?? null) ? trim($option['key']) : '';
            $label = is_array($option) && is_string($option['label'] ?? null) ? trim($option['label']) : '';

            if ($key !== '' && $label !== '' && ! isset($options[$key]) && count($options) < 6) {
                $options[$key] = ['key' => $key, 'label' => $label, 'consequence' => is_string($option['consequence'] ?? null) ? trim($option['consequence']) : ''];
            }
        }

        if ($question === '' || count($options) < 2) {
            if ($question !== '') {
                $steps[] = $question;
                $notes[] = 'Die Entscheidung hatte keine zwei brauchbaren Optionen und wurde als interner Schritt übernommen.';
            }

            return null;
        }

        $ids = array_values(array_filter((array) ($decision['knowledge_ids'] ?? []), 'is_string'));

        foreach (array_diff($ids, $knowledgeIds) as $id) {
            $notes[] = "Unbekannte Quelle „{$id}“ bei der Entscheidung entfernt.";
        }

        return [
            'question' => $question,
            'why' => is_string($decision['why'] ?? null) ? trim($decision['why']) : '',
            'options' => array_values($options),
            'knowledge_ids' => array_values(array_unique(array_intersect($ids, $knowledgeIds))),
        ];
    }
}

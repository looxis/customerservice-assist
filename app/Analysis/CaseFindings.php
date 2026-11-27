<?php

namespace App\Analysis;

use App\Knowledge\CasePatternFinder;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeDocument;

/**
 * What the employee found out in the guided fact-finding (PROJ-103). Three kinds of information stay apart:
 *  - chosen case patterns: hypotheses, never a diagnosis,
 *  - answers to their check questions: observations of the employee, or values the app knows ("System"),
 *  - customer checks taken over: a decision of the employee about the next step.
 *
 * Built only from the knowledge and the validated request; option texts never come from the browser. A pattern that
 * does not fit the scope of the case is left out and named in `dropped`.
 */
final readonly class CaseFindings
{
    public const array ANSWERS = ['ja' => 'ja', 'nein' => 'nein', 'offen' => 'nicht beurteilbar'];

    /**
     * @param  list<array{document: KnowledgeDocument, answers: list<array{key: string, question: string, answer: string, source: string}>, open: list<string>, adopted: bool}>  $patterns
     * @param  list<string>  $dropped  Titles or IDs of chosen patterns that do not fit the case.
     * @param  list<string>  $droppedChecks  Names of taken-over customer checks that do not count (pattern left out or without a check).
     */
    public function __construct(
        public array $patterns,
        public bool $none,
        public string $by,
        public string $at,
        public array $dropped = [],
        public array $droppedChecks = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input  Validated request fields (fallmuster, pruefung, kundenpruefung, kein_fallmuster).
     * @param  list<string>  $products
     * @param  array<string, array{value: mixed, sure: bool, type: string, source: string}>  $known  From KnownCaseValues.
     */
    public static function fromInput(array $input, CasePatternFinder $finder, CustomerGroup $group, array $products, array $known, string $by): self
    {
        $chosen = array_values(array_unique(array_filter((array) ($input['fallmuster'] ?? []), 'is_string')));
        $answers = (array) ($input['pruefung'] ?? []);
        $adopted = array_values(array_unique(array_filter((array) ($input['kundenpruefung'] ?? []), 'is_string')));
        $patterns = [];
        $dropped = [];
        $droppedChecks = [];

        foreach ($chosen as $id) {
            $document = $finder->pattern($id);

            if ($document === null || $finder->exclusionReason($document, $group, $products, null) !== null) {
                $dropped[] = $document?->title ?? $id;

                continue;
            }

            $given = (array) ($answers[$id] ?? []);
            $lines = [];
            $open = [];

            foreach ($document->checks() as $check) {
                $value = $check['known_from'] === null ? null : ($known[$check['known_from']] ?? null);

                if (! $check['auto'] && is_array($value) && $value['sure'] && $value['type'] === 'bool') {
                    $lines[] = ['key' => $check['key'], 'question' => $check['question'], 'answer' => KnownCaseValues::display($value['value']), 'source' => 'System: '.$value['source']];

                    continue;
                }

                $answer = $given[$check['key']] ?? null;

                if ($check['auto']) {
                    $open[] = $check['question'].' (prüft die Analyse selbst)';
                } elseif (is_string($answer) && isset(self::ANSWERS[$answer]) && $answer !== 'offen') {
                    $lines[] = ['key' => $check['key'], 'question' => $check['question'], 'answer' => self::ANSWERS[$answer], 'source' => 'Beobachtung des Mitarbeiters'];
                } else {
                    $open[] = $check['question'].($answer === 'offen' ? ' (nicht beurteilbar)' : ' (nicht beantwortet)');
                }
            }

            $patterns[] = ['document' => $document, 'answers' => $lines, 'open' => $open, 'adopted' => in_array($id, $adopted, true) && $document->customerCheck() !== null];
        }

        foreach ($adopted as $id) {
            $kept = collect($patterns)->first(fn (array $pattern): bool => $pattern['document']->id === $id && $pattern['adopted']);

            if ($kept === null) {
                $droppedChecks[] = $id;
            }
        }

        return new self($patterns, $patterns === [] && filter_var($input['kein_fallmuster'] ?? false, FILTER_VALIDATE_BOOLEAN), $by, now()->toIso8601String(), $dropped, $droppedChecks);
    }

    public function isEmpty(): bool
    {
        return $this->patterns === [] && ! $this->none;
    }

    /**
     * @return list<string>
     */
    public function patternIds(): array
    {
        return array_map(fn (array $pattern): string => (string) $pattern['document']->id, $this->patterns);
    }

    /**
     * The documents with the instructions of the taken-over customer checks.
     *
     * @return list<string>
     */
    public function instructionIds(): array
    {
        return array_values(array_unique(array_map(fn (array $pattern): string => $pattern['document']->customerCheck()['knowledge'], array_filter($this->patterns, fn (array $pattern): bool => $pattern['adopted']))));
    }

    public function hasAdoptedCheck(): bool
    {
        return $this->instructionIds() !== [];
    }

    /**
     * The decisions of the employee, for the additional information: one sentence per taken-over check.
     */
    public function decisionText(): string
    {
        $lines = [];

        foreach ($this->patterns as $pattern) {
            if ($pattern['adopted']) {
                $check = $pattern['document']->customerCheck();
                $lines[] = "- Entscheidung des Mitarbeiters: Der Kunde soll die Prüfung „{$check['name']}“ durchführen. Die Anleitung steht in {$check['knowledge']}; gib sie dem Kunden verständlich weiter (Maßnahme customer-check).";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * The section for the language model: hypotheses and observations, clearly apart from the checked facts.
     */
    public function promptSection(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $lines = ['# Sachverhaltsermittlung des Mitarbeiters (Hypothesen und Beobachtungen, nicht geprüft)'];

        if ($this->none) {
            $lines[] = 'Der Mitarbeiter hat die bekannten Fallmuster angesehen: Keines passt. Das ist keine Wissenslücke.';

            return implode("\n", $lines);
        }

        foreach ($this->patterns as $pattern) {
            $document = $pattern['document'];
            $lines[] = "In Betracht gezogenes Fallmuster (Hypothese, nicht bestätigt): {$document->id} – {$document->title}";

            foreach ($pattern['answers'] as $answer) {
                $lines[] = "- {$answer['question']} → {$answer['answer']} ({$answer['source']})";
            }

            foreach ($pattern['open'] as $open) {
                $lines[] = "- Offen: {$open}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Observed facts that may find further knowledge by its triggers: the answered questions.
     */
    public function triggerText(): string
    {
        return implode("\n", array_merge(...array_map(fn (array $pattern): array => array_map(fn (array $answer): string => $answer['question'].' '.$answer['answer'], $pattern['answers']), $this->patterns) ?: [[]]));
    }

    /**
     * For the stored analysis and the result view.
     *
     * @return array{patterns: list<array{id: string, title: string, answers: list<array{question: string, answer: string, source: string}>, open: list<string>, adopted: array{name: string, knowledge: string}|null}>, none: bool, by: string, at: string}
     */
    public function toStored(): array
    {
        return [
            'patterns' => array_map(fn (array $pattern): array => [
                'id' => (string) $pattern['document']->id,
                'title' => (string) $pattern['document']->title,
                'answers' => array_map(fn (array $answer): array => ['question' => $answer['question'], 'answer' => $answer['answer'], 'source' => $answer['source']], $pattern['answers']),
                'open' => $pattern['open'],
                'adopted' => $pattern['adopted'] ? $pattern['document']->customerCheck() : null,
            ], $this->patterns),
            'none' => $this->none,
            'by' => $this->by,
            'at' => $this->at,
        ];
    }

    /**
     * The form fields to store with the inputs, so the form can be prefilled. Only the employee's own answers.
     *
     * @return array<string, mixed>
     */
    public function storedInput(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $answers = [];

        foreach ($this->patterns as $pattern) {
            foreach ($pattern['answers'] as $answer) {
                if ($answer['source'] === 'Beobachtung des Mitarbeiters') {
                    $answers[(string) $pattern['document']->id][$answer['key']] = array_search($answer['answer'], self::ANSWERS, true);
                }
            }

            foreach ($pattern['open'] as $open) {
                if (str_ends_with($open, '(nicht beurteilbar)')) {
                    $key = collect($pattern['document']->checks())->first(fn (array $check): bool => $open === $check['question'].' (nicht beurteilbar)')['key'] ?? null;

                    if ($key !== null) {
                        $answers[(string) $pattern['document']->id][$key] = 'offen';
                    }
                }
            }
        }

        return [
            'fallmuster' => $this->patternIds(),
            'pruefung' => $answers,
            'kundenpruefung' => array_values(array_map(fn (array $pattern): string => (string) $pattern['document']->id, array_filter($this->patterns, fn (array $pattern): bool => $pattern['adopted']))),
            'kein_fallmuster' => $this->none,
        ];
    }
}

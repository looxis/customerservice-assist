<?php

namespace App\Knowledge\Retrieval;

use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\Retrieval\Router\RouterAddition;

/**
 * Second stage of the knowledge selection (PROJ-36): from the documents that
 * apply to the case by customer group and product (stage one, PROJ-4), picks
 * those the concern needs. Core knowledge and binding knowledge come first and
 * are never cut; triggered knowledge fills the rest by strength; examples last.
 * Deterministic and without a language model.
 */
class KnowledgeRetriever
{
    private const array EXAMPLE_TYPES = ['example-good', 'example-bad'];

    public function __construct(
        private readonly KnowledgeSelector $selector,
        private readonly TriggerMatcher $matcher,
    ) {}

    /**
     * The retrieval rules and settings in force as a short identifier: the version of the rules and
     * a hash of the settings, stored with every analysis so a result can be traced back (PROJ-36).
     */
    public static function configurationVersion(): string
    {
        $settings = config('knowledge.retrieval');
        $keys = array_map(fn (array $definition): array => [$definition['level'], $definition['type']], $settings['when_keys']);

        return $settings['version'].'/'.substr(sha1((string) json_encode([
            $settings['budget'], $settings['min_trigger_length'], $settings['max_triggers'], $settings['required'], $keys,
            config('analysis.limits'), config('knowledge.selection.max_good_examples'), config('knowledge.selection.max_bad_examples'),
        ])), 0, 8);
    }

    /**
     * @param  list<string>  $manualIds  Documents the employee switched on by hand.
     * @param  int|null  $available  Characters that are left for knowledge in the whole prompt; null = no limit (preview).
     * @param  list<RouterAddition>  $additions  Documents the router adds (PROJ-37). They run through the same budget as strong or weak hits, behind the hits of their tier.
     *
     * @throws KnowledgeTooLarge when the mandatory knowledge alone does not fit into $available.
     */
    public function retrieve(CaseContext $context, CaseFacts $facts, array $manualIds = [], ?int $available = null, array $additions = []): RetrievalResult
    {
        $settings = config('knowledge.retrieval');
        $evaluator = new WhenEvaluator($settings['when_keys']);
        $target = (int) $settings['budget']['target'];
        $available ??= PHP_INT_MAX;

        $mandatory = [];
        $strong = [];
        $weak = [];
        $examples = [];
        $notRetrieved = [];
        $evaluations = [];
        $suppressed = [];
        $unclassified = [];
        $scope = $this->selector->scope($context)['selected'];

        foreach ($scope as $entry) {
            $document = $entry->document;
            $class = $document->retrieval();

            if ($class === null) {
                $unclassified[] = (string) $document->id;
            }

            $outcome = $this->decide($document, $class ?? 'core', $class === null, $facts, $evaluator, $evaluations, $suppressed);

            if (! $outcome instanceof RetrievedDocument) {
                $notRetrieved[(string) $document->id] = ['document' => $document, 'code' => $outcome['code'], 'detail' => $outcome['detail']];

                continue;
            }

            if (in_array($document->type, self::EXAMPLE_TYPES, true)) {
                $examples[] = $outcome;
            } elseif ($outcome->strength === RetrievedDocument::MANDATORY) {
                $mandatory[(string) $document->id] = $outcome;
            } elseif ($outcome->strength === RetrievedDocument::STRONG) {
                $strong[] = $outcome;
            } else {
                $weak[] = $outcome;
            }
        }

        foreach ($scope as $entry) {
            $id = (string) $entry->document->id;

            if (in_array($id, $manualIds, true) && ! isset($mandatory[$id])) {
                $mandatory[$id] = new RetrievedDocument($entry->document, $entry->document->retrieval() ?? 'core', RetrievedDocument::MANDATORY, 0, ['von Hand zugeschaltet'], origin: RetrievedDocument::ORIGIN_MANUAL);
                unset($notRetrieved[$id]);
                $strong = array_values(array_filter($strong, fn (RetrievedDocument $item): bool => $item->document->id !== $id));
                $weak = array_values(array_filter($weak, fn (RetrievedDocument $item): bool => $item->document->id !== $id));
                $examples = array_values(array_filter($examples, fn (RetrievedDocument $item): bool => $item->document->id !== $id));
            }
        }

        $routerStrong = [];
        $routerWeak = [];

        foreach ($additions as $addition) {
            $id = (string) $addition->document->id;

            if (isset($mandatory[$id]) || ! isset($notRetrieved[$id])) {
                continue;
            }

            unset($notRetrieved[$id]);
            $item = new RetrievedDocument($addition->document, $addition->document->retrieval() ?? 'triggered', $addition->strength === RouterAddition::STRONG ? RetrievedDocument::STRONG : RetrievedDocument::WEAK, 0, ['KI-Router: '.$addition->reason], origin: RetrievedDocument::ORIGIN_ROUTER);

            if ($addition->strength === RouterAddition::STRONG) {
                $routerStrong[] = $item;
            } else {
                $routerWeak[] = $item;
            }
        }

        $mandatoryCharacters = array_sum(array_map(fn (RetrievedDocument $item): int => $item->document->length(), $mandatory));

        if ($mandatoryCharacters > $available) {
            throw new KnowledgeTooLarge($mandatoryCharacters, $available);
        }

        $warnings = [];

        if ($mandatoryCharacters > $target) {
            $warnings[] = 'Das Pflichtwissen (Grundwissen und verbindliche Regeln) umfasst '.$this->number($mandatoryCharacters).' Zeichen und liegt über dem Zielumfang von '.$this->number($target).' Zeichen. Es wird nicht gekürzt.';
        }

        if ($unclassified !== []) {
            $warnings[] = count($unclassified).' '.(count($unclassified) === 1 ? 'Dokument ist' : 'Dokumente sind').' noch nicht klassifiziert und zählt/zählen vorläufig als Grundwissen.';
        }

        $chosen = $mandatory;
        $room = max(0, min($target, $available) - $mandatoryCharacters);

        foreach ([[...$this->byScore($strong), ...$routerStrong], [...$this->byScore($weak), ...$routerWeak], $this->capExamples($examples, $context, $notRetrieved)] as $tier) {
            foreach ($tier as $index => $item) {
                $length = $item->document->length();

                if ($length > $room) {
                    if ($item->origin === RetrievedDocument::ORIGIN_ROUTER) {
                        // A router addition that does not fit is skipped; smaller ones and the rest of the room stay usable.
                        $notRetrieved[(string) $item->document->id] = ['document' => $item->document, 'code' => RetrievalResult::OUT_OF_SPACE, 'detail' => 'wegen Umfang nicht mitgesendet ('.implode('; ', $item->reasons).')'];

                        continue;
                    }

                    foreach (array_slice($tier, $index) as $left) {
                        $notRetrieved[(string) $left->document->id] = ['document' => $left->document, 'code' => RetrievalResult::OUT_OF_SPACE, 'detail' => 'wegen Umfang nicht mitgesendet ('.implode('; ', $left->reasons).')'];
                    }

                    $room = 0;

                    break;
                }

                $room -= $length;
                $chosen[(string) $item->document->id] = $item;
            }
        }

        $retrieved = [];

        foreach ($scope as $entry) {
            if (isset($chosen[(string) $entry->document->id])) {
                $retrieved[] = $chosen[(string) $entry->document->id];
            }
        }

        return new RetrievalResult(
            retrieved: $retrieved,
            notRetrieved: array_values($notRetrieved),
            evaluations: $evaluations,
            suppressed: $suppressed,
            unclassified: $unclassified,
            warnings: $warnings,
            mandatoryCharacters: $mandatoryCharacters,
            target: $target,
            available: $available,
        );
    }

    /**
     * Decide for one document: retrieved (with strength and reasons) or not (code and detail).
     *
     * @param  array<string, Evaluation>  $evaluations
     * @param  list<array{id: string, trigger: string, tier: string, sentence: string, exclusion: string}>  $suppressed
     * @return RetrievedDocument|array{code: string, detail: string}
     */
    private function decide(KnowledgeDocument $document, string $class, bool $unclassified, CaseFacts $facts, WhenEvaluator $evaluator, array &$evaluations, array &$suppressed): RetrievedDocument|array
    {
        $id = (string) $document->id;
        $example = in_array($document->type, self::EXAMPLE_TYPES, true);

        if ($class === 'core' || ($class === 'binding' && $example)) {
            return new RetrievedDocument($document, $class, $example ? RetrievedDocument::EXAMPLE : RetrievedDocument::MANDATORY, 0, [$unclassified ? 'Grundwissen (noch nicht klassifiziert)' : 'Grundwissen'], origin: RetrievedDocument::ORIGIN_CORE);
        }

        $match = $this->matcher->match($facts->tiers, $document->triggers(), $document->excludeTriggers());
        $strongHits = array_values(array_filter($match['hits'], fn (TriggerHit $hit): bool => $hit->tier === 'A'));
        $weakHits = array_values(array_filter($match['hits'], fn (TriggerHit $hit): bool => $hit->tier !== 'A'));

        foreach ($match['suppressed'] as $item) {
            $suppressed[] = ['id' => $id, ...$item];
        }

        $evaluation = $document->when() === [] ? null : $evaluator->evaluate($document->when(), $facts);

        if ($evaluation !== null) {
            $evaluations[$id] = $evaluation;
        }

        $score = $this->score($match['hits']);
        $reasons = array_map(fn (TriggerHit $hit): string => 'Auslöser „'.$hit->trigger.'“ ('.($hit->tier === 'A' ? 'aktuelles Anliegen' : 'früherer Verlauf').')', $match['hits']);
        $conditionYes = $evaluation?->result === Tri::Yes;

        if ($class === 'binding') {
            return $this->binding($document, $match, $evaluation, $reasons, $score);
        }

        if ($strongHits !== [] || $conditionYes) {
            return new RetrievedDocument($document, $class, $example ? RetrievedDocument::EXAMPLE : RetrievedDocument::STRONG, max($score, $conditionYes ? 2.0 : 0.0), [...array_map(fn (TriggerHit $hit): string => 'Auslöser „'.$hit->trigger.'“ (aktuelles Anliegen)', $strongHits), ...($conditionYes ? ['Bedingung erfüllt'] : [])]);
        }

        if ($weakHits !== []) {
            return new RetrievedDocument($document, $class, $example ? RetrievedDocument::EXAMPLE : RetrievedDocument::WEAK, $score, $reasons);
        }

        return $this->missed($match, $evaluation);
    }

    /**
     * A binding document comes along by its scope alone, by a hit, by a fulfilled
     * condition, or as a precaution when the condition cannot be checked. A
     * document tied only to actions waits for the safeguard.
     *
     * @param  array{hits: list<TriggerHit>, suppressed: list<array<string, string>>}  $match
     * @param  list<string>  $reasons
     * @return RetrievedDocument|array{code: string, detail: string}
     */
    private function binding(KnowledgeDocument $document, array $match, ?Evaluation $evaluation, array $reasons, float $score): RetrievedDocument|array
    {
        $hasTriggers = $document->triggers() !== [];
        $hasWhen = $document->when() !== [];
        $hasActions = $document->actions() !== [];

        if (! $hasTriggers && ! $hasWhen && ! $hasActions) {
            return new RetrievedDocument($document, 'binding', RetrievedDocument::MANDATORY, 0, ['verbindlich im Geltungsbereich'], origin: RetrievedDocument::ORIGIN_CORE);
        }

        if ($match['hits'] !== []) {
            return new RetrievedDocument($document, 'binding', RetrievedDocument::MANDATORY, $score, $reasons);
        }

        if ($evaluation?->result === Tri::Yes) {
            return new RetrievedDocument($document, 'binding', RetrievedDocument::MANDATORY, 2.0, ['Bedingung erfüllt']);
        }

        if ($evaluation?->result === Tri::Unknown) {
            $keys = $evaluation->unknownKeys();

            return new RetrievedDocument($document, 'binding', RetrievedDocument::MANDATORY, 0, ['Voraussetzung ungeklärt ('.implode(', ', $keys).')'], true, $keys);
        }

        if ($hasActions && ! $hasTriggers && ! $hasWhen) {
            return ['code' => RetrievalResult::ACTION_BOUND, 'detail' => 'maßnahmengebunden: wird bei einer empfohlenen Maßnahme ('.implode(', ', $document->actions()).') nachgeladen'];
        }

        return $this->missed($match, $evaluation, $hasActions);
    }

    /**
     * @param  array{hits: list<TriggerHit>, suppressed: list<array<string, string>>}  $match
     * @return array{code: string, detail: string}
     */
    private function missed(array $match, ?Evaluation $evaluation, bool $actionBound = false): array
    {
        if ($match['suppressed'] !== []) {
            $first = $match['suppressed'][0];

            return ['code' => RetrievalResult::SUPPRESSED, 'detail' => 'Auslöser „'.$first['trigger'].'“ unterdrückt durch „'.$first['exclusion'].'“ im Satz: '.$first['sentence']];
        }

        if ($evaluation?->result === Tri::Unknown) {
            return ['code' => RetrievalResult::CONDITION_UNKNOWN, 'detail' => 'Bedingung nicht prüfbar ('.implode(', ', $evaluation->unknownKeys()).')'];
        }

        if ($evaluation !== null) {
            return ['code' => RetrievalResult::CONDITION_FALSE, 'detail' => 'Bedingung nicht erfüllt'];
        }

        return ['code' => $actionBound ? RetrievalResult::ACTION_BOUND : RetrievalResult::NO_TRIGGER, 'detail' => 'kein Auslöser im Anliegen'];
    }

    /**
     * Trigger hits count by tier: the current concern three times, recent messages once, the rest half;
     * a longer phrase adds a little.
     *
     * @param  list<TriggerHit>  $hits
     */
    private function score(array $hits): float
    {
        $score = 0.0;

        foreach ($hits as $hit) {
            $score += match ($hit->tier) {
                'A' => 3.0,
                'B' => 1.0,
                default => 0.5,
            } + min(2.0, mb_strlen($hit->trigger) / 20);
        }

        return $score;
    }

    /**
     * @param  list<RetrievedDocument>  $items
     * @return list<RetrievedDocument>
     */
    private function byScore(array $items): array
    {
        usort($items, fn (RetrievedDocument $a, RetrievedDocument $b): int => [$b->score, $a->document->id] <=> [$a->score, $b->document->id]);

        return $items;
    }

    /**
     * At most the configured number of good and bad reference cases, those tied to a chosen product first.
     *
     * @param  list<RetrievedDocument>  $examples
     * @param  array<string, array{document: KnowledgeDocument, code: string, detail: string}>  $notRetrieved
     * @return list<RetrievedDocument>
     */
    private function capExamples(array $examples, CaseContext $context, array &$notRetrieved): array
    {
        $limits = ['example-good' => (int) config('knowledge.selection.max_good_examples'), 'example-bad' => (int) config('knowledge.selection.max_bad_examples')];
        $kept = [];

        foreach ($limits as $type => $max) {
            $ofType = array_values(array_filter($examples, fn (RetrievedDocument $item): bool => $item->document->type === $type));
            usort($ofType, fn (RetrievedDocument $a, RetrievedDocument $b): int => [array_intersect($b->document->boundProducts(), $context->products) !== [] ? 1 : 0, $a->document->id] <=> [array_intersect($a->document->boundProducts(), $context->products) !== [] ? 1 : 0, $b->document->id]);

            foreach (array_slice($ofType, $max) as $item) {
                $notRetrieved[(string) $item->document->id] = ['document' => $item->document, 'code' => RetrievalResult::OUT_OF_SPACE, 'detail' => "Obergrenze für Beispiele erreicht ({$max})"];
            }

            array_push($kept, ...array_slice($ofType, 0, $max));
        }

        return $kept;
    }

    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}

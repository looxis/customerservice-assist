<?php

namespace App\Knowledge\Retrieval;

use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeSelectionEntry;

/**
 * What the retrieval decided for one case: what goes into the prompt and why,
 * what was checked but not retrieved, what was left out for size, and which
 * hits an exclusion suppressed (PROJ-36).
 */
final readonly class RetrievalResult
{
    public const string NO_TRIGGER = 'no_trigger';

    public const string CONDITION_FALSE = 'condition_false';

    public const string CONDITION_UNKNOWN = 'condition_unknown';

    public const string SUPPRESSED = 'suppressed';

    public const string ACTION_BOUND = 'action_bound';

    public const string OUT_OF_SPACE = 'out_of_space';

    /**
     * @param  list<RetrievedDocument>  $retrieved  In knowledge base precedence.
     * @param  list<array{document: KnowledgeDocument, code: string, detail: string}>  $notRetrieved
     * @param  array<string, Evaluation>  $evaluations  By document ID, for documents with a condition.
     * @param  list<array{id: string, trigger: string, tier: string, sentence: string, exclusion: string}>  $suppressed
     * @param  list<string>  $unclassified  IDs of documents without a class (counted as core in the transition mode).
     * @param  list<string>  $warnings
     */
    public function __construct(
        public array $retrieved,
        public array $notRetrieved,
        public array $evaluations,
        public array $suppressed,
        public array $unclassified,
        public array $warnings,
        public int $mandatoryCharacters,
        public int $target,
        public int $available,
    ) {}

    /**
     * @return list<KnowledgeSelectionEntry>
     */
    public function entries(): array
    {
        return array_map(fn (RetrievedDocument $item): KnowledgeSelectionEntry => new KnowledgeSelectionEntry($item->document, $item->reason()), $this->retrieved);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_map(fn (RetrievedDocument $item): string => (string) $item->document->id, $this->retrieved);
    }

    public function characters(): int
    {
        return array_sum(array_map(fn (RetrievedDocument $item): int => $item->document->length(), $this->retrieved));
    }

    /**
     * Binding documents tied to an action that did not come along (for the safeguard of PROJ-36).
     *
     * @return list<KnowledgeDocument>
     */
    public function actionBound(): array
    {
        return array_values(array_map(
            fn (array $item): KnowledgeDocument => $item['document'],
            array_filter($this->notRetrieved, fn (array $item): bool => $item['code'] === self::ACTION_BOUND),
        ));
    }

    /**
     * @return list<KnowledgeDocument>
     */
    public function omitted(): array
    {
        return array_values(array_map(
            fn (array $item): KnowledgeDocument => $item['document'],
            array_filter($this->notRetrieved, fn (array $item): bool => $item['code'] === self::OUT_OF_SPACE),
        ));
    }
}

<?php

namespace App\Knowledge;

/**
 * The result of one selection. Holds everything an analysis needs to
 * reproduce and explain which knowledge was used.
 */
final readonly class KnowledgeSelection
{
    /**
     * @param  list<KnowledgeSelectionEntry>  $selected  In knowledge base precedence, then by ID.
     * @param  list<KnowledgeSelectionEntry>  $excluded  Usable documents that were left out, in the same order.
     * @param  list<string>  $unknownProducts  Chosen products without any product file; ignored.
     * @param  list<string>  $productsWithoutKnowledge  Chosen products whose product files are all unusable.
     * @param  list<string>  $warnings
     */
    public function __construct(
        public CaseContext $context,
        public KnowledgeState $state,
        public array $selected,
        public array $excluded,
        public array $unknownProducts,
        public array $productsWithoutKnowledge,
        public int $characterLimit,
        public array $warnings,
    ) {}

    /** @return list<KnowledgeDocument> */
    public function documents(): array
    {
        return array_map(fn (KnowledgeSelectionEntry $entry): KnowledgeDocument => $entry->document, $this->selected);
    }

    /**
     * Fingerprint of every selected document, keyed by ID.
     *
     * @return array<string, string>
     */
    public function fingerprints(): array
    {
        $fingerprints = [];

        foreach ($this->selected as $entry) {
            $fingerprints[$entry->document->id] = $entry->document->fingerprint;
        }

        return $fingerprints;
    }

    public function draftCount(): int
    {
        return count(array_filter($this->selected, fn (KnowledgeSelectionEntry $entry): bool => $entry->isDraft()));
    }

    public function characterCount(): int
    {
        return array_sum(array_map(fn (KnowledgeSelectionEntry $entry): int => $entry->document->length(), $this->selected));
    }

    public function exceedsLimit(): bool
    {
        return $this->characterCount() > $this->characterLimit;
    }

    public function isEmpty(): bool
    {
        return $this->selected === [];
    }
}

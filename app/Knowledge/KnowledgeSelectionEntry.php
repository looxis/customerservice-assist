<?php

namespace App\Knowledge;

/**
 * A document in a selection result with the reason it was selected or left out.
 */
final readonly class KnowledgeSelectionEntry
{
    public function __construct(
        public KnowledgeDocument $document,
        public string $reason,
    ) {}

    public function isDraft(): bool
    {
        return $this->document->isDraft();
    }
}

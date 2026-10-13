<?php

namespace App\Knowledge\Retrieval\Router;

use App\Knowledge\KnowledgeDocument;

/**
 * A document the router adds (PROJ-37): a strong addition when the concern is in the current message
 * (also when it refers back), a weak one when it only stands in the earlier course.
 */
final readonly class RouterAddition
{
    public const string STRONG = 'strong';

    public const string WEAK = 'weak';

    public function __construct(
        public KnowledgeDocument $document,
        public string $strength,
        public int $rank,
        public string $reason,
    ) {}
}

<?php

namespace App\Knowledge\Retrieval;

use App\Knowledge\KnowledgeDocument;

/**
 * A document that goes into the prompt, with why (PROJ-36).
 */
final readonly class RetrievedDocument
{
    public const string MANDATORY = 'mandatory';

    public const string STRONG = 'strong';

    public const string WEAK = 'weak';

    public const string EXAMPLE = 'example';

    /**
     * @param  string  $strength  mandatory (core, binding, by hand), strong or weak (triggered) or example.
     * @param  list<string>  $reasons
     * @param  list<string>  $unknownKeys  Keys of the condition whose facts were unknown; set when the document comes along only as a precaution.
     */
    public function __construct(
        public KnowledgeDocument $document,
        public string $class,
        public string $strength,
        public float $score,
        public array $reasons,
        public bool $unverified = false,
        public array $unknownKeys = [],
    ) {}

    public function reason(): string
    {
        return implode('; ', $this->reasons);
    }
}

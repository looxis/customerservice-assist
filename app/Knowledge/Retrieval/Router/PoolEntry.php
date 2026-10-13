<?php

namespace App\Knowledge\Retrieval\Router;

use App\Knowledge\KnowledgeDocument;

/**
 * One document the router may be offered (PROJ-37), with the hits an exclusion suppressed for it.
 */
final readonly class PoolEntry
{
    /**
     * @param  list<array{trigger: string, tier: string, sentence: string, exclusion: string}>  $suppressed
     */
    public function __construct(
        public KnowledgeDocument $document,
        public array $suppressed = [],
    ) {}

    public function id(): string
    {
        return (string) $this->document->id;
    }

    /**
     * The one line the router sees: ID, title, summary and, if a hit was suppressed, a note about it.
     */
    public function line(): string
    {
        $line = '['.$this->id().'] '.$this->document->title.' – '.trim((string) $this->document->summary());

        if ($this->suppressed !== []) {
            $first = $this->suppressed[0];
            $line .= ' (Unterdrückt: Auslöser „'.$first['trigger'].'“ im Satz „'.$first['sentence'].'“ wurde durch „'.$first['exclusion'].'“ ausgeschlossen)';
        }

        return $line;
    }
}

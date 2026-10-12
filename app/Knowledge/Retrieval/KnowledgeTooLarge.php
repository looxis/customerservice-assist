<?php

namespace App\Knowledge\Retrieval;

use RuntimeException;

/**
 * The mandatory knowledge alone does not fit into the prompt. Nothing is cut;
 * the analysis stops with a clear message (PROJ-36).
 */
class KnowledgeTooLarge extends RuntimeException
{
    public function __construct(public readonly int $mandatory, public readonly int $available)
    {
        parent::__construct('Wissen und Ticketverlauf sind zu umfangreich: Zusammenfassung verwenden oder Admin informieren ('.number_format($mandatory, 0, ',', '.').' Zeichen Pflichtwissen bei '.number_format($available, 0, ',', '.').' Zeichen Platz).');
    }
}

<?php

namespace App\Console\Commands;

use App\Knowledge\KnowledgeLibrary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('knowledge:overview')]
#[Description('Gibt vergebene IDs, nächste freie IDs und Schlagwörter aus, zum Einfügen in den KI-Chat')]
class KnowledgeOverview extends Command
{
    public function handle(KnowledgeLibrary $library): int
    {
        $this->line($library->overview());

        return self::SUCCESS;
    }
}

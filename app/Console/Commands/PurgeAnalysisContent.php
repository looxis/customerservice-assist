<?php

namespace App\Console\Commands;

use App\Analysis\AnalysisStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('analysis:purge')]
#[Description('Leert Kundeninhalte von Analysen nach der Aufbewahrungsfrist und löscht alte Zusammenfassungen und gemerkte Auswahlen')]
class PurgeAnalysisContent extends Command
{
    public function handle(AnalysisStore $store): int
    {
        $counts = $store->purge();

        $this->line("Analysen bereinigt: {$counts['analyses']}, Zusammenfassungen gelöscht: {$counts['summaries']}, gemerkte Auswahlen gelöscht: {$counts['choices']}, Kundengruppen je Kunde gelöscht: {$counts['customers']}");

        return self::SUCCESS;
    }
}

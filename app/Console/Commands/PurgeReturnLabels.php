<?php

namespace App\Console\Commands;

use App\Returns\ReturnLabelService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('return-labels:purge')]
#[Description('Löscht die PDF-Dateien der DHL-Retourenlabels nach der Aufbewahrungsfrist (Sendungsnummern bleiben)')]
class PurgeReturnLabels extends Command
{
    public function handle(ReturnLabelService $labels): int
    {
        $counts = $labels->purge();

        Log::info('Return labels purged', $counts);

        $this->line("Label-Dateien gelöscht: {$counts['files']}");

        return self::SUCCESS;
    }
}

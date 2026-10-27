<?php

namespace App\Console\Commands;

use App\Quotes\QuoteBackupImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('quotes:import {file : Pfad zur Sicherung des alten Rabattrechners (JSON)}')]
#[Description('Liest die Sicherung des Browser-Rabattrechners (Artikel, Kosten, Angebote) in die Datenbank ein')]
class ImportQuoteBackup extends Command
{
    public function handle(QuoteBackupImporter $importer): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Datei nicht lesbar: {$path}");

            return self::FAILURE;
        }

        try {
            $counts = $importer->import((string) file_get_contents($path));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line("Artikel: {$counts['articles']} (davon mit vollständigen Kosten: {$counts['with_costs']}), Angebote: {$counts['offers']}");

        return self::SUCCESS;
    }
}

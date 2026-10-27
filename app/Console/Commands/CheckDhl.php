<?php

namespace App\Console\Commands;

use App\Dhl\DhlClient;
use App\Dhl\DhlException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dhl:check')]
#[Description('Prüft die DHL-Anbindung (Anmeldung) und listet die eingerichteten Retourenempfänger')]
class CheckDhl extends Command
{
    public function handle(DhlClient $dhl): int
    {
        try {
            $locations = $dhl->locations();
        } catch (DhlException $exception) {
            $this->error($exception->problem->message());

            return self::FAILURE;
        }

        $this->info('Anmeldung bei DHL erfolgreich.');

        if ($locations === []) {
            $this->warn('Es ist kein Retourenempfänger eingerichtet.');

            return self::SUCCESS;
        }

        $this->table(['Empfänger-ID', 'Abrechnungsnummer', 'Name'], array_map(fn (array $row): array => [$row['receiverId'], $row['billingNumber'] ?? '–', $row['name'] ?? '–'], $locations));

        return self::SUCCESS;
    }
}

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
        if (! $dhl->hasLogin()) {
            $this->error('Es fehlen Zugangsdaten: DHL_CLIENT_ID, DHL_CLIENT_SECRET, DHL_USERNAME und DHL_PASSWORD müssen in der .env stehen (danach „sail artisan config:clear“, falls die Konfiguration zwischengespeichert ist).');

            return self::FAILURE;
        }

        try {
            $locations = $dhl->locations();
        } catch (DhlException $exception) {
            $this->error($exception->problem->message());

            if ($exception->detail !== null) {
                $this->line('Antwort von DHL: '.$exception->detail);
            }

            $this->line('Angefragt wird: '.parse_url((string) config('services.dhl.token_url'), PHP_URL_HOST).(str_contains((string) config('services.dhl.token_url'), 'sandbox') ? ' (Sandbox)' : ' (Produktion)'));

            return self::FAILURE;
        }

        $this->info('Anmeldung bei DHL erfolgreich.');

        if ($locations === []) {
            $this->warn('Es ist kein Retourenempfänger eingerichtet.');

            return self::SUCCESS;
        }

        $this->table(['Empfänger-ID', 'Abrechnungsnummer', 'Name'], array_map(fn (array $row): array => [$row['receiverId'], $row['billingNumber'] ?? '–', $row['name'] ?? '–'], $locations));

        $this->line($dhl->isConfigured() ? 'DHL_RECEIVER_ID ist gesetzt.' : 'Trage die gewünschte Empfänger-ID als DHL_RECEIVER_ID in die .env ein.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Dhl\DhlClient;
use App\Dhl\DhlException;
use App\Returns\ReturnCountry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dhl:check')]
#[Description('Prüft die DHL-Anbindung (Anmeldung) und listet die eingerichteten Retourenempfänger je Land')]
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

            $this->line('HTTP-Status: '.($exception->httpStatus ?? 'keine Antwort'));
            $this->line('Antwort von DHL: '.($exception->detail ?? '(leer)'));

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

        $this->newLine();
        $this->line('Empfänger für Rücksendungen aus dem Ausland (die App nimmt den einen Empfänger des Landes):');

        $rows = [];

        foreach (ReturnCountry::cases() as $country) {
            if ($country->isDomestic()) {
                continue;
            }

            try {
                $found = $dhl->locations($country);
                $rows[] = [$country->label(), implode(', ', array_map(fn (array $row): string => $row['receiverId'], $found)) ?: '–', count($found) === 1 ? 'in Ordnung' : 'kein Label möglich: genau ein Empfänger nötig'];
            } catch (DhlException $exception) {
                $rows[] = [$country->label(), '–', 'Abfrage fehlgeschlagen (HTTP '.($exception->httpStatus ?? 'keine Antwort').')'];
            }
        }

        $this->table(['Land', 'Empfänger-ID', 'Stand'], $rows);

        return self::SUCCESS;
    }
}

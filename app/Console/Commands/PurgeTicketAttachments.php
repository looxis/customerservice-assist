<?php

namespace App\Console\Commands;

use App\Zammad\AttachmentStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('attachments:purge')]
#[Description('Löscht angesehene Ticket-Anhänge aus dem Zwischenspeicher nach der Aufbewahrungsfrist')]
class PurgeTicketAttachments extends Command
{
    public function handle(AttachmentStore $store): int
    {
        $counts = $store->purge();

        Log::info('Ticket attachments purged', $counts);

        $this->line("Anhänge gelöscht: {$counts['files']}, leere Ordner gelöscht: {$counts['folders']}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\WismoRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('wismo:purge')]
#[Description('Empty the reply texts of the automatic WISMO answers after their retention period (PROJ-82)')]
class PurgeWismoTexts extends Command
{
    /**
     * The result, the reason and the tracking data stay; only the texts that went to the customer are emptied.
     */
    public function handle(): int
    {
        $emptied = WismoRun::query()
            ->where('created_at', '<', now()->subDays(WismoRun::TEXT_DAYS))
            ->where(fn ($query) => $query->whereNotNull('reply_text')->orWhereNotNull('reply_text_de'))
            ->update(['reply_text' => null, 'reply_text_de' => null]);

        Log::info('WISMO texts purged', compact('emptied'));
        $this->info("Geleert: {$emptied} Antworttexte.");

        return self::SUCCESS;
    }
}

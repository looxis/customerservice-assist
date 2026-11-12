<?php

namespace App\Console\Commands;

use App\Models\ClaimReplyRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('claim-reply:purge')]
#[Description('Empty the reply texts of the automatic first replies to complaints after their retention period (PROJ-85)')]
class PurgeClaimReplyTexts extends Command
{
    /**
     * The result, the reason and the counts stay; only the texts that went to the customer are emptied.
     */
    public function handle(): int
    {
        $emptied = ClaimReplyRun::query()
            ->where('created_at', '<', now()->subDays(ClaimReplyRun::TEXT_DAYS))
            ->where(fn ($query) => $query->whereNotNull('reply_text')->orWhereNotNull('reply_text_de'))
            ->update(['reply_text' => null, 'reply_text_de' => null]);

        Log::info('Claim reply texts purged', compact('emptied'));
        $this->info("Geleert: {$emptied} Antworttexte.");

        return self::SUCCESS;
    }
}

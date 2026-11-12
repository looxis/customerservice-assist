<?php

namespace App\Console\Commands;

use App\ClaimReply\ClaimReplyResponder;
use App\Jobs\AnswerClaimTicket;
use App\Models\ClaimReplyRun;
use App\Models\LabelTicket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('claim-reply:recover')]
#[Description('Start abandoned automatic first replies to complaints again (PROJ-85)')]
class RecoverClaimReplyRuns extends Command
{
    /**
     * A run that stayed "working" or "retry" for minutes lost its job (the worker was killed, the queue was emptied);
     * the job takes it over, or leaves a note after the third attempt. Runs with an unclear sending are left alone.
     */
    public function handle(): int
    {
        $before = now()->subMinutes(ClaimReplyResponder::STALE_MINUTES);
        $runs = ClaimReplyRun::query()->whereIn('result', [ClaimReplyRun::WORKING, ClaimReplyRun::RETRY])->where('updated_at', '<', $before)->get();
        $started = 0;

        foreach ($runs as $run) {
            $label = LabelTicket::query()->where('zammad_id', $run->zammad_id)->first();

            if ($label !== null) {
                AnswerClaimTicket::dispatch($label->id);
                $started++;
            }
        }

        $this->info("Neu gestartet: {$started} Bearbeitungen.");

        return self::SUCCESS;
    }
}

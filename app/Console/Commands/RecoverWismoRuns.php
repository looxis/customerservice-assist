<?php

namespace App\Console\Commands;

use App\Jobs\AnswerWismoTicket;
use App\Models\LabelTicket;
use App\Models\WismoRun;
use App\Wismo\WismoResponder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wismo:recover')]
#[Description('Start abandoned automatic WISMO replies again (PROJ-82)')]
class RecoverWismoRuns extends Command
{
    /**
     * A run that stayed "working" or "retry" for minutes lost its job (the worker was killed, the queue was emptied);
     * the job takes it over, or leaves a note after the third attempt. Runs with an unclear sending are left alone.
     */
    public function handle(): int
    {
        $before = now()->subMinutes(WismoResponder::STALE_MINUTES);
        $runs = WismoRun::query()->whereIn('result', [WismoRun::WORKING, WismoRun::RETRY])->where('updated_at', '<', $before)->get();
        $started = 0;

        foreach ($runs as $run) {
            $label = LabelTicket::query()->where('zammad_id', $run->zammad_id)->first();

            if ($label !== null) {
                AnswerWismoTicket::dispatch($label->id);
                $started++;
            }
        }

        $this->info("Neu gestartet: {$started} Bearbeitungen.");

        return self::SUCCESS;
    }
}

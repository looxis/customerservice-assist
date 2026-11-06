<?php

namespace App\Labeling;

use App\Jobs\ClassifyLabelTicket;
use App\Jobs\FetchLabelWindow;
use App\Jobs\PushLabelTag;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use Carbon\CarbonImmutable;

/**
 * Starts and counts the runs of the ticket tagging (PROJ-78): retrieval, classification and writing the tags.
 */
class LabelRunner
{
    public function __construct(private readonly LabelTagging $tagging) {}

    public function active(string $kind): ?LabelRun
    {
        return LabelRun::query()->where('kind', $kind)->whereIn('state', ['queued', 'running'])->latest('id')->first();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function start(string $kind, string $admin, array $options = []): LabelRun
    {
        $run = LabelRun::query()->create(['kind' => $kind, 'state' => 'running', 'admin' => $admin, 'options' => $options, 'started_at' => now()]);

        match ($kind) {
            'fetch' => $this->startFetch($run),
            'classify' => $this->startClassify($run, LabelTicket::query()->where('state', 'captured')->pluck('id')->all()),
            'reclassify' => $this->startClassify($run, LabelTicket::query()->whereNull('decision')->whereNull('tag_value')->whereIn('state', ['captured', 'classified', 'failed'])->pluck('id')->all()),
            'tags' => $this->startTags($run, (bool) ($options['dry_run'] ?? true)),
        };

        return $run->refresh();
    }

    public function count(LabelRun $run, bool $ok): void
    {
        $run->increment($ok ? 'done' : 'failed');
        $this->finishIfDone($run->refresh());
    }

    public function finishIfDone(LabelRun $run): void
    {
        if ($run->isActive() && ($run->cursor['windows'] ?? []) === [] && $run->done + $run->failed >= $run->total) {
            $run->update(['state' => 'done', 'finished_at' => now()]);
        }
    }

    public function cancel(LabelRun $run): void
    {
        if ($run->isActive()) {
            $run->update(['state' => 'cancelled', 'cancel_requested' => true, 'finished_at' => now()]);
        }
    }

    private function startFetch(LabelRun $run): void
    {
        $windows = [];
        $from = CarbonImmutable::parse((string) config('labeling.earliest'))->startOfMonth()->utc();
        $end = CarbonImmutable::now()->utc();

        for ($start = $from; $start < $end; $start = $start->addMonth()) {
            $windows[] = [$start->toIso8601ZuluString(), min($start->addMonth(), $end)->toIso8601ZuluString()];
        }

        $run->update(['cursor' => ['windows' => $windows, 'page' => 1]]);
        FetchLabelWindow::dispatch($run->id);
    }

    /**
     * @param  list<int>  $ids
     */
    private function startClassify(LabelRun $run, array $ids): void
    {
        $run->update(['total' => count($ids)]);

        foreach ($ids as $id) {
            ClassifyLabelTicket::dispatch($run->id, $id);
        }

        $this->finishIfDone($run->refresh());
    }

    private function startTags(LabelRun $run, bool $dry): void
    {
        $tickets = $this->tagging->pending();
        $run->update(['planned' => $tickets->count(), 'total' => $dry ? 0 : $tickets->count()]);

        foreach ($dry ? [] : $tickets as $ticket) {
            PushLabelTag::dispatch($run->id, $ticket->id);
        }

        $this->finishIfDone($run->refresh());
    }
}

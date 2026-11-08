<?php

namespace App\Jobs;

use App\Analysis\TicketContext;
use App\Jobs\Middleware\SkipInactiveLabelRun;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Labeling\LabelTagging;
use App\Labeling\SenderFilter;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Reads the first customer message and the tags of one Zammad ticket into the ticket tagging (PROJ-78).
 */
class CaptureLabelTicket implements ShouldQueue
{
    use Queueable;

    /**
     * Waiting for the rate limit releases a job and counts as an attempt, so only the exceptions are limited.
     */
    public int $tries = 0;

    public int $maxExceptions = 5;

    public function __construct(public int $runId, public int $zammadId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new SkipInactiveLabelRun, new RateLimited('labeling-zammad')];
    }

    public function handle(ZammadClient $zammad, LabelingSettings $settings, LabelRunner $runner, SenderFilter $senders): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run === null || ! $run->isActive()) {
            return;
        }

        if (LabelTicket::query()->where('zammad_id', $this->zammadId)->exists()) {
            $runner->count($run, true);

            return;
        }

        try {
            $head = $zammad->ticketHead($this->zammadId);

            if ($head->state === 'merged') {
                $runner->count($run, true);

                return;
            }

            $tags = $zammad->ticketTags($this->zammadId);
            ['article' => $article, 'contacted_first' => $contactedFirst] = $zammad->firstCustomerContact($this->zammadId, $head->customerId);
        } catch (ZammadException $exception) {
            if ($exception->problem === ZammadProblem::NotFound) {
                $runner->count($run, false);

                return;
            }

            throw $exception;
        }

        $existing = LabelTagging::existing($tags);

        $filteredBySender = $senders->matches($article?->from);

        if ($existing === null && ($filteredBySender || $contactedFirst)) {
            LabelTicket::query()->create([
                'zammad_id' => $this->zammadId,
                'number' => $head->number,
                'zammad_created_at' => $head->createdAt,
                'source' => 'pool',
                'split' => 'training',
                'state' => LabelTicket::EXCLUDED,
                'problem' => $filteredBySender ? 'Entfernt (Absenderfilter)' : 'Entfernt (zuerst von uns kontaktiert)',
                'decided_by' => $filteredBySender ? 'Absenderfilter' : 'Erstkontakt durch uns',
                'last_activity_at' => now(),
            ]);

            $runner->count($run, true);

            return;
        }

        $test = $existing !== null ? $existing['test'] : abs(crc32('labeling-'.$this->zammadId)) % 100 < $settings->all()['test_share'];

        LabelTicket::query()->create([
            'zammad_id' => $this->zammadId,
            'number' => $head->number,
            'group_name' => $head->group,
            'article_id' => $article?->id,
            'text' => $article === null ? null : TicketContext::plainText($article),
            'subject' => $head->title === '(ohne Betreff)' ? null : $head->title,
            'sender' => $article?->from,
            'zammad_created_at' => $head->createdAt,
            'source' => $existing === null ? 'pool' : 'vorhanden',
            'split' => $test ? 'test' : 'training',
            'state' => $article === null ? 'unusable' : 'captured',
            'problem' => $article === null ? 'Keine Kundennachricht gefunden' : null,
            'existing_tag' => $existing['tag'] ?? null,
            'decision' => $existing['category']->value ?? null,
            'decision_origin' => $existing === null ? null : 'vorhanden',
            'tag_kind' => $existing === null ? null : 'vorhanden',
            'tag_value' => $existing['tag'] ?? null,
            'last_activity_at' => now(),
        ]);

        $runner->count($run, true);
    }

    public function failed(): void
    {
        $run = LabelRun::query()->find($this->runId);

        if ($run !== null) {
            app(LabelRunner::class)->count($run, false);
        }
    }
}

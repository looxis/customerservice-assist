<?php

namespace App\Jobs;

use App\Analysis\TicketContext;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Labeling\LabelTagging;
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

    public int $tries = 5;

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
        return [new RateLimited('labeling-zammad')];
    }

    public function handle(ZammadClient $zammad, LabelingSettings $settings, LabelRunner $runner): void
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
            $article = $zammad->firstCustomerArticle($this->zammadId, $head->customerId);
        } catch (ZammadException $exception) {
            if ($exception->problem === ZammadProblem::NotFound) {
                $runner->count($run, false);

                return;
            }

            throw $exception;
        }

        $existing = LabelTagging::existing($tags);
        $test = $existing !== null ? $existing['test'] : abs(crc32('labeling-'.$this->zammadId)) % 100 < $settings->all()['test_share'];

        LabelTicket::query()->create([
            'zammad_id' => $this->zammadId,
            'number' => $head->number,
            'group_name' => $head->group,
            'article_id' => $article?->id,
            'text' => $article === null ? null : TicketContext::plainText($article),
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

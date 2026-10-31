<?php

namespace App\Jobs;

use App\Models\TrackedTicket;
use App\Models\ZammadWebhookDelivery;
use App\Zammad\SyncStatus;
use App\Zammad\TicketSynchronizer;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Reads one ticket from Zammad into CSA (PROJ-66). Unique only while waiting: a
 * change that arrives during the run still gets its own run. The sync commands
 * run it directly and take its exceptions as "this run is not complete".
 */
class SyncZammadTicket implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 600;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 180];
    }

    public function __construct(
        public readonly int $zammadId,
        public readonly ?string $listedUpdatedAt = null,
        public readonly bool $readArticles = false,
        public readonly bool $partOfRun = false,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->zammadId;
    }

    public function handle(TicketSynchronizer $synchronizer): void
    {
        try {
            $synchronizer->sync($this->zammadId, $this->listedUpdatedAt, $this->readArticles);
        } catch (LockTimeoutException $exception) {
            if ($this->partOfRun) {
                throw $exception;
            }

            $this->release(30);

            return;
        } catch (ZammadException $exception) {
            if (! $this->partOfRun && $exception->problem !== ZammadProblem::Unavailable) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }

        $this->closeDeliveries(ZammadWebhookDelivery::PROCESSED);
    }

    /**
     * All attempts are used up. A busy ticket is no problem of the ticket; the next run takes it.
     */
    public function failed(?Throwable $exception): void
    {
        if ($exception instanceof LockTimeoutException) {
            return;
        }

        rescue(function () use ($exception): void {
            $row = TrackedTicket::query()->where('zammad_id', $this->zammadId)->first();
            $row?->update(['sync_status' => SyncStatus::SyncFailed, 'sync_problem_since' => $row->sync_problem_since ?? now()]);
            $this->closeDeliveries(ZammadWebhookDelivery::FAILED, $exception instanceof ZammadException ? $exception->problem->value : 'error');
        });
    }

    private function closeDeliveries(string $status, ?string $errorCode = null): void
    {
        ZammadWebhookDelivery::query()
            ->where('zammad_ticket_id', $this->zammadId)
            ->where('status', ZammadWebhookDelivery::RECEIVED)
            ->update(['status' => $status, 'error_code' => $errorCode, 'processed_at' => now()]);
    }
}

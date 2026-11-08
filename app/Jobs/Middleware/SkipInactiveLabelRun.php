<?php

namespace App\Jobs\Middleware;

use App\Models\LabelRun;
use Closure;

/**
 * Drops a job of the ticket tagging whose run was cancelled or finished (PROJ-78). It runs before the rate limit,
 * so the jobs left over from a cancelled run do not hold up the next run.
 */
class SkipInactiveLabelRun
{
    public function handle(object $job, Closure $next): void
    {
        $run = LabelRun::query()->find($job->runId);

        if ($run === null || ! $run->isActive()) {
            return;
        }

        $next($job);
    }
}

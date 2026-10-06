<?php

namespace App\Analysis;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/**
 * Temporary, encrypted storage for summaries and results until the analysis
 * log (PROJ-11) keeps them in the database.
 */
class AnalysisStore
{
    public function summary(string $ticketNumber): ?Summary
    {
        $data = $this->get("analysis.summary.{$ticketNumber}");

        return $data === null ? null : Summary::fromArray($data);
    }

    public function putSummary(string $ticketNumber, Summary $summary): void
    {
        $this->put("analysis.summary.{$ticketNumber}", $summary->toArray());
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function putResult(array $result): string
    {
        $id = (string) Str::uuid();
        $this->put("analysis.result.{$id}", $result);

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function result(string $id): ?array
    {
        return Str::isUuid($id) ? $this->get("analysis.result.{$id}") : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function put(string $key, array $data): void
    {
        Cache::put($key, Crypt::encrypt($data), now()->addDays((int) config('analysis.retention_days')));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get(string $key): ?array
    {
        $value = Cache::get($key);

        if (! is_string($value)) {
            return null;
        }

        try {
            $data = Crypt::decrypt($value);
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }
}

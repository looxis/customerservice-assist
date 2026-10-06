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
     * The customer group and products chosen for a ticket, with who chose them.
     *
     * @return array{group: string, products: list<string>, staff: string, at: string}|null
     */
    public function caseChoice(string $ticketNumber): ?array
    {
        return $this->get("analysis.case.{$ticketNumber}");
    }

    /**
     * @param  list<string>  $products
     */
    public function putCaseChoice(string $ticketNumber, string $group, array $products, string $staff): void
    {
        $this->put("analysis.case.{$ticketNumber}", ['group' => $group, 'products' => $products, 'staff' => $staff, 'at' => now()->toIso8601String()]);
    }

    /**
     * The customer group last chosen for a Zammad customer or organization.
     */
    public function customerGroup(?string $customerKey): ?string
    {
        return $customerKey === null ? null : ($this->get("analysis.customer-group.{$customerKey}")['group'] ?? null);
    }

    public function putCustomerGroup(?string $customerKey, string $group): void
    {
        if ($customerKey !== null) {
            $this->put("analysis.customer-group.{$customerKey}", ['group' => $group], (int) config('analysis.customer_group_retention_days'));
        }
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
     * Save a changed result, e.g. with the edited reply (PROJ-10).
     *
     * @param  array<string, mixed>  $result
     */
    public function updateResult(string $id, array $result): void
    {
        $this->put("analysis.result.{$id}", $result);
    }

    /**
     * The latest analysis of a ticket (or of a test-run cut point, see Ticket::summaryKey()).
     */
    public function latest(string $ticketKey): ?string
    {
        return $this->get("analysis.latest.{$ticketKey}")['id'] ?? null;
    }

    public function putLatest(string $ticketKey, string $id): void
    {
        $this->put("analysis.latest.{$ticketKey}", ['id' => $id]);
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
    private function put(string $key, array $data, ?int $days = null): void
    {
        Cache::put($key, Crypt::encrypt($data), now()->addDays($days ?? (int) config('analysis.retention_days')));
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

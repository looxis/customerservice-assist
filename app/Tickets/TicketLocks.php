<?php

namespace App\Tickets;

use App\Models\TicketLock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Keeps two people from working on the same ticket at once (PROJ-96). The person who opens a free ticket
 * holds it while there is activity on their page; five minutes without activity free it again. The same
 * name never locks itself out (without a login the name is all the app knows).
 */
class TicketLocks
{
    public const int TTL_MINUTES = 5;

    /**
     * The active lock of someone else on the ticket, or null when it is free or held by the given name.
     */
    public function holder(string $number, ?string $me): ?TicketLock
    {
        $lock = TicketLock::query()->where('ticket_number', $number)->where('last_activity_at', '>=', $this->threshold())->first();

        return $lock === null || $lock->staff_name === $me ? null : $lock;
    }

    /**
     * Takes or extends the lock for the name. Returns the active lock of someone else when that one wins.
     */
    public function touch(string $number, string $me): ?TicketLock
    {
        try {
            return DB::transaction(function () use ($number, $me): ?TicketLock {
                $lock = TicketLock::query()->where('ticket_number', $number)->lockForUpdate()->first();

                if ($lock !== null && $lock->staff_name !== $me && $lock->last_activity_at->gte($this->threshold())) {
                    return $lock;
                }

                TicketLock::query()->updateOrCreate(['ticket_number' => $number], ['staff_name' => $me, 'last_activity_at' => now()]);

                return null;
            });
        } catch (UniqueConstraintViolationException) {
            // Someone else created the row in the same moment: their lock wins.
            return $this->holder($number, $me);
        }
    }

    /**
     * Frees the ticket, but only from the name's own lock.
     */
    public function release(string $number, string $me): void
    {
        TicketLock::query()->where('ticket_number', $number)->where('staff_name', $me)->delete();
    }

    /**
     * Who works on which of the given tickets, without the given name's own locks.
     *
     * @param  iterable<string>  $numbers
     * @return array<string, string> ticket number => name
     */
    public function activeFor(iterable $numbers, ?string $me): array
    {
        $numbers = collect($numbers)->map(fn ($number): string => (string) $number)->values()->all();

        if ($numbers === []) {
            return [];
        }

        return TicketLock::query()
            ->whereIn('ticket_number', $numbers)
            ->where('last_activity_at', '>=', $this->threshold())
            ->when($me !== null, fn ($query) => $query->where('staff_name', '!=', $me))
            ->pluck('staff_name', 'ticket_number')
            ->all();
    }

    private function threshold(): Carbon
    {
        return now()->subMinutes(self::TTL_MINUTES);
    }
}

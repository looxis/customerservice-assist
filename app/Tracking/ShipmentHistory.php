<?php

namespace App\Tracking;

/**
 * The course of a shipment read from the whole tracking history (PROJ-99), not only from the latest event: a parcel
 * that went back to LOOXIS shows "delivered" at the end, but it was delivered to us. The case is decided here in code,
 * never by the language model; the milestones are the few events worth telling the customer (attempts, pickup point,
 * notification, storage period, address, return), without scans, sorting and loading.
 */
final readonly class ShipmentHistory
{
    public const string DELIVERED = 'delivered';

    public const string IN_TRANSIT = 'in_transit';

    public const string UNKNOWN = 'unknown';

    public const string PICKUP_READY = 'pickup_ready';

    public const string RETURNED_NOT_COLLECTED = 'returned_not_collected';

    public const string RETURNED_ADDRESS = 'returned_address';

    public const string RETURNED_REFUSED = 'returned_refused';

    public const string ADDRESS_HOLD = 'address_hold';

    public const string SPECIAL = 'special';

    /** The cases in which the customer has to act or nothing is open; only a special case keeps the ticket open. */
    public const array CLOSABLE = [self::DELIVERED, self::IN_TRANSIT, self::UNKNOWN, self::PICKUP_READY, self::RETURNED_NOT_COLLECTED, self::RETURNED_ADDRESS, self::RETURNED_REFUSED, self::ADDRESS_HOLD];

    /** The cases that count as a delivery problem. */
    public const array PROBLEMS = [self::RETURNED_NOT_COLLECTED, self::RETURNED_ADDRESS, self::RETURNED_REFUSED, self::ADDRESS_HOLD, self::SPECIAL];

    /** From the most to the least serious; the case of a ticket with several shipments is the most serious one. */
    public const array SEVERITY = [self::SPECIAL, self::RETURNED_ADDRESS, self::RETURNED_REFUSED, self::RETURNED_NOT_COLLECTED, self::ADDRESS_HOLD, self::PICKUP_READY, self::IN_TRANSIT, self::UNKNOWN, self::DELIVERED];

    public const int MAX_MILESTONES = 10;

    private const array PROBLEM_KINDS = ['return', 'not_collected', 'address', 'refused', 'absent', 'attempt', 'special'];

    public const string BACK_AT_LOOXIS = 'Die Sendung ist wieder beim Absender (LOOXIS) eingegangen.';

    /**
     * @param  list<array{date: ?string, text: string}>  $milestones
     */
    public function __construct(
        public ?string $returnCase = null,
        public bool $returnedToUs = false,
        public ?string $returnedOn = null,
        public ?string $holdCase = null,
        public ?string $problem = null,
        public ?int $storageDays = null,
        public array $milestones = [],
    ) {}

    /**
     * @param  list<TrackingEvent>  $events  In any order; carriers send the newest first.
     */
    public static function from(array $events): self
    {
        $events = self::chronological($events);
        $kinds = array_map(fn (TrackingEvent $event): array => self::kindsOf($event), $events);
        $returnAt = self::firstIndex($kinds, ['return', 'not_collected', 'refused']);
        $storage = null;

        foreach ($events as $index => $event) {
            if (in_array('not_collected', $kinds[$index], true)) {
                $storage ??= DeliveryText::storageDays($event->text);
            }
        }

        if ($returnAt !== null) {
            [$case, $problem] = self::returnReason($events, $kinds, $returnAt);
            $back = null;

            foreach ($events as $index => $event) {
                if ($index > $returnAt && $event->delivered) {
                    $back = $event;
                }
            }

            return new self(
                returnCase: $case,
                returnedToUs: $back !== null,
                returnedOn: self::date($back),
                problem: $problem,
                storageDays: $storage,
                milestones: self::milestones($events, $kinds, $returnAt),
            );
        }

        [$hold, $problem] = self::hold($events, $kinds);

        return new self(holdCase: $hold, problem: $problem, storageDays: $storage, milestones: self::milestones($events, $kinds, null));
    }

    /**
     * The case of the shipment, from its history and its current state.
     */
    public function case(bool $known, bool $delivered, bool $pickupReady): string
    {
        return match (true) {
            $this->returnCase !== null => $this->returnCase,
            $delivered => self::DELIVERED,
            $pickupReady => self::PICKUP_READY,
            $this->holdCase !== null => $this->holdCase,
            $known => self::IN_TRANSIT,
            default => self::UNKNOWN,
        };
    }

    public function returned(): bool
    {
        return $this->returnCase !== null;
    }

    public static function closable(string $case): bool
    {
        return in_array($case, self::CLOSABLE, true);
    }

    /**
     * The most serious of the cases, or null without any.
     *
     * @param  list<string>  $cases
     */
    public static function worst(array $cases): ?string
    {
        foreach (self::SEVERITY as $case) {
            if (in_array($case, $cases, true)) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @param  list<TrackingEvent>  $events
     * @return list<TrackingEvent>
     */
    private static function chronological(array $events): array
    {
        $indexed = array_map(fn (TrackingEvent $event, int $index): array => [$event, $index], array_values($events), array_keys(array_values($events)));

        usort($indexed, function (array $a, array $b): int {
            $byTime = ($a[0]->at?->getTimestamp() ?? 0) <=> ($b[0]->at?->getTimestamp() ?? 0);

            return $byTime !== 0 ? $byTime : $b[1] <=> $a[1];
        });

        return array_map(fn (array $pair): TrackingEvent => $pair[0], $indexed);
    }

    /**
     * @return list<string>
     */
    private static function kindsOf(TrackingEvent $event): array
    {
        $kinds = DeliveryText::kinds($event->text);

        if ($event->failure && array_intersect($kinds, self::PROBLEM_KINDS) === []) {
            $kinds[] = 'special';
        }

        return $kinds;
    }

    /**
     * @param  list<list<string>>  $kinds
     * @param  list<string>  $wanted
     */
    private static function firstIndex(array $kinds, array $wanted): ?int
    {
        foreach ($kinds as $index => $eventKinds) {
            if (array_intersect($eventKinds, $wanted) !== []) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Why the shipment went back: the reason in the return event or in the events of the same minute (DHL writes
     * "Empfänger nicht gefunden" and "geht zurück zum Absender" as two events), else the nearest earlier reason.
     *
     * @param  list<TrackingEvent>  $events
     * @param  list<list<string>>  $kinds
     * @return array{0: string, 1: string}
     */
    private static function returnReason(array $events, array $kinds, int $returnAt): array
    {
        $moment = $events[$returnAt]->at;
        $group = array_keys(array_filter($events, fn (TrackingEvent $event, int $index): bool => $index === $returnAt || ($moment !== null && $event->at !== null && abs($event->at->getTimestamp() - $moment->getTimestamp()) <= 60), ARRAY_FILTER_USE_BOTH));

        $best = null;

        foreach ($group as $index) {
            $case = self::reason($kinds[$index]);

            if ($case !== null && ($best === null || array_search($case, self::SEVERITY, true) < array_search($best[0], self::SEVERITY, true))) {
                $best = [$case, $events[$index]->text];
            }
        }

        if ($best !== null) {
            return $best;
        }

        for ($index = $returnAt - 1; $index >= 0; $index--) {
            $case = self::reason($kinds[$index]);

            if ($case !== null) {
                return [$case, $events[$returnAt]->text];
            }
        }

        $attempted = collect(array_slice($kinds, 0, $returnAt + 1))->contains(fn (array $eventKinds): bool => in_array('attempt', $eventKinds, true));

        return [$attempted ? self::RETURNED_NOT_COLLECTED : self::SPECIAL, $events[$returnAt]->text];
    }

    /**
     * @param  list<string>  $kinds
     */
    private static function reason(array $kinds): ?string
    {
        return match (true) {
            in_array('special', $kinds, true) => self::SPECIAL,
            in_array('address', $kinds, true) => self::RETURNED_ADDRESS,
            in_array('refused', $kinds, true) => self::RETURNED_REFUSED,
            in_array('not_collected', $kinds, true), in_array('absent', $kinds, true) => self::RETURNED_NOT_COLLECTED,
            default => null,
        };
    }

    /**
     * A problem without return that is still open: an address error or a special case after which the parcel was
     * neither delivered nor brought to a pickup point.
     *
     * @param  list<TrackingEvent>  $events
     * @param  list<list<string>>  $kinds
     * @return array{0: ?string, 1: ?string}
     */
    private static function hold(array $events, array $kinds): array
    {
        $last = null;

        foreach ($kinds as $index => $eventKinds) {
            if (in_array('special', $eventKinds, true) || in_array('address', $eventKinds, true)) {
                $last = $index;
            }
        }

        if ($last === null) {
            return [null, null];
        }

        foreach ($events as $index => $event) {
            if ($index > $last && ($event->delivered || in_array('pickup', $kinds[$index], true))) {
                return [null, null];
            }
        }

        return [in_array('special', $kinds[$last], true) ? self::SPECIAL : self::ADDRESS_HOLD, $events[$last]->text];
    }

    /**
     * @param  list<TrackingEvent>  $events
     * @param  list<list<string>>  $kinds
     * @return list<array{date: ?string, text: string}>
     */
    private static function milestones(array $events, array $kinds, ?int $returnAt): array
    {
        $milestones = [];

        foreach ($events as $index => $event) {
            if ($kinds[$index] === [] && ! $event->delivered) {
                continue;
            }

            $text = $returnAt !== null && $index > $returnAt && $event->delivered ? self::BACK_AT_LOOXIS : $event->text;

            if ($milestones !== [] && $milestones[array_key_last($milestones)]['text'] === $text) {
                continue;
            }

            $milestones[] = ['date' => self::date($event), 'text' => $text];
        }

        return array_values(array_slice($milestones, -self::MAX_MILESTONES));
    }

    private static function date(?TrackingEvent $event): ?string
    {
        return $event?->at?->setTimezone('Europe/Berlin')->format('d.m.Y');
    }
}

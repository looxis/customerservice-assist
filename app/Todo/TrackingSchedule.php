<?php

namespace App\Todo;

use App\Models\TicketTodo;
use Carbon\CarbonImmutable;

/**
 * When the automatic check for the tracking number of a ToDo is due (PROJ-77). Fixed times, Monday to Saturday
 * (Berlin time): a ToDo created before 14:00 is checked at 16:07 and 18:34 that day, one created before 16:00 at
 * 18:34, a later one only the next day. From the next day on it is 11:26 and 16:16. After five days (Sundays do
 * not count) the check ends.
 */
final class TrackingSchedule
{
    public const string ZONE = 'Europe/Berlin';

    /** The times of the scheduled runs. */
    public const array SLOTS = ['11:26', '16:07', '16:16', '18:34'];

    /** The times used on the day after the ToDo was created and later. */
    public const array LATER_SLOTS = ['11:26', '16:16'];

    public const int MAX_DAYS = 5;

    /** A run may start a little late; a slot counts for this many minutes. */
    public const int TOLERANCE_MINUTES = 20;

    /**
     * The slot a run at this time belongs to, or null when it is no slot time (or Sunday).
     */
    public static function slotAt(CarbonImmutable $now): ?string
    {
        $now = $now->setTimezone(self::ZONE);

        if ($now->isSunday()) {
            return null;
        }

        foreach (array_reverse(self::SLOTS) as $slot) {
            $start = $now->setTimeFromTimeString($slot);

            if ($now >= $start && $start->diffInMinutes($now) <= self::TOLERANCE_MINUTES) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Whether the ToDo is checked at this slot on the day of $now.
     */
    public static function isDue(TicketTodo $todo, CarbonImmutable $now, string $slot): bool
    {
        return in_array($slot, self::slotsOn($todo, $now), true);
    }

    /**
     * Whether the five days are over (the check ends).
     */
    public static function expired(TicketTodo $todo, CarbonImmutable $now): bool
    {
        $day = $now->setTimezone(self::ZONE)->startOfDay();

        return $day > self::created($todo)->startOfDay() && self::dayNumber($todo, $day) > self::MAX_DAYS;
    }

    /**
     * The next slot after $now ("heute 18:34", "morgen 11:26", "Sa 16:16"), or null when none follows.
     */
    public static function label(TicketTodo $todo, ?CarbonImmutable $now = null): ?string
    {
        $now = ($now ?? CarbonImmutable::now())->setTimezone(self::ZONE);

        for ($offset = 0; $offset <= 8; $offset++) {
            $day = $now->startOfDay()->addDays($offset);

            if ($day > self::created($todo)->startOfDay() && self::dayNumber($todo, $day) > self::MAX_DAYS) {
                return null;
            }

            foreach (self::slotsOn($todo, $day) as $slot) {
                if ($day->setTimeFromTimeString($slot) > $now) {
                    return match ($offset) {
                        0 => "heute {$slot}",
                        1 => "morgen {$slot}",
                        default => $day->locale('de')->isoFormat('dd').". {$slot}",
                    };
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function slotsOn(TicketTodo $todo, CarbonImmutable $day): array
    {
        $day = $day->setTimezone(self::ZONE);
        $created = self::created($todo);

        if ($day->isSunday() || $day->startOfDay() < $created->startOfDay()) {
            return [];
        }

        if ($day->startOfDay()->equalTo($created->startOfDay())) {
            $slots = [];

            if ($created->format('H:i') < '14:00') {
                $slots[] = '16:07';
            }

            if ($created->format('H:i') < '16:00') {
                $slots[] = '18:34';
            }

            return $slots;
        }

        return self::dayNumber($todo, $day) > self::MAX_DAYS ? [] : self::LATER_SLOTS;
    }

    /**
     * Which check day it is: the first day after the creation (Sundays skipped) is day 1.
     */
    private static function dayNumber(TicketTodo $todo, CarbonImmutable $day): int
    {
        $number = 0;

        for ($date = self::created($todo)->startOfDay()->addDay(); $date <= $day->startOfDay(); $date = $date->addDay()) {
            if (! $date->isSunday()) {
                $number++;
            }
        }

        return $number;
    }

    private static function created(TicketTodo $todo): CarbonImmutable
    {
        return CarbonImmutable::instance($todo->created_at)->setTimezone(self::ZONE);
    }
}

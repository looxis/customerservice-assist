<?php

namespace App\Todo;

/**
 * Where a ticket ToDo stands (PROJ-70). "Benachrichtigung offen": the work is done, the customer still has to be told.
 */
enum TodoStatus: string
{
    case Open = 'open';
    case NotifyPending = 'notify_pending';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::NotifyPending => 'Benachrichtigung offen',
            self::Done => 'Erledigt',
        };
    }
}

<?php

namespace App\Todo;

/**
 * Whether a ToDo can be worked on now or waits for an event, for example the shipment of a replacement order (PROJ-70).
 */
enum TodoKind: string
{
    case Direct = 'direct';
    case Waiting = 'waiting';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Direkt bearbeitbar',
            self::Waiting => 'Wartet auf Ereignis',
        };
    }
}

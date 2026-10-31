<?php

namespace App\Zammad;

/**
 * Whether a tracked ticket could last be read from Zammad (PROJ-66). No status
 * ever removes a row: only tickets:purge deletes, after its retention periods.
 */
enum SyncStatus: string
{
    case Synced = 'synced';
    case NotFound = 'not_found';
    case AccessDenied = 'access_denied';
    case SyncFailed = 'sync_failed';

    /**
     * @return list<self> The tickets hidden from the working list.
     */
    public static function unreadable(): array
    {
        return [self::NotFound, self::AccessDenied];
    }
}

<?php

namespace App\Tracking;

use RuntimeException;

/**
 * The carrier could not be asked (PROJ-82). A temporary problem (no connection, rate limit, server error) is worth a
 * new try; a missing or refused access is not.
 */
class TrackingException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $temporary = true)
    {
        parent::__construct($message);
    }
}

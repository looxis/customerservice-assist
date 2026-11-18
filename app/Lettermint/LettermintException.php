<?php

namespace App\Lettermint;

use RuntimeException;

/**
 * Lettermint could not be asked (PROJ-97). A temporary problem (no connection, rate limit, server error) is worth a
 * new try; a missing or refused access is not.
 */
class LettermintException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $temporary = true)
    {
        parent::__construct($message);
    }
}

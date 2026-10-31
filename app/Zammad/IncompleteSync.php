<?php

namespace App\Zammad;

use RuntimeException;

/**
 * A sync run could not cover everything it had to; the progress marker stays where it is.
 */
final class IncompleteSync extends RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct("Zammad sync incomplete: {$errorCode}");
    }
}

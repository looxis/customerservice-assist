<?php

namespace App\Zammad;

use RuntimeException;

final class ZammadException extends RuntimeException
{
    public function __construct(public readonly ZammadProblem $problem, public readonly ?int $httpStatus = null)
    {
        parent::__construct("Zammad request failed: {$problem->value}".($httpStatus ? " (HTTP {$httpStatus})" : ''));
    }
}

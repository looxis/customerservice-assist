<?php

namespace App\Eocs;

use RuntimeException;

final class EocsException extends RuntimeException
{
    public function __construct(public readonly EocsProblem $problem, public readonly ?int $httpStatus = null)
    {
        parent::__construct("EOCS request failed: {$problem->value}".($httpStatus ? " (HTTP {$httpStatus})" : ''));
    }
}

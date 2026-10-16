<?php

namespace App\ProductForge;

use RuntimeException;

final class ProductForgeException extends RuntimeException
{
    public function __construct(public readonly ProductForgeProblem $problem, public readonly ?int $httpStatus = null)
    {
        parent::__construct("ProductForge request failed: {$problem->value}".($httpStatus ? " (HTTP {$httpStatus})" : ''));
    }
}

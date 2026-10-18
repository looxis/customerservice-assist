<?php

namespace App\Zammad;

use RuntimeException;

class ReplyException extends RuntimeException
{
    public function __construct(public readonly ReplyProblem $problem, public readonly ?string $detail = null)
    {
        parent::__construct($problem->value);
    }
}

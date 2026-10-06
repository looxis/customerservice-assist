<?php

namespace App\Analysis;

use RuntimeException;

final class AnalysisException extends RuntimeException
{
    public function __construct(public readonly AnalysisProblem $problem, ?string $detail = null)
    {
        parent::__construct("Analysis failed: {$problem->value}".($detail ? " ({$detail})" : ''));
    }
}

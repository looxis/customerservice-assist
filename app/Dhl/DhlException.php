<?php

namespace App\Dhl;

use RuntimeException;

class DhlException extends RuntimeException
{
    /**
     * @param  ?string  $detail  DHL's own wording of a rejection, shortened; meant for the employee, never logged.
     * @param  ?string  $shipmentNo  Set when DHL created the label although its file is unusable.
     */
    public function __construct(
        public readonly DhlProblem $problem,
        public readonly ?int $httpStatus = null,
        public readonly ?string $detail = null,
        public readonly ?string $shipmentNo = null,
    ) {
        parent::__construct("DHL request failed: {$problem->value}".($httpStatus === null ? '' : " (HTTP {$httpStatus})"));
    }
}

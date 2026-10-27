<?php

namespace App\Dhl;

/**
 * A label DHL created (PROJ-64): the return's shipment number and the unchanged PDF.
 */
final readonly class ReturnLabelResult
{
    public function __construct(public string $shipmentNo, public string $pdf) {}
}

<?php

namespace App\Dhl;

/**
 * The customer as the sender of a return (PROJ-64). The e-mail address is deliberately not part of it:
 * DHL never mails the label, the reply of the employee does.
 */
final readonly class ReturnRequest
{
    public function __construct(
        public string $name,
        public string $street,
        public string $houseNumber,
        public string $postalCode,
        public string $city,
        public string $customerReference,
        public ?string $shipmentReference = null,
    ) {}
}

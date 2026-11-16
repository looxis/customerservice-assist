<?php

namespace App\Dhl;

use App\Returns\ReturnCountry;

/**
 * The customer as the sender of a return (PROJ-64). The e-mail address is deliberately not part of it:
 * DHL never mails the label, the reply of the employee does. The country is not a field of the address at DHL:
 * it decides which return receiver the label is ordered for.
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
        public ReturnCountry $country = ReturnCountry::Germany,
    ) {}
}

<?php

namespace App\Returns;

/**
 * What the employee confirmed for a return label (PROJ-64): the order, the optional ticket and the address of the sender.
 */
final readonly class ReturnLabelInput
{
    public function __construct(
        public ?string $ticketNumber,
        public string $orderNumber,
        public int $eocsOrderId,
        public string $name,
        public string $street,
        public string $houseNumber,
        public string $postalCode,
        public string $city,
        public ReturnCountry $country = ReturnCountry::Germany,
    ) {}

    /**
     * Identifies the same request again (same ticket, order and address) without keeping the address. The country
     * only counts abroad, so that German labels ordered before returns from abroad existed are still recognised.
     */
    public function fingerprint(): string
    {
        $parts = [$this->ticketNumber, $this->eocsOrderId, $this->name, $this->street, $this->houseNumber, $this->postalCode, $this->city];

        return hash('sha256', mb_strtolower(implode('|', $this->country->isDomestic() ? $parts : [...$parts, $this->country->value])));
    }

    /**
     * The original order number: claim orders (R1-…) carry the number of the order the goods belong to.
     */
    public function originalOrderNumber(): string
    {
        return (string) preg_replace('/^R\d+-/i', '', $this->orderNumber);
    }

    /**
     * Printed on the label (DHL prints the first 30 characters): order and ticket number when both fit.
     */
    public function customerReference(): string
    {
        $order = $this->originalOrderNumber();
        $with = $this->ticketNumber === null ? $order : $order.' T'.$this->ticketNumber;

        return mb_strlen($with) <= 30 ? $with : $order;
    }

    public function shipmentReference(): ?string
    {
        return $this->ticketNumber === null ? null : 'Ticket '.$this->ticketNumber;
    }
}

<?php

namespace App\Eocs;

/**
 * The delivery address of an order in separate fields (PROJ-64), as the return label needs it. Like the lines of
 * EocsOrder::$deliveryAddress it is never shown in the ticket header, never logged and never sent to the language
 * model; it only prefills the form of the return label, where the employee checks it.
 */
final readonly class EocsShippingAddress
{
    public function __construct(
        public string $company,
        public string $firstName,
        public string $lastName,
        public string $street,
        public string $streetNumber,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {}

    /**
     * Null when EOCS has no address (for example Amazon orders).
     *
     * @param  array<string, mixed>  $shipping
     */
    public static function fromShipping(array $shipping): ?self
    {
        $text = fn (string $key): string => trim((string) ($shipping[$key] ?? ''));

        $address = new self($text('company'), $text('first_name'), $text('last_name'), $text('street'), $text('street_no'), $text('zipcode'), $text('city'), strtoupper($text('country_code')));

        return $address->street === '' && $address->city === '' && $address->postalCode === '' ? null : $address;
    }

    /**
     * Person's name, or the company when there is no person.
     */
    public function name(): string
    {
        $person = trim($this->firstName.' '.$this->lastName);

        return $person !== '' ? $person : $this->company;
    }
}

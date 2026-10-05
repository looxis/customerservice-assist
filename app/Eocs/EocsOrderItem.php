<?php

namespace App\Eocs;

/**
 * One position of an order. Loaded for the product suggestion (PROJ-4) and
 * the analysis (PROJ-9); not shown in the ticket header yet.
 */
final readonly class EocsOrderItem
{
    /**
     * @param  array<string, mixed>  $customization  Personalisation data as EOCS delivers it (e.g. Amazon's customizationData).
     */
    public function __construct(
        public ?string $itemNumber,
        public string $name,
        public ?string $productType,
        public int $quantity,
        public ?string $status,
        public ?string $configurationCode,
        public ?string $configurationUrl,
        public ?string $asin,
        public array $customization = [],
    ) {}
}

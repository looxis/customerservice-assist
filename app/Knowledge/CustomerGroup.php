<?php

namespace App\Knowledge;

/**
 * One of the customer groups an employee picks for a case. The only place
 * that decides whether a document's customer type and channel fit a case.
 */
final readonly class CustomerGroup
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $customerType,
        public ?string $salesChannel,
    ) {}

    /**
     * All groups from the "knowledge.customer_groups" configuration, in its order.
     *
     * @param  array<string, array{label: string, customer_type: ?string, sales_channel: ?string}>  $groups
     * @return list<self>
     */
    public static function all(array $groups): array
    {
        $result = [];

        foreach ($groups as $key => $group) {
            $result[] = new self($key, $group['label'], $group['customer_type'] ?? null, $group['sales_channel'] ?? null);
        }

        return $result;
    }

    public function isUnclear(): bool
    {
        return $this->customerType === null && $this->salesChannel === null;
    }

    public function covers(KnowledgeDocument $document): bool
    {
        return $this->exclusionReason($document) === null;
    }

    /**
     * Why the document does not apply to this group, or null when it does.
     * Customer type is checked before channel, so the reason is always the same.
     */
    public function exclusionReason(KnowledgeDocument $document): ?string
    {
        $customerTypes = $document->customerTypes();
        $salesChannels = $document->salesChannels();

        if ($customerTypes !== [] && ! in_array($this->customerType, $customerTypes, true)) {
            return 'nur für Kundenart '.implode(', ', $customerTypes);
        }

        if ($salesChannels !== [] && ! in_array($this->salesChannel, $salesChannels, true)) {
            return 'nur für Kanal '.implode(', ', $salesChannels);
        }

        return null;
    }

    /**
     * Why a covered document applies, e.g. "Kundenart b2c, Kanal shop"; null when it applies to everyone.
     */
    public function inclusionReason(KnowledgeDocument $document): ?string
    {
        $parts = [];

        if ($document->customerTypes() !== []) {
            $parts[] = "Kundenart {$this->customerType}";
        }

        if ($document->salesChannels() !== []) {
            $parts[] = "Kanal {$this->salesChannel}";
        }

        return $parts === [] ? null : implode(', ', $parts);
    }
}

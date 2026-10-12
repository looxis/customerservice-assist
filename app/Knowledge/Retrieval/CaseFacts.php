<?php

namespace App\Knowledge\Retrieval;

/**
 * Everything the retrieval knows about one case, collected once per analysis
 * and used unchanged for the retrieval, the log and a repeated pass (PROJ-36).
 */
final readonly class CaseFacts
{
    public const string REFERENCE_NAMED = 'named';

    public const string REFERENCE_SINGLE = 'single';

    public const string REFERENCE_UNCLEAR = 'unclear';

    public const string REFERENCE_NONE = 'none';

    /**
     * @param  array<string, mixed>  $values  Case-level facts by `when` key; null is unknown.
     * @param  list<OrderFacts>  $orders  The orders the concern may refer to (the reference order, or the candidates when it is unclear).
     * @param  string  $reference  How the orders were chosen: named, single, unclear or none.
     * @param  array{A: list<string>, B: list<string>, C: list<string>}  $tiers  Customer texts: A = the current concern, B = recent messages and the summary, C = the rest.
     */
    public function __construct(
        public array $values,
        public array $orders,
        public string $reference,
        public array $tiers,
    ) {}

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function orderReferenceIsUnclear(): bool
    {
        return $this->reference === self::REFERENCE_UNCLEAR;
    }
}

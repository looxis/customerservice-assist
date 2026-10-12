<?php

namespace App\Knowledge\Retrieval;

/**
 * The outcome of one `when` condition for a case, with what it was based on,
 * for the log of the analysis (PROJ-36).
 */
final readonly class Evaluation
{
    /**
     * @param  string|null  $order  The order the result is based on; null for conditions on the case only.
     * @param  bool  $orderUnclear  Several orders were candidates because the concern names none.
     * @param  list<array{key: string, fact: string, result: string, order: string|null}>  $checks  Every checked key with the fact found and its result.
     */
    public function __construct(
        public Tri $result,
        public ?string $order,
        public bool $orderUnclear,
        public array $checks,
    ) {}

    /**
     * The keys whose fact was unknown, for the note "Voraussetzung ungeklärt".
     *
     * @return list<string>
     */
    public function unknownKeys(): array
    {
        return array_values(array_unique(array_map(
            fn (array $check): string => $check['key'],
            array_filter($this->checks, fn (array $check): bool => $check['result'] === Tri::Unknown->value),
        )));
    }
}

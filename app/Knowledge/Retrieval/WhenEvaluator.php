<?php

namespace App\Knowledge\Retrieval;

/**
 * Evaluates a `when` condition against the facts of a case (PROJ-36) in
 * three-valued logic. A condition that uses order facts is evaluated as a
 * whole for each candidate order, so facts of different orders are never
 * mixed; it holds if one candidate satisfies all of it, is unknown if none
 * does but one is unknown, and fails if every candidate fails.
 */
class WhenEvaluator
{
    /**
     * @param  array<string, array<string, mixed>>  $keys  The "knowledge.retrieval.when_keys" configuration.
     */
    public function __construct(private readonly array $keys) {}

    /**
     * @param  array<string, mixed>  $when
     */
    public function evaluate(array $when, CaseFacts $facts): Evaluation
    {
        if (! $this->usesOrderFacts($when)) {
            $checks = [];

            return new Evaluation($this->condition($when, $facts, OrderFacts::unknown(), $checks), null, false, $checks);
        }

        $candidates = $facts->orders === [] ? [OrderFacts::unknown()] : $facts->orders;
        $outcomes = [];

        foreach ($candidates as $order) {
            $checks = [];
            $outcomes[] = [$this->condition($when, $facts, $order, $checks), $order, $checks];
        }

        foreach ([Tri::Yes, Tri::Unknown, Tri::No] as $wanted) {
            foreach ($outcomes as [$result, $order, $checks]) {
                if ($result === $wanted) {
                    return new Evaluation($result, $order->number, $facts->orderReferenceIsUnclear(), $checks);
                }
            }
        }

        return new Evaluation(Tri::No, null, false, []);
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  list<array{key: string, fact: string, result: string, order: string|null}>  $checks
     */
    private function condition(array $condition, CaseFacts $facts, OrderFacts $order, array &$checks): Tri
    {
        $results = [];

        foreach ($condition as $key => $value) {
            $key = (string) $key;

            if ($key === 'any_of') {
                $alternatives = [];

                foreach ((array) $value as $alternative) {
                    $alternatives[] = $this->condition((array) $alternative, $facts, $order, $checks);
                }

                $results[] = Tri::any($alternatives);
            } elseif ($key === 'not') {
                $results[] = $this->condition((array) $value, $facts, $order, $checks)->not();
            } else {
                $results[] = $this->leaf($key, $value, $facts, $order, $checks);
            }
        }

        return Tri::all($results);
    }

    /**
     * @param  list<array{key: string, fact: string, result: string, order: string|null}>  $checks
     */
    private function leaf(string $key, mixed $expected, CaseFacts $facts, OrderFacts $order, array &$checks): Tri
    {
        $definition = $this->keys[$key] ?? ['level' => 'case', 'type' => 'bool'];
        $isOrder = $definition['level'] === 'order';
        $fact = $isOrder ? $order->get($key) : $facts->get($key);

        $result = match ($definition['type']) {
            'bool' => $fact === null ? Tri::Unknown : Tri::fromBool($fact === $expected),
            'number' => $this->number($fact, $expected),
            'slugs' => Tri::fromBool(array_intersect((array) $fact, (array) $expected) !== []),
            default => $fact === null ? Tri::Unknown : Tri::fromBool(in_array($fact, (array) $expected, true)),
        };

        $checks[] = [
            'key' => $key,
            'fact' => match (true) {
                $fact === null => 'unbekannt',
                is_bool($fact) => $fact ? 'ja' : 'nein',
                is_array($fact) => $fact === [] ? 'keine' : implode(', ', $fact),
                default => (string) $fact,
            },
            'result' => $result->value,
            'order' => $isOrder ? $order->number : null,
        ];

        return $result;
    }

    private function number(mixed $fact, mixed $expected): Tri
    {
        if ($fact === null) {
            return Tri::Unknown;
        }

        if (! is_array($expected)) {
            return Tri::fromBool($fact == $expected);
        }

        return Tri::fromBool((! isset($expected['min']) || $fact >= $expected['min']) && (! isset($expected['max']) || $fact <= $expected['max']));
    }

    /**
     * @param  array<array-key, mixed>  $condition
     */
    private function usesOrderFacts(array $condition): bool
    {
        foreach ($condition as $key => $value) {
            if ($key === 'any_of' && is_array($value)) {
                foreach ($value as $alternative) {
                    if (is_array($alternative) && $this->usesOrderFacts($alternative)) {
                        return true;
                    }
                }
            } elseif ($key === 'not') {
                if (is_array($value) && $this->usesOrderFacts($value)) {
                    return true;
                }
            } elseif (($this->keys[$key]['level'] ?? null) === 'order') {
                return true;
            }
        }

        return false;
    }
}

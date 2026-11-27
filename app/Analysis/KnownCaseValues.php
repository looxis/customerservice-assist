<?php

namespace App\Analysis;

use App\Eocs\EocsOrder;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\Retrieval\CaseFacts;
use App\Knowledge\Retrieval\CaseFactsBuilder;
use App\Zammad\Ticket;

/**
 * What the app already knows about a case, for the check questions of the guided fact-finding (PROJ-103). Built from
 * the same case facts the knowledge retrieval uses (`when` keys), nothing is fetched for it.
 *
 * A value is "sure" only when it is known and belongs to the level the question means: a ticket value (attachment in
 * the current message) needs no order; an order value counts only when exactly one order is the reference of the
 * concern and its values do not contradict each other. Everything else is shown as a hint, the question stays.
 */
final class KnownCaseValues
{
    public function __construct(
        private readonly CaseFactsBuilder $builder,
        private readonly KnowledgeSelector $selector,
    ) {}

    /**
     * @param  list<EocsOrder>  $orders
     * @param  list<array{number: string, channel: string|null, items: list<string>}>  $mentioned
     * @return array<string, array{value: bool|int|string|null, sure: bool, level: string, type: string, source: string, hint: string}>
     */
    public function build(Ticket $ticket, array $orders, array $mentioned = []): array
    {
        $unclear = collect($this->selector->selectableGroups())->first(fn (CustomerGroup $group): bool => $group->isUnclear());

        return $this->from($this->builder->build($ticket, $unclear, [], $orders, $mentioned));
    }

    /**
     * @return array<string, array{value: bool|int|string|null, sure: bool, level: string, type: string, source: string, hint: string}>
     */
    public function from(CaseFacts $facts): array
    {
        $known = [];
        $single = count($facts->orders) === 1 && in_array($facts->reference, [CaseFacts::REFERENCE_NAMED, CaseFacts::REFERENCE_SINGLE], true);
        $contradicted = $single && $this->contradicts($facts->orders[0]->values);

        foreach ((array) config('knowledge.retrieval.when_keys') as $key => $definition) {
            if (! in_array($definition['type'], ['bool', 'number'], true)) {
                continue;
            }

            $entry = ['level' => $definition['level'], 'type' => $definition['type'], 'source' => (string) $definition['source']];

            if ($definition['level'] === 'case') {
                $value = $facts->get($key);
                $known[$key] = [...$entry, 'value' => $value, 'sure' => $value !== null, 'hint' => $value === null ? 'liegt nicht vor' : ''];

                continue;
            }

            if ($facts->orders === []) {
                $known[$key] = [...$entry, 'value' => null, 'sure' => false, 'hint' => 'keine Bestellung geladen'];

                continue;
            }

            if ($single) {
                $value = $facts->orders[0]->get($key);
                $known[$key] = [...$entry, 'value' => $value, 'sure' => $value !== null && ! $contradicted, 'hint' => match (true) {
                    $value === null => 'liegt für Bestellung '.$facts->orders[0]->number.' nicht vor',
                    $contradicted => 'widersprüchliche Angaben zu Bestellung '.$facts->orders[0]->number,
                    default => '',
                }];

                continue;
            }

            $parts = array_map(fn ($order): string => $order->number.': '.self::display($order->get($key)), $facts->orders);
            $known[$key] = [...$entry, 'value' => null, 'sure' => false, 'hint' => 'mehrere Bestellungen, Bezug unklar – '.implode(', ', $parts)];
        }

        return $known;
    }

    /**
     * How a value reads for the employee and the model.
     */
    public static function display(mixed $value): string
    {
        return match (true) {
            $value === true => 'ja',
            $value === false => 'nein',
            $value === null => 'unbekannt',
            default => (string) $value,
        };
    }

    /**
     * Values of one order that cannot all be true: delivered without being shipped, or every shipment delivered
     * without a delivery.
     *
     * @param  array<string, mixed>  $values
     */
    private function contradicts(array $values): bool
    {
        return (($values['delivery_confirmed'] ?? null) === true && ($values['shipped'] ?? null) === false)
            || (($values['all_shipments_delivered'] ?? null) === true && ($values['delivery_confirmed'] ?? null) === false);
    }
}

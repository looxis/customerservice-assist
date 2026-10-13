<?php

namespace App\Knowledge\Retrieval\Router;

use App\Knowledge\CaseContext;

/**
 * Keeps the pool within the configured size (PROJ-37). Up to the limit the router sees every line.
 * Above it, two things decide, neither of which needs a trigger hit:
 *  - places reserved for documents with a specific scope, handed out in turn to documents narrowed to the chosen
 *    product and to documents narrowed to the customer group (a document need not be both);
 *    places that are not needed fall back to the ranking by similarity,
 *  - a plain word-start similarity between the customer text and the document's title, summary, topics and triggers.
 */
class CandidatePreselector
{
    private const int STEM = 5;

    private const int MIN_WORD = 4;

    /**
     * @param  list<PoolEntry>  $pool
     * @param  array{A: list<string>, B: list<string>, C: list<string>}  $tiers
     * @return array{selected: list<PoolEntry>, left_out: list<string>, preselected: bool}
     */
    public function select(array $pool, array $tiers, CaseContext $context): array
    {
        $maxLines = (int) config('knowledge.router.pool.max_lines');
        $maxCharacters = (int) config('knowledge.router.pool.max_characters');
        $total = array_sum(array_map(fn (PoolEntry $entry): int => mb_strlen($entry->line()) + 1, $pool));

        if (count($pool) <= $maxLines && $total <= $maxCharacters) {
            return ['selected' => $pool, 'left_out' => [], 'preselected' => false];
        }

        $query = $this->query($tiers);
        $scored = [];

        foreach ($pool as $entry) {
            $scored[$entry->id()] = ['entry' => $entry, 'score' => $this->score($entry, $query)];
        }

        $order = fn (array $items): array => $this->sorted($items);
        $reserved = (int) floor($maxLines * (float) config('knowledge.router.pool.reserved_share'));
        $byProduct = $order(array_filter($scored, fn (array $item): bool => $this->productSpecific($item['entry'], $context)));
        $byGroup = $order(array_filter($scored, fn (array $item): bool => $this->groupSpecific($item['entry'], $context)));
        $chosen = [];
        $characters = 0;
        $take = function (array $item) use (&$chosen, &$characters, $maxLines, $maxCharacters): bool {
            $id = $item['entry']->id();
            $length = mb_strlen($item['entry']->line()) + 1;

            if (isset($chosen[$id]) || count($chosen) >= $maxLines || $characters + $length > $maxCharacters) {
                return false;
            }

            $chosen[$id] = $item['entry'];
            $characters += $length;

            return true;
        };

        $lists = [$byProduct, $byGroup];
        $turn = 0;

        while (count($chosen) < $reserved && ($lists[0] !== [] || $lists[1] !== [])) {
            $index = $turn % 2;
            $turn++;

            if ($lists[$index] === []) {
                $index = 1 - $index;
            }

            $take(array_shift($lists[$index]));
        }

        foreach ($order($scored) as $item) {
            $take($item);
        }

        $selected = array_values(array_filter($pool, fn (PoolEntry $entry): bool => isset($chosen[$entry->id()])));

        return [
            'selected' => $selected,
            'left_out' => array_values(array_map(fn (PoolEntry $entry): string => $entry->id(), array_filter($pool, fn (PoolEntry $entry): bool => ! isset($chosen[$entry->id()])))),
            'preselected' => true,
        ];
    }

    /**
     * A document narrowed to products of which the chosen ones include one.
     */
    public function productSpecific(PoolEntry $entry, CaseContext $context): bool
    {
        return $entry->document->products() !== [] && array_intersect($entry->document->products(), $context->products) !== [];
    }

    /**
     * A document narrowed to customer types or channels, which the chosen customer group is in.
     */
    public function groupSpecific(PoolEntry $entry, CaseContext $context): bool
    {
        return ! $context->customerGroup->isUnclear()
            && ($entry->document->customerTypes() !== [] || $entry->document->salesChannels() !== []);
    }

    /**
     * @param  array<string, array{entry: PoolEntry, score: float}>  $items
     * @return list<array{entry: PoolEntry, score: float}>
     */
    private function sorted(array $items): array
    {
        $items = array_values($items);
        usort($items, fn (array $a, array $b): int => [$b['score'], $a['entry']->id()] <=> [$a['score'], $b['entry']->id()]);

        return $items;
    }

    /**
     * @param  array{A: list<string>, B: list<string>, C: list<string>}  $tiers
     * @return array<string, float> Word stem => weight.
     */
    private function query(array $tiers): array
    {
        $query = [];

        foreach (['A' => 3.0, 'B' => 1.0] as $tier => $weight) {
            foreach ($tiers[$tier] as $text) {
                foreach ($this->stems($text) as $stem) {
                    $query[$stem] = max($query[$stem] ?? 0.0, $weight);
                }
            }
        }

        return $query;
    }

    /**
     * @param  array<string, float>  $query
     */
    private function score(PoolEntry $entry, array $query): float
    {
        $document = $entry->document;
        $stems = array_unique($this->stems(implode(' ', [$document->title, (string) $document->summary(), implode(' ', $document->topics()), implode(' ', $document->triggers())])));

        if ($stems === []) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($stems as $stem) {
            $sum += $query[$stem] ?? 0.0;
        }

        return $sum / (1 + log(1 + count($stems)));
    }

    /**
     * @return list<string>
     */
    private function stems(string $text): array
    {
        preg_match_all('/\p{L}{'.self::MIN_WORD.',}/u', mb_strtolower($text), $matches);

        return array_values(array_unique(array_map(fn (string $word): string => mb_substr($word, 0, self::STEM), $matches[0])));
    }
}

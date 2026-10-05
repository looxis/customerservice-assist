<?php

namespace App\Knowledge;

use Illuminate\Support\Collection;

/**
 * Picks the knowledge for a case deterministically by customer group and
 * product. Reads only usable documents from the library and changes nothing.
 */
class KnowledgeSelector
{
    private const array EXAMPLE_TYPES = ['example-good', 'example-bad'];

    /**
     * @param  array<string, mixed>  $config  The "knowledge" configuration.
     */
    public function __construct(
        private readonly KnowledgeLibrary $library,
        private readonly array $config,
    ) {}

    /**
     * The customer groups for the form, in configured order.
     *
     * @return list<CustomerGroup>
     */
    public function customerGroups(): array
    {
        return CustomerGroup::all($this->config['customer_groups']);
    }

    public function customerGroup(string $key): ?CustomerGroup
    {
        foreach ($this->customerGroups() as $group) {
            if ($group->key === $key) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Products with at least one usable product file, sorted by title.
     *
     * @return list<array{slug: string, title: string}>
     */
    public function products(): array
    {
        return $this->library->usable()
            ->filter(fn (KnowledgeDocument $document): bool => $document->productSlug() !== null)
            ->groupBy(fn (KnowledgeDocument $document): string => $document->productSlug())
            ->map(fn (Collection $files, string $slug): array => ['slug' => $slug, 'title' => $this->productTitle($slug, $files)])
            ->sortBy(fn (array $product): string => $this->sortKey($product['title']).'|'.$product['slug'])
            ->values()
            ->all();
    }

    public function select(CaseContext $context): KnowledgeSelection
    {
        $knownProducts = $this->library->all()
            ->map(fn (KnowledgeDocument $document): ?string => $document->productSlug())
            ->filter()
            ->unique()
            ->values()
            ->all();
        $usableProducts = array_column($this->products(), 'slug');

        $unknownProducts = array_values(array_diff($context->products, $knownProducts));
        $productsWithoutKnowledge = array_values(array_diff($context->products, $unknownProducts, $usableProducts));
        $products = array_values(array_diff($context->products, $unknownProducts));

        $selected = [];
        $excluded = [];

        foreach ($this->ordered($this->library->usable()) as $document) {
            $reason = $context->customerGroup->exclusionReason($document) ?? $this->productExclusionReason($document, $products);

            if ($reason === null) {
                $selected[] = new KnowledgeSelectionEntry($document, $this->inclusionReason($document, $context->customerGroup, $products));
            } else {
                $excluded[] = new KnowledgeSelectionEntry($document, $reason);
            }
        }

        [$selected, $capped] = $this->capExamples($selected, $products);
        [$selected, $dropped, $warnings] = $this->limitLength($selected);

        return new KnowledgeSelection(
            context: $context,
            state: $this->library->state(),
            selected: $selected,
            excluded: $this->sortEntries([...$excluded, ...$capped, ...$dropped]),
            unknownProducts: $unknownProducts,
            productsWithoutKnowledge: $productsWithoutKnowledge,
            characterLimit: $this->config['selection']['max_characters'],
            warnings: [...$this->contextWarnings($selected, $unknownProducts, $productsWithoutKnowledge), ...$warnings],
        );
    }

    /**
     * @param  list<string>  $products
     */
    private function productExclusionReason(KnowledgeDocument $document, array $products): ?string
    {
        $bound = $document->boundProducts();

        if ($bound === [] || array_intersect($bound, $products) !== []) {
            return null;
        }

        return ($products === [] ? 'nur für Produkt ' : 'anderes Produkt: ').implode(', ', $bound);
    }

    /**
     * @param  list<string>  $products
     */
    private function inclusionReason(KnowledgeDocument $document, CustomerGroup $group, array $products): string
    {
        $matching = array_values(array_intersect($document->boundProducts(), $products));
        $productReason = match (true) {
            $matching === [] => null,
            $document->productSlug() !== null => 'Produktwissen '.implode(', ', $matching),
            default => 'Produkt '.implode(', ', $matching),
        };

        $parts = array_filter([$group->inclusionReason($document), $productReason]);

        return $parts === [] ? 'gilt für alle' : implode(', ', $parts);
    }

    /**
     * Keep the configured number of good and bad reference cases, preferring
     * those bound to a chosen product, then by ID.
     *
     * @param  list<KnowledgeSelectionEntry>  $entries
     * @param  list<string>  $products
     * @return array{0: list<KnowledgeSelectionEntry>, 1: list<KnowledgeSelectionEntry>}
     */
    private function capExamples(array $entries, array $products): array
    {
        $limits = [
            'example-good' => ['max' => $this->config['selection']['max_good_examples'], 'label' => 'gute Beispiele'],
            'example-bad' => ['max' => $this->config['selection']['max_bad_examples'], 'label' => 'schlechte Beispiele'],
        ];
        $capped = [];

        foreach ($limits as $type => $limit) {
            $examples = array_filter($entries, fn (KnowledgeSelectionEntry $entry): bool => $entry->document->type === $type);

            usort($examples, fn (KnowledgeSelectionEntry $a, KnowledgeSelectionEntry $b): int => [$this->productMatch($b->document, $products), $a->document->id] <=> [$this->productMatch($a->document, $products), $b->document->id]);

            foreach (array_slice($examples, $limit['max']) as $entry) {
                $capped[] = new KnowledgeSelectionEntry($entry->document, "Obergrenze für {$limit['label']} erreicht ({$limit['max']})");
            }
        }

        $cappedIds = array_map(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id, $capped);

        return [
            array_values(array_filter($entries, fn (KnowledgeSelectionEntry $entry): bool => ! in_array($entry->document->id, $cappedIds, true))),
            $capped,
        ];
    }

    /**
     * Drop reference cases from the end until the text fits the character
     * limit. Binding knowledge is never dropped; the warning says so.
     *
     * @param  list<KnowledgeSelectionEntry>  $entries
     * @return array{0: list<KnowledgeSelectionEntry>, 1: list<KnowledgeSelectionEntry>, 2: list<string>}
     */
    private function limitLength(array $entries): array
    {
        $limit = $this->config['selection']['max_characters'];
        $length = $this->length($entries);

        if ($length <= $limit) {
            return [$entries, [], []];
        }

        $dropped = [];

        for ($index = count($entries) - 1; $index >= 0 && $this->length($entries) > $limit; $index--) {
            if (in_array($entries[$index]->document->type, self::EXAMPLE_TYPES, true)) {
                $dropped[] = new KnowledgeSelectionEntry($entries[$index]->document, 'weggelassen, weil der Gesamtumfang die Obergrenze überschreitet');
                unset($entries[$index]);
            }
        }

        $entries = array_values($entries);
        $remaining = $this->length($entries);
        $warnings = [];

        if ($dropped !== []) {
            $warnings[] = 'Der Umfang von '.$this->number($length).' Zeichen überschreitet die Obergrenze von '.$this->number($limit).' Zeichen. '.(count($dropped) === 1 ? 'Ein Referenzfall wurde' : count($dropped).' Referenzfälle wurden').' weggelassen.';
        }

        if ($remaining > $limit) {
            $warnings[] = 'Der Umfang liegt mit '.$this->number($remaining).' Zeichen über der Obergrenze von '.$this->number($limit).' Zeichen. Verbindliches Wissen wird nicht gekürzt.';
        }

        return [$entries, array_reverse($dropped), $warnings];
    }

    /**
     * @param  list<KnowledgeSelectionEntry>  $selected
     * @param  list<string>  $unknownProducts
     * @param  list<string>  $productsWithoutKnowledge
     * @return list<string>
     */
    private function contextWarnings(array $selected, array $unknownProducts, array $productsWithoutKnowledge): array
    {
        $warnings = $this->library->issues()
            ->filter(fn (KnowledgeIssue $issue): bool => $issue->path === '.')
            ->map(fn (KnowledgeIssue $issue): string => $issue->message)
            ->values()
            ->all();

        foreach ($unknownProducts as $product) {
            $warnings[] = "Unbekanntes Produkt `{$product}`: Es gibt keine Produktdatei, das Produkt wird ignoriert.";
        }

        foreach ($productsWithoutKnowledge as $product) {
            $warnings[] = "Produkt `{$product}` ohne Produktwissen: Keine Produktdatei ist derzeit verwendbar.";
        }

        if ($selected === []) {
            $warnings[] = 'Kein Wissen für diesen Fall.';
        }

        return $warnings;
    }

    /**
     * @param  list<string>  $products
     */
    private function productMatch(KnowledgeDocument $document, array $products): bool
    {
        return array_intersect($document->boundProducts(), $products) !== [];
    }

    /**
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @return list<KnowledgeDocument>
     */
    private function ordered(Collection $documents): array
    {
        $rank = array_flip(array_keys($this->config['types']));

        return $documents
            ->sort(fn (KnowledgeDocument $a, KnowledgeDocument $b): int => [$rank[$a->type] ?? PHP_INT_MAX, $a->id] <=> [$rank[$b->type] ?? PHP_INT_MAX, $b->id])
            ->values()
            ->all();
    }

    /**
     * @param  list<KnowledgeSelectionEntry>  $entries
     * @return list<KnowledgeSelectionEntry>
     */
    private function sortEntries(array $entries): array
    {
        $documents = $this->ordered(collect($entries)->map(fn (KnowledgeSelectionEntry $entry): KnowledgeDocument => $entry->document));
        $byId = collect($entries)->keyBy(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id);

        return array_map(fn (KnowledgeDocument $document): KnowledgeSelectionEntry => $byId[$document->id], $documents);
    }

    /**
     * @param  list<KnowledgeSelectionEntry>  $entries
     */
    private function length(array $entries): int
    {
        return array_sum(array_map(fn (KnowledgeSelectionEntry $entry): int => $entry->document->length(), $entries));
    }

    /**
     * @param  Collection<int, KnowledgeDocument>  $files
     */
    private function productTitle(string $slug, Collection $files): string
    {
        $main = $files->first(fn (KnowledgeDocument $document): bool => pathinfo($document->path, PATHINFO_FILENAME) === $slug)
            ?? $files->sortBy(fn (KnowledgeDocument $document): string => (string) $document->id)->first();

        return $main->title ?? $slug;
    }

    /**
     * Alphabetical order as a German reader expects it: case-insensitive,
     * umlauts next to their base letter. Works without the intl extension.
     */
    private function sortKey(string $title): string
    {
        return strtr(mb_strtolower($title), ['ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss']);
    }

    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}

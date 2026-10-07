<?php

namespace App\Knowledge;

/**
 * Suggests customer group and products from order data. Suggestions only
 * prefill the form; the employee's choice always wins.
 */
class KnowledgeSuggester
{
    /**
     * @param  array<string, mixed>  $config  The "knowledge" configuration.
     */
    public function __construct(
        private readonly KnowledgeLibrary $library,
        private readonly KnowledgeSelector $selector,
        private readonly array $config,
    ) {}

    /**
     * Every product whose order keyword occurs in the article number or
     * description of an order line, ignoring case. Sorted by slug.
     *
     * @param  list<array{article_number?: string|null, description?: string|null}>  $orderLines
     * @return list<string>
     */
    public function products(array $orderLines): array
    {
        $texts = array_map(
            fn (array $line): string => trim(($line['article_number'] ?? '').' '.($line['description'] ?? '')),
            $orderLines,
        );

        return $this->library->usable()
            ->filter(fn (KnowledgeDocument $document): bool => $document->productSlug() !== null)
            ->filter(fn (KnowledgeDocument $document): bool => $this->matchesAny($document->orderKeywords(), $texts))
            ->map(fn (KnowledgeDocument $document): string => $document->productSlug())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Products whose customer terms or title occur in the given texts (title
     * and customer messages of a ticket, PROJ-33), with the matched words.
     * A term matches at the start of a word, ignoring case ("Glasstein" in
     * "Glassteine"); the title only as a whole phrase.
     *
     * @param  list<string>  $texts
     * @return array<string, list<string>> Product slug => matched words as written in the knowledge.
     */
    public function productsFromText(array $texts): array
    {
        $text = implode("\n", $texts);
        $found = [];

        if (trim($text) === '') {
            return [];
        }

        foreach ($this->library->usable() as $document) {
            $slug = $document->productSlug();

            if ($slug === null) {
                continue;
            }

            foreach ($document->customerTerms() as $term) {
                if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'/iu', $text) === 1) {
                    $found[$slug][] = $term;
                }
            }

            if ($document->title !== null && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($document->title, '/').'(?![\p{L}\p{N}])/iu', $text) === 1) {
                $found[$slug][] = $document->title;
            }
        }

        ksort($found);

        return array_map(fn (array $words): array => array_values(array_unique($words)), $found);
    }

    /**
     * The customer group of the order's sales channel; "unclear" without a
     * channel or for a channel name the configuration does not know.
     */
    public function customerGroup(?string $orderChannel): CustomerGroup
    {
        $channels = array_change_key_case($this->config['order_channels'], CASE_LOWER);
        $channel = $channels[mb_strtolower(trim((string) $orderChannel))] ?? null;
        $groups = $this->selector->customerGroups();

        foreach ($groups as $group) {
            if ($channel !== null && $group->salesChannel === $channel) {
                return $group;
            }
        }

        return array_values(array_filter($groups, fn (CustomerGroup $group): bool => $group->isUnclear()))[0];
    }

    /**
     * @param  list<string>  $keywords
     * @param  list<string>  $texts
     */
    private function matchesAny(array $keywords, array $texts): bool
    {
        foreach ($keywords as $keyword) {
            foreach ($texts as $text) {
                if (mb_stripos($text, $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}

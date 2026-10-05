<?php

namespace App\Knowledge;

use Illuminate\Support\Collection;

/**
 * Renders the session start block of docs/KNOWLEDGE_AUTHORING_GUIDE.md:
 * assigned IDs, next free ID per type and every tag value in use.
 */
class KnowledgeOverview
{
    /**
     * @param  array{types: array<string, array{folder: string, prefix: string}>}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @param  Collection<int, KnowledgeDocument>  $documents
     */
    public function render(Collection $documents): string
    {
        $lines = ['Vergebene IDs:'];

        foreach ($this->config['types'] as $type => $definition) {
            $assigned = $this->assigned($documents, $definition['prefix']);

            $lines[] = "{$type} (nächste freie ID: {$this->nextId($assigned, $definition['prefix'])}):";

            if ($assigned === []) {
                $lines[] = '  noch keine';
            }

            foreach ($assigned as $id => $notes) {
                $lines[] = "  {$id} – {$notes}";
            }
        }

        foreach ([
            'Vorhandene products-Slugs:' => 'products',
            'Vorhandene topics-Werte:' => 'topics',
            'Verwendete categories-Werte:' => 'categories',
            'Verwendete customer_types-Werte:' => 'customer_types',
            'Verwendete sales_channels-Werte:' => 'sales_channels',
        ] as $heading => $field) {
            $values = $this->values($documents, $field);

            array_push($lines, '', $heading, $values === [] ? 'noch keine' : implode(', ', $values));
        }

        $actions = array_map(fn (string $key, string $label): string => "{$key} ({$label})", array_keys($this->config['actions'] ?? []), $this->config['actions'] ?? []);
        array_push($lines, '', 'Erlaubte Vorgänge (actions):', $actions === [] ? 'keine' : implode(', ', $actions));

        $keywords = $this->orderKeywords($documents);
        array_push($lines, '', 'Vergebene order_keywords je Produkt:', ...($keywords === [] ? ['noch keine'] : $keywords));

        array_push($lines, '', 'Heute möchte ich erfassen:', '(Thema oder Fall)');

        return implode("\n", $lines);
    }

    /**
     * IDs in use for a prefix, with title. An ID counts as assigned even when
     * its file is faulty or deprecated — IDs are never reused.
     *
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @return array<string, string>
     */
    private function assigned(Collection $documents, string $prefix): array
    {
        $assigned = [];

        foreach ($documents as $document) {
            if ($document->id === null || ! preg_match('/^'.preg_quote($prefix, '/').'-(?!000)\d{3}$/', $document->id)) {
                continue;
            }

            $title = $document->parsed ? ($document->title ?? '(ohne Titel)') : '(Datei fehlerhaft, Titel nicht lesbar)';

            $assigned[$document->id] = $document->status === 'deprecated' ? $title.' (deprecated)' : $title;
        }

        ksort($assigned);

        return $assigned;
    }

    /**
     * @param  array<string, string>  $assigned
     */
    private function nextId(array $assigned, string $prefix): string
    {
        $highest = 0;

        foreach (array_keys($assigned) as $id) {
            $highest = max($highest, (int) substr($id, -3));
        }

        return sprintf('%s-%03d', $prefix, $highest + 1);
    }

    /**
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @return list<string>
     */
    private function values(Collection $documents, string $field): array
    {
        $values = $documents
            ->flatMap(fn (KnowledgeDocument $document): array => $document->list($field))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($field !== 'products') {
            return $values;
        }

        $files = $documents
            ->map(fn (KnowledgeDocument $document): ?string => $document->productSlug())
            ->filter();

        return collect($values)->merge($files)->unique()->sort()->values()->all();
    }

    /**
     * One line per product, e.g. "magic-mug: Thermotasse, Zaubertasse", so a
     * new product file does not reuse a keyword.
     *
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @return list<string>
     */
    private function orderKeywords(Collection $documents): array
    {
        return $documents
            ->filter(fn (KnowledgeDocument $document): bool => $document->productSlug() !== null && $document->orderKeywords() !== [])
            ->groupBy(fn (KnowledgeDocument $document): string => $document->productSlug())
            ->sortKeys()
            ->map(fn (Collection $files, string $slug): string => $slug.': '.$files
                ->flatMap(fn (KnowledgeDocument $document): array => $document->orderKeywords())
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->implode(', '))
            ->values()
            ->all();
    }
}

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
     * Opening line of the session start block for the AI chat.
     */
    public const string INTRO = 'Wissensdatenbank für die Customer Service Assist App unter der Berücksichtigung des Authoring Guides. Der Authoring Guide sollte als KNOWLEDGE_AUTHORING_GUIDE.md vorliegen, falls nicht bitte um Mitteilung!';

    /**
     * @param  array{types: array<string, array{folder: string, prefix: string}>}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @param  Collection<int, KnowledgeDocument>  $documents
     */
    public function render(Collection $documents): string
    {
        $lines = [self::INTRO, '', 'Vergebene IDs:'];

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

        $keywords = $this->perProduct($documents, fn (KnowledgeDocument $document): array => $document->orderKeywords());
        array_push($lines, '', 'Vergebene order_keywords je Produkt:', ...($keywords === [] ? ['noch keine'] : $keywords));

        $terms = $this->perProduct($documents, fn (KnowledgeDocument $document): array => $document->customerTerms());
        array_push($lines, '', 'Vergebene customer_terms je Produkt:', ...($terms === [] ? ['noch keine'] : $terms));

        array_push($lines, ...$this->retrieval($documents));

        array_push($lines, '', 'Heute möchte ich erfassen:', '(Thema oder Fall)');

        return implode("\n", $lines);
    }

    /**
     * What the targeted retrieval (PROJ-36) needs the author to know: core knowledge with its size,
     * triggers already given (several documents per trigger are fine), keys for `when` and what is
     * not classified yet.
     *
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @return list<string>
     */
    private function retrieval(Collection $documents): array
    {
        $settings = $this->config['retrieval'] ?? null;

        if ($settings === null) {
            return [];
        }

        $usable = $documents->filter(fn (KnowledgeDocument $document): bool => $document->parsed && $document->status !== 'deprecated' && $document->type !== 'procedure');
        $classes = array_map(fn (string $key, string $label): string => "{$key} ({$label})", array_keys($settings['classes']), $settings['classes']);
        $lines = ['', 'Klassen für retrieval:', implode(', ', $classes)];

        $core = $usable->filter(fn (KnowledgeDocument $document): bool => $document->retrieval() === 'core');
        $lines[] = '';
        $lines[] = 'Grundwissen (retrieval: core), steht in jedem Prompt – '.number_format($core->sum(fn (KnowledgeDocument $document): int => $document->length()), 0, ',', '.').' von '.number_format($settings['budget']['core'], 0, ',', '.').' Zeichen Budget:';

        array_push($lines, ...($core->isEmpty() ? ['noch keins'] : $core->map(fn (KnowledgeDocument $document): string => "  {$document->id} – {$document->title} (".number_format($document->length(), 0, ',', '.').' Zeichen)')->values()->all()));

        $triggers = [];

        foreach ($usable as $document) {
            foreach ($document->triggers() as $trigger) {
                $triggers[mb_strtolower($trigger)][] = (string) $document->id;
            }
        }

        ksort($triggers);
        $lines[] = '';
        $lines[] = 'Vergebene triggers (derselbe Begriff in mehreren Dokumenten ist erlaubt):';

        array_push($lines, ...($triggers === [] ? ['noch keine'] : array_map(fn (string $trigger, array $ids): string => "  {$trigger} → ".implode(', ', array_unique($ids)).(count(array_unique($ids)) > 1 ? ' (mehrfach)' : ''), array_keys($triggers), $triggers)));

        $lines[] = '';
        $lines[] = 'Schlüssel für when (Ebene; Quelle; Verfügbarkeit):';

        foreach ($settings['when_keys'] as $key => $definition) {
            $lines[] = "  {$key} ({$definition['type']}) – ".($definition['level'] === 'order' ? 'je Bestellung' : 'für den Fall').'; '.$definition['source'].'; '.$definition['availability'];
        }

        $unclassified = $usable->filter(fn (KnowledgeDocument $document): bool => $document->retrieval() === null);
        $lines[] = '';
        $lines[] = 'Noch nicht klassifiziert (retrieval fehlt, zählt vorläufig als Grundwissen): '.$unclassified->count();

        if ($unclassified->isNotEmpty()) {
            $lines[] = '  '.$unclassified->map(fn (KnowledgeDocument $document): string => (string) $document->id)->implode(', ');
        }

        return $lines;
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
     * new product file does not reuse a keyword or customer term.
     *
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @param  callable(KnowledgeDocument): list<string>  $values
     * @return list<string>
     */
    private function perProduct(Collection $documents, callable $values): array
    {
        return $documents
            ->filter(fn (KnowledgeDocument $document): bool => $document->productSlug() !== null && $values($document) !== [])
            ->groupBy(fn (KnowledgeDocument $document): string => $document->productSlug())
            ->sortKeys()
            ->map(fn (Collection $files, string $slug): string => $slug.': '.$files
                ->flatMap(fn (KnowledgeDocument $document): array => $values($document))
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->implode(', '))
            ->values()
            ->all();
    }
}

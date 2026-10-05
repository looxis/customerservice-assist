<?php

namespace App\Knowledge;

class KnowledgeValidator
{
    private const string SLUG = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    private const array LIST_FIELDS = ['products', 'categories', 'topics', 'customer_types', 'sales_channels', 'related_knowledge', 'order_keywords', 'actions', 'action'];

    private const array KNOWN_FIELDS = [
        'id', 'title', 'type', 'status',
        'products', 'categories', 'topics', 'customer_types', 'sales_channels', 'related_knowledge',
        'priority', 'risk_level', 'owner', 'reviewed_by', 'last_reviewed',
    ];

    private const array PERMISSION_FIELDS = ['action', 'agent_allowed', 'max_value_eur', 'approval_role'];

    private const array PRODUCT_FIELDS = ['order_keywords'];

    private const array PROCEDURE_FIELDS = ['actions'];

    /**
     * @param  array{types: array<string, array{folder: string, prefix: string}>, statuses: list<string>, customer_types: list<string>, sales_channels: list<string>, categories: list<string>, actions: array<string, string>, procedure_sections: list<string>, retired_values: array<string, array<string, string>>, customer_groups: array<string, array{label: string, customer_type: ?string, sales_channel: ?string}>, min_order_keyword_length: int, max_body_length: int}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * Validate all documents: rules per document first, then rules across documents.
     *
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeIssue>
     */
    public function validate(array $documents): array
    {
        $parsed = array_values(array_filter($documents, fn (KnowledgeDocument $document): bool => $document->parsed));
        $issues = [];

        foreach ($parsed as $document) {
            array_push(
                $issues,
                ...$this->location($document),
                ...$this->requiredFields($document),
                ...$this->typeAndStatus($document),
                ...$this->idScheme($document),
                ...$this->listFields($document),
                ...$this->fixedValues($document),
                ...$this->permissionFields($document),
                ...$this->procedureFields($document),
                ...$this->orderKeywords($document),
                ...$this->reachability($document),
                ...$this->body($document),
                ...$this->openQuestions($document),
                ...$this->missingCategories($document),
                ...$this->filename($document),
                ...$this->slugs($document),
                ...$this->unknownFields($document),
                ...$this->personalData($document),
            );
        }

        array_push(
            $issues,
            ...$this->duplicateIds($parsed),
            ...$this->references($parsed),
            ...$this->productFiles($parsed),
            ...$this->duplicateOrderKeywords($parsed),
        );

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function location(KnowledgeDocument $document): array
    {
        if ($document->folderType !== null) {
            return [];
        }

        return [KnowledgeIssue::error($document->path, 'Die Datei liegt in keinem Typ-Ordner (z. B. policies/, playbooks/).')];
    }

    /** @return list<KnowledgeIssue> */
    private function requiredFields(KnowledgeDocument $document): array
    {
        $issues = [];

        foreach (['id', 'title', 'type', 'status'] as $field) {
            $value = $document->frontmatter[$field] ?? null;

            if ($value !== null && ! is_string($value) && ! is_int($value) && ! is_float($value)) {
                $issues[] = KnowledgeIssue::error($document->path, "Feld `{$field}` muss ein einfacher Text sein. Sieht der Wert wie ein Datum, ein Ja/Nein-Wert oder eine Liste aus, bitte in Anführungszeichen setzen.");
            } elseif ($document->string($field) === null) {
                $issues[] = KnowledgeIssue::error($document->path, "Pflichtfeld `{$field}` fehlt oder ist leer.");
            }
        }

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function typeAndStatus(KnowledgeDocument $document): array
    {
        $issues = [];
        $types = array_keys($this->config['types']);

        if ($document->type !== null && ! in_array($document->type, $types, true)) {
            $issues[] = KnowledgeIssue::error($document->path, "Unbekannter Typ `{$document->type}`. Erlaubt: ".implode(', ', $types).'.');
        }

        if ($document->status !== null && ! in_array($document->status, $this->config['statuses'], true)) {
            $issues[] = KnowledgeIssue::error($document->path, "Unbekannter Status `{$document->status}`. Erlaubt: ".implode(', ', $this->config['statuses']).'.');
        }

        if ($this->hasKnownType($document) && $document->folderType !== null && $document->type !== $document->folderType) {
            $folder = $this->config['types'][$document->type]['folder'];
            $issues[] = KnowledgeIssue::error($document->path, "Typ `{$document->type}` passt nicht zum Ordner. Dokumente dieses Typs gehören nach {$folder}/.");
        }

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function idScheme(KnowledgeDocument $document): array
    {
        if ($document->id === null || ! $this->hasKnownType($document)) {
            return [];
        }

        $prefix = $this->config['types'][$document->type]['prefix'];

        if (! preg_match('/^'.preg_quote($prefix, '/').'-\d{3}$/', $document->id)) {
            return [KnowledgeIssue::error($document->path, "Die ID `{$document->id}` passt nicht zum Schema {$prefix}-001 (Präfix des Typs, dreistellige Nummer).")];
        }

        if (str_ends_with($document->id, '-000')) {
            return [KnowledgeIssue::error($document->path, 'Die Nummer 000 ist für Vorlagen reserviert. Bitte die nächste freie ID vergeben.')];
        }

        return [];
    }

    /** @return list<KnowledgeIssue> */
    private function listFields(KnowledgeDocument $document): array
    {
        $issues = [];

        foreach (self::LIST_FIELDS as $field) {
            $value = $document->frontmatter[$field] ?? null;

            if (! is_array($value)) {
                continue;
            }

            if (! array_is_list($value) || array_filter($value, fn (mixed $item): bool => ! is_scalar($item)) !== []) {
                $issues[] = KnowledgeIssue::error($document->path, "Feld `{$field}` muss eine einfache Liste von Werten sein.");
            }
        }

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function fixedValues(KnowledgeDocument $document): array
    {
        $issues = [];

        foreach (['customer_types', 'sales_channels', 'categories'] as $field) {
            foreach (array_diff($document->list($field), $this->config[$field]) as $value) {
                $hint = $this->config['retired_values'][$field][$value] ?? 'Erlaubt: '.implode(', ', $this->config[$field]).'.';
                $issues[] = KnowledgeIssue::error($document->path, "Unbekannter Wert `{$value}` in `{$field}`. {$hint}");
            }
        }

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function permissionFields(KnowledgeDocument $document): array
    {
        if ($document->type !== 'permission') {
            return [];
        }

        $issues = [];

        if ($document->list('action') === []) {
            $issues[] = KnowledgeIssue::error($document->path, 'Permission ohne `action`: Bitte angeben, welche Maßnahme die Befugnis betrifft.');
        }

        foreach (array_diff($document->list('action'), array_keys($this->config['actions'])) as $action) {
            $issues[] = KnowledgeIssue::warning($document->path, "Unbekannter Vorgang `{$action}` in `action`. Bekannt: ".implode(', ', array_keys($this->config['actions'])).'.');
        }

        if (! is_bool($document->frontmatter['agent_allowed'] ?? null)) {
            $issues[] = KnowledgeIssue::error($document->path, '`agent_allowed` muss `true` oder `false` sein.');
        }

        $limit = $document->frontmatter['max_value_eur'] ?? null;

        if ($limit !== null && ! is_int($limit) && ! is_float($limit)) {
            $issues[] = KnowledgeIssue::error($document->path, '`max_value_eur` muss eine Zahl ohne Währungszeichen sein oder leer bleiben.');
        } elseif ($limit !== null && $limit < 0) {
            $issues[] = KnowledgeIssue::error($document->path, '`max_value_eur` darf nicht negativ sein.');
        }

        return $issues;
    }

    /**
     * A procedure names the actions it is for and has the fixed sections.
     *
     * @return list<KnowledgeIssue>
     */
    private function procedureFields(KnowledgeDocument $document): array
    {
        if ($document->type !== 'procedure') {
            return [];
        }

        $issues = [];
        $allowed = implode(', ', array_keys($this->config['actions']));

        if ($document->actions() === []) {
            $issues[] = KnowledgeIssue::error($document->path, "Arbeitsablauf ohne `actions`: Bitte angeben, für welche Vorgänge er gilt. Erlaubt: {$allowed}.");
        }

        foreach (array_diff($document->actions(), array_keys($this->config['actions'])) as $action) {
            $issues[] = KnowledgeIssue::error($document->path, "Unbekannter Vorgang `{$action}` in `actions`. Erlaubt: {$allowed}.");
        }

        foreach ($this->config['procedure_sections'] as $section) {
            if (! preg_match('/^#[ \t]+'.preg_quote($section, '/').'[ \t]*$/mu', $document->body)) {
                $issues[] = KnowledgeIssue::warning($document->path, "Abschnitt „# {$section}\" fehlt.");
            }
        }

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function orderKeywords(KnowledgeDocument $document): array
    {
        if ($document->type !== 'product') {
            return [];
        }

        $minimum = $this->config['min_order_keyword_length'];

        return array_values(array_map(
            fn (string $keyword): KnowledgeIssue => KnowledgeIssue::warning($document->path, "Das Schlüsselwort `{$keyword}` in `order_keywords` ist kürzer als {$minimum} Zeichen und passt vermutlich auf viele Bestellpositionen."),
            array_filter($document->orderKeywords(), fn (string $keyword): bool => mb_strlen($keyword) < $minimum),
        ));
    }

    /**
     * A document whose customer type and channel fit no customer group is never selected.
     *
     * @return list<KnowledgeIssue>
     */
    private function reachability(KnowledgeDocument $document): array
    {
        $customerTypes = $document->customerTypes();
        $salesChannels = $document->salesChannels();

        if (($customerTypes === [] && $salesChannels === [])
            || array_diff($customerTypes, $this->config['customer_types']) !== []
            || array_diff($salesChannels, $this->config['sales_channels']) !== []) {
            return [];
        }

        foreach (CustomerGroup::all($this->config['customer_groups']) as $group) {
            if ($group->covers($document)) {
                return [];
            }
        }

        return [KnowledgeIssue::warning($document->path, 'Kundenart und Kanal passen zu keiner Kundengruppe (z. B. `b2c` nur zusammen mit Kanal `looxis-pro`). Das Dokument wird nie ausgewählt.')];
    }

    /** @return list<KnowledgeIssue> */
    private function body(KnowledgeDocument $document): array
    {
        if ($document->body === '') {
            return [KnowledgeIssue::error($document->path, 'Der Textteil ist leer.')];
        }

        $length = mb_strlen($document->body);

        if ($length > $this->config['max_body_length']) {
            return [KnowledgeIssue::warning($document->path, "Der Text ist mit {$length} Zeichen sehr lang (Grenze: {$this->config['max_body_length']}). Das Dokument behandelt vermutlich mehr als ein Thema.")];
        }

        return [];
    }

    /** @return list<KnowledgeIssue> */
    private function openQuestions(KnowledgeDocument $document): array
    {
        if ($document->status !== 'active' || ! preg_match('/^#+\s*Noch zu klären/miu', $document->body)) {
            return [];
        }

        return [KnowledgeIssue::warning($document->path, 'Aktives Dokument mit Abschnitt „Noch zu klären": Offene Punkte werden als geltender Inhalt mitgelesen.')];
    }

    /** @return list<KnowledgeIssue> */
    private function missingCategories(KnowledgeDocument $document): array
    {
        if (! in_array($document->type, ['playbook', 'process'], true) || $document->categories() !== []) {
            return [];
        }

        return [KnowledgeIssue::warning($document->path, 'Playbooks und Processes brauchen mindestens eine Kategorie in `categories`.')];
    }

    /** @return list<KnowledgeIssue> */
    private function filename(KnowledgeDocument $document): array
    {
        if (pathinfo($document->path, PATHINFO_EXTENSION) !== 'md') {
            return [KnowledgeIssue::warning($document->path, 'Die Dateiendung sollte `.md` sein (klein geschrieben).')];
        }

        if (! $this->hasKnownType($document)) {
            return [];
        }

        $name = pathinfo($document->path, PATHINFO_FILENAME);

        if ($document->type === 'product') {
            return preg_match(self::SLUG, $name)
                ? []
                : [KnowledgeIssue::warning($document->path, 'Der Dateiname einer Produktdatei ist der Produkt-Slug: nur Kleinbuchstaben, Ziffern und Bindestriche.')];
        }

        if ($document->id === null) {
            return [];
        }

        $expected = strtolower($document->id);

        if (! str_starts_with($name, $expected.'-') || ! preg_match(self::SLUG, $name)) {
            return [KnowledgeIssue::warning($document->path, "Der Dateiname sollte dem Schema {$expected}-kurzer-titel.md folgen (Kleinbuchstaben, Ziffern, Bindestriche).")];
        }

        return [];
    }

    /** @return list<KnowledgeIssue> */
    private function slugs(KnowledgeDocument $document): array
    {
        $issues = [];

        foreach (['products', 'topics'] as $field) {
            foreach ($document->list($field) as $value) {
                if (! preg_match(self::SLUG, $value)) {
                    $issues[] = KnowledgeIssue::warning($document->path, "Wert `{$value}` in `{$field}` ist kein Slug (nur Kleinbuchstaben, Ziffern, Bindestriche).");
                }
            }
        }

        return $issues;
    }

    /** @return list<KnowledgeIssue> */
    private function unknownFields(KnowledgeDocument $document): array
    {
        $known = match ($document->type) {
            'permission' => [...self::KNOWN_FIELDS, ...self::PERMISSION_FIELDS],
            'product' => [...self::KNOWN_FIELDS, ...self::PRODUCT_FIELDS],
            'procedure' => [...self::KNOWN_FIELDS, ...self::PROCEDURE_FIELDS],
            default => self::KNOWN_FIELDS,
        };

        return array_map(
            fn (string $field): KnowledgeIssue => KnowledgeIssue::warning($document->path, "Unbekanntes Feld `{$field}` im Frontmatter (Tippfehler?)."),
            array_values(array_diff(array_map('strval', array_keys($document->frontmatter)), $known)),
        );
    }

    /** @return list<KnowledgeIssue> */
    private function personalData(KnowledgeDocument $document): array
    {
        $text = $document->title.' '.$document->body;

        $found = array_keys(array_filter([
            'E-Mail-Adresse' => preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/u', $text),
            'Telefonnummer' => preg_match('/(?<![\w.,-])(?:\+\d|0\d)(?:[ \/-]?\d){7,}(?![\w-])/', $text),
            'IBAN' => preg_match('/\b[A-Z]{2}\d{2}(?: ?[A-Z0-9]{4}){3,}/', $text),
            'lange Ziffernfolge (z. B. Bestell- oder Kundennummer)' => preg_match('/(?<![\w-])\d{7,}(?![\w-])/', $text),
        ]));

        if ($found === []) {
            return [];
        }

        return [KnowledgeIssue::warning($document->path, 'Mögliche personenbezogene Daten: '.implode(', ', $found).'. Bitte prüfen. Namen und Anschriften erkennt die Prüfung nicht.')];
    }

    /**
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeIssue>
     */
    private function duplicateIds(array $documents): array
    {
        $byId = [];

        foreach ($documents as $document) {
            if ($document->id !== null) {
                $byId[$document->id][] = $document->path;
            }
        }

        $issues = [];

        foreach ($byId as $id => $paths) {
            if (count($paths) < 2) {
                continue;
            }

            foreach ($paths as $path) {
                $others = implode(', ', array_diff($paths, [$path]));
                $issues[] = KnowledgeIssue::error($path, "Die ID `{$id}` ist doppelt vergeben, auch in: {$others}.");
            }
        }

        return $issues;
    }

    /**
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeIssue>
     */
    private function references(array $documents): array
    {
        $statusById = [];

        foreach ($documents as $document) {
            if ($document->id !== null) {
                $statusById[$document->id] = $document->status;
            }
        }

        $prefixes = array_column($this->config['types'], 'prefix');
        usort($prefixes, fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $pattern = '/(?<![\w-])(?:'.implode('|', array_map(fn (string $prefix): string => preg_quote($prefix, '/'), $prefixes)).')-\d{3}(?!\d)/';

        $issues = [];

        foreach ($documents as $document) {
            preg_match_all($pattern, $document->body, $matches);

            $references = array_unique([...$document->relatedKnowledge(), ...$matches[0]]);

            foreach ($references as $reference) {
                if ($reference === $document->id) {
                    continue;
                }

                if (! array_key_exists($reference, $statusById)) {
                    $issues[] = KnowledgeIssue::warning($document->path, "Verweis auf `{$reference}`, aber dieses Dokument gibt es nicht.");
                } elseif ($statusById[$reference] === 'deprecated') {
                    $issues[] = KnowledgeIssue::warning($document->path, "Verweis auf `{$reference}`, das als deprecated markiert ist.");
                }
            }
        }

        return $issues;
    }

    /**
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeIssue>
     */
    private function productFiles(array $documents): array
    {
        $known = [];

        foreach ($documents as $document) {
            if ($document->folderType === 'product') {
                array_push($known, ...$document->boundProducts());
            }
        }

        $issues = [];

        foreach ($documents as $document) {
            if ($document->folderType === 'product') {
                continue;
            }

            foreach (array_diff($document->products(), $known) as $product) {
                $issues[] = KnowledgeIssue::warning($document->path, "Für das Produkt `{$product}` gibt es keine Produktdatei in products/.");
            }
        }

        return $issues;
    }

    /**
     * The same order keyword in files of different products makes the product suggestion ambiguous.
     *
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeIssue>
     */
    private function duplicateOrderKeywords(array $documents): array
    {
        $owners = [];

        foreach ($documents as $document) {
            if ($document->productSlug() === null) {
                continue;
            }

            foreach (array_unique(array_map('mb_strtolower', $document->orderKeywords())) as $keyword) {
                $owners[$keyword][] = $document;
            }
        }

        $issues = [];

        foreach ($owners as $keyword => $files) {
            $products = array_unique(array_map(fn (KnowledgeDocument $document): string => $document->productSlug(), $files));

            if (count($products) < 2) {
                continue;
            }

            foreach ($files as $document) {
                $others = implode(', ', array_diff($products, [$document->productSlug()]));
                $issues[] = KnowledgeIssue::warning($document->path, "Das Schlüsselwort `{$keyword}` in `order_keywords` steht auch beim Produkt {$others}. Der Produktvorschlag ist dann nicht eindeutig.");
            }
        }

        return $issues;
    }

    private function hasKnownType(KnowledgeDocument $document): bool
    {
        return $document->type !== null && array_key_exists($document->type, $this->config['types']);
    }
}

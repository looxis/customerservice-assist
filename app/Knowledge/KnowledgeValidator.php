<?php

namespace App\Knowledge;

class KnowledgeValidator
{
    private const string SLUG = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    private const array LIST_FIELDS = ['products', 'categories', 'topics', 'customer_types', 'sales_channels', 'related_knowledge'];

    private const array KNOWN_FIELDS = [
        'id', 'title', 'type', 'status',
        'products', 'categories', 'topics', 'customer_types', 'sales_channels', 'related_knowledge',
        'priority', 'risk_level', 'owner', 'reviewed_by', 'last_reviewed',
    ];

    private const array PERMISSION_FIELDS = ['action', 'agent_allowed', 'max_value_eur', 'approval_role'];

    /**
     * @param  array{types: array<string, array{folder: string, prefix: string}>, statuses: list<string>, customer_types: list<string>, sales_channels: list<string>, categories: list<string>, max_body_length: int}  $config
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
                $issues[] = KnowledgeIssue::error($document->path, "Unbekannter Wert `{$value}` in `{$field}`. Erlaubt: ".implode(', ', $this->config[$field]).'.');
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

        if ($document->string('action') === null) {
            $issues[] = KnowledgeIssue::error($document->path, 'Permission ohne `action`: Bitte angeben, welche Maßnahme die Befugnis betrifft.');
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
        $known = $document->type === 'permission'
            ? [...self::KNOWN_FIELDS, ...self::PERMISSION_FIELDS]
            : self::KNOWN_FIELDS;

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
                $known[] = pathinfo($document->path, PATHINFO_FILENAME);
                array_push($known, ...$document->products());
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

    private function hasKnownType(KnowledgeDocument $document): bool
    {
        return $document->type !== null && array_key_exists($document->type, $this->config['types']);
    }
}

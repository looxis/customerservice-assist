<?php

namespace App\Knowledge;

class KnowledgeValidator
{
    private const string SLUG = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    private const array LIST_FIELDS = ['products', 'exclude_products', 'categories', 'topics', 'customer_types', 'sales_channels', 'related_knowledge', 'order_keywords', 'customer_terms', 'actions', 'action', 'triggers', 'exclude_triggers'];

    private const array KNOWN_FIELDS = [
        'id', 'title', 'type', 'status',
        'products', 'exclude_products', 'categories', 'topics', 'customer_types', 'sales_channels', 'related_knowledge',
        'priority', 'risk_level', 'owner', 'reviewed_by', 'last_reviewed',
        'summary', 'retrieval', 'triggers', 'exclude_triggers', 'when', 'actions',
    ];

    private const array PERMISSION_FIELDS = ['action', 'agent_allowed', 'max_value_eur', 'approval_role'];

    private const array PRODUCT_FIELDS = ['order_keywords', 'customer_terms'];

    private const array PROCEDURE_FIELDS = ['actions'];

    /**
     * @param  array{types: array<string, array{folder: string, prefix: string}>, statuses: list<string>, customer_types: list<string>, sales_channels: list<string>, categories: list<string>, actions: array<string, string>, procedure_sections: list<string>, retired_values: array<string, array<string, string>>, customer_groups: array<string, array{label: string, customer_type: ?string, sales_channel: ?string}>, min_order_keyword_length: int, min_customer_term_length?: int, max_body_length: int}  $config
     */
    public function __construct(private readonly array $config)
    {
        $this->when = new WhenSchema($config['retrieval']['when_keys'] ?? [], [
            'customer_types' => $config['customer_types'],
            'sales_channels' => $config['sales_channels'],
        ]);
    }

    private readonly WhenSchema $when;

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
                ...$this->retrieval($document),
                ...$this->reachability($document),
                ...$this->body($document),
                ...$this->openQuestions($document),
                ...$this->missingCategories($document),
                ...$this->filename($document),
                ...$this->slugs($document),
                ...$this->excludedProducts($document),
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
            ...$this->duplicateCustomerTerms($parsed),
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
        $termMinimum = $this->config['min_customer_term_length'] ?? 4;

        return [
            ...array_values(array_map(
                fn (string $keyword): KnowledgeIssue => KnowledgeIssue::warning($document->path, "Das Schlüsselwort `{$keyword}` in `order_keywords` ist kürzer als {$minimum} Zeichen und passt vermutlich auf viele Bestellpositionen."),
                array_filter($document->orderKeywords(), fn (string $keyword): bool => mb_strlen($keyword) < $minimum),
            )),
            ...array_values(array_map(
                fn (string $term): KnowledgeIssue => KnowledgeIssue::warning($document->path, "Der Kundenbegriff `{$term}` in `customer_terms` ist kürzer als {$termMinimum} Zeichen und passt vermutlich auf viele Texte."),
                array_filter($document->customerTerms(), fn (string $term): bool => mb_strlen($term) < $termMinimum),
            )),
        ];
    }

    /**
     * Fields for the targeted retrieval (PROJ-36): class, summary, triggers,
     * exclusions, condition and actions.
     *
     * @return list<KnowledgeIssue>
     */
    private function retrieval(KnowledgeDocument $document): array
    {
        $settings = $this->config['retrieval'] ?? [];
        $classes = array_keys($settings['classes'] ?? []);
        $required = (bool) ($settings['required'] ?? false);
        $class = $document->retrieval();
        $path = $document->path;
        $issues = [];

        if (isset($document->frontmatter['retrieval']) && ! is_string($document->frontmatter['retrieval'])) {
            $issues[] = KnowledgeIssue::error($path, 'Feld `retrieval` muss ein einfacher Text sein: '.implode(', ', $classes).'.');
        } elseif ($class === null && $required && $document->type !== 'procedure') {
            $issues[] = KnowledgeIssue::error($path, 'Pflichtfeld `retrieval` fehlt. Erlaubt: '.implode(', ', $classes).'.');
        } elseif ($class !== null && ! in_array($class, $classes, true)) {
            $issues[] = KnowledgeIssue::error($path, "Unbekannter Wert `{$class}` in `retrieval`. Erlaubt: ".implode(', ', $classes).'.');
        }

        if (array_key_exists('summary', $document->frontmatter) && $document->frontmatter['summary'] !== null && ! is_string($document->frontmatter['summary'])) {
            $issues[] = KnowledgeIssue::error($path, 'Feld `summary` muss ein einfacher Text sein. Enthält er einen Doppelpunkt, bitte in Anführungszeichen setzen.');
        } elseif ($class !== null && $document->summary() === null) {
            $issues[] = KnowledgeIssue::warning($path, 'Ein Satz in `summary` fehlt: Wann wird das Dokument gebraucht, wann nicht?');
        }

        $triggers = $document->triggers();
        $excluded = $document->excludeTriggers();
        $hasWhen = array_key_exists('when', $document->frontmatter) && $document->frontmatter['when'] !== null;
        $minimum = (int) ($settings['min_trigger_length'] ?? 4);
        $maximum = (int) ($settings['max_triggers'] ?? 25);

        foreach (['triggers' => $triggers, 'exclude_triggers' => $excluded] as $field => $values) {
            foreach (array_filter($values, fn (string $value): bool => mb_strlen($value) < $minimum) as $value) {
                $issues[] = KnowledgeIssue::warning($path, "Der Eintrag `{$value}` in `{$field}` ist kürzer als {$minimum} Zeichen und passt vermutlich auf viele Texte.");
            }
        }

        if (count($triggers) > $maximum) {
            $issues[] = KnowledgeIssue::warning($path, 'Das Dokument hat '.count($triggers)." Auslöser (Warnschwelle: {$maximum}). Behandelt es mehr als ein Thema?");
        }

        if ($hasWhen) {
            foreach ($this->when->errors($document->frontmatter['when']) as $error) {
                $issues[] = KnowledgeIssue::error($path, $error);
            }

            $issues = [...$issues, ...$this->unavailableKeys($document)];
        }

        $hasActions = $document->actions() !== [];

        if ($document->type !== 'procedure') {
            foreach (array_diff($document->actions(), array_keys($this->config['actions'])) as $action) {
                $issues[] = KnowledgeIssue::error($path, "Unbekannter Vorgang `{$action}` in `actions`. Erlaubt: ".implode(', ', array_keys($this->config['actions'])).'.');
            }
        }

        if ($class === 'triggered' && $triggers === [] && ! $hasWhen && ! $hasActions) {
            $issues[] = $required
                ? KnowledgeIssue::error($path, 'Dokument mit `retrieval: triggered` ohne `triggers` und ohne `when`: Es könnte nie abgerufen werden.')
                : KnowledgeIssue::warning($path, 'Dokument mit `retrieval: triggered` ohne `triggers` und ohne `when`: Es wird nie abgerufen. Nach der Migration ist das ein Fehler.');
        } elseif ($class === 'binding') {
            array_push($issues, ...$this->bindingIssues($document, $triggers, $excluded, $hasWhen, $hasActions));
        } elseif ($class === 'core' && ($triggers !== [] || $excluded !== [] || $hasWhen)) {
            $issues[] = KnowledgeIssue::warning($path, 'Auslöser, Ausschlüsse und `when` haben bei `retrieval: core` keine Wirkung: Grundwissen ist immer im Geltungsbereich.');
        }

        if ($excluded !== [] && $triggers === [] && $class !== 'binding') {
            $issues[] = KnowledgeIssue::warning($path, '`exclude_triggers` ohne `triggers` hat keine Wirkung.');
        }

        return $issues;
    }

    /**
     * @param  list<string>  $triggers
     * @param  list<string>  $excluded
     * @return list<KnowledgeIssue>
     */
    private function bindingIssues(KnowledgeDocument $document, array $triggers, array $excluded, bool $hasWhen, bool $hasActions): array
    {
        $issues = [];

        if ($excluded !== []) {
            $issues[] = KnowledgeIssue::error($document->path, 'Verbindliche Dokumente (`retrieval: binding`) dürfen keine `exclude_triggers` haben: Ein Ausschluss könnte eine Pflichtregel unterdrücken.');
        }

        if ($triggers !== [] && ! $hasWhen && ! $hasActions) {
            $issues[] = KnowledgeIssue::warning($document->path, 'Verbindliches Dokument, das nur über `triggers` abgerufen wird: Es kann übersehen werden, wenn das Stichwort fehlt. Ohne `triggers` und `when` gilt es dagegen immer im Geltungsbereich; alternativ `when` oder `actions` ergänzen.');
        }

        return $issues;
    }

    /**
     * A `when` key that is never known for any customer group the document applies to.
     *
     * @return list<KnowledgeIssue>
     */
    private function unavailableKeys(KnowledgeDocument $document): array
    {
        $groups = array_values(array_filter(
            CustomerGroup::all($this->config['customer_groups']),
            fn (CustomerGroup $group): bool => $group->covers($document),
        ));

        if ($groups === []) {
            return [];
        }

        $issues = [];

        foreach ($this->when->usedKeys($document->frontmatter['when']) as $key) {
            $never = $this->config['retrieval']['when_keys'][$key]['unavailable_for_groups'] ?? [];

            if ($never !== [] && array_diff(array_map(fn (CustomerGroup $group): string => $group->key, $groups), $never) === []) {
                $issues[] = KnowledgeIssue::warning($document->path, "Der Schlüssel `{$key}` in `when` ist für die Kundengruppen dieses Dokuments nie bekannt.");
            }
        }

        return $issues;
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

    /**
     * `exclude_products` narrows a scope; it must not contradict `products` and has no meaning in a product file.
     *
     * @return list<KnowledgeIssue>
     */
    private function excludedProducts(KnowledgeDocument $document): array
    {
        $excluded = $document->excludedProducts();

        if ($excluded === []) {
            return [];
        }

        if ($document->folderType === 'product') {
            return [KnowledgeIssue::error($document->path, 'Eine Produktdatei darf kein `exclude_products` haben: Sie gilt immer für ihr eigenes Produkt.')];
        }

        $both = array_values(array_intersect($excluded, $document->products()));

        return $both === [] ? [] : [KnowledgeIssue::error($document->path, 'Produkt in `products` und in `exclude_products` zugleich: '.implode(', ', $both).'.')];
    }

    /** @return list<KnowledgeIssue> */
    private function slugs(KnowledgeDocument $document): array
    {
        $issues = [];

        foreach (['products', 'exclude_products', 'topics'] as $field) {
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

            foreach (array_diff($document->excludedProducts(), $known) as $product) {
                $issues[] = KnowledgeIssue::warning($document->path, "Für das Produkt `{$product}` in `exclude_products` gibt es keine Produktdatei in products/.");
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

    /**
     * A customer term at two products, or equal to another product's order
     * keyword, makes the suggestion from the ticket text ambiguous (PROJ-33).
     *
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeIssue>
     */
    private function duplicateCustomerTerms(array $documents): array
    {
        $terms = [];
        $keywords = [];

        foreach ($documents as $document) {
            if ($document->productSlug() === null) {
                continue;
            }

            foreach (array_unique(array_map('mb_strtolower', $document->customerTerms())) as $term) {
                $terms[$term][] = $document;
            }

            foreach (array_unique(array_map('mb_strtolower', $document->orderKeywords())) as $keyword) {
                $keywords[$keyword][] = $document->productSlug();
            }
        }

        $issues = [];

        foreach ($terms as $term => $files) {
            $products = array_unique(array_map(fn (KnowledgeDocument $document): string => $document->productSlug(), $files));

            foreach ($files as $document) {
                $others = array_diff($products, [$document->productSlug()]);
                $keywordOwners = array_diff(array_unique($keywords[$term] ?? []), [$document->productSlug()]);

                if ($others !== []) {
                    $issues[] = KnowledgeIssue::warning($document->path, "Der Kundenbegriff `{$term}` in `customer_terms` steht auch beim Produkt ".implode(', ', $others).'. Der Produktvorschlag ist dann nicht eindeutig.');
                }

                if ($keywordOwners !== []) {
                    $issues[] = KnowledgeIssue::warning($document->path, "Der Kundenbegriff `{$term}` in `customer_terms` steht beim Produkt ".implode(', ', $keywordOwners).' in `order_keywords`. Der Produktvorschlag ist dann nicht eindeutig.');
                }
            }
        }

        return $issues;
    }

    private function hasKnownType(KnowledgeDocument $document): bool
    {
        return $document->type !== null && array_key_exists($document->type, $this->config['types']);
    }
}

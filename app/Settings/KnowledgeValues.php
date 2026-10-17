<?php

namespace App\Settings;

use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeLibrary;
use Illuminate\Support\Arr;
use Throwable;

/**
 * The values of the knowledge base that the settings page changes (PROJ-50): customer types, sales channels,
 * customer groups, EOCS channel names, categories and actions. The defaults are the configuration of the app
 * as it was before anything was saved; saved values are laid over the configuration, so every place that reads
 * `config('knowledge.…')` (checking the documents, the selection, the analysis, the router) sees the same values.
 */
class KnowledgeValues
{
    public const array GROUP_KEYS = ['kn_customer_types', 'kn_sales_channels', 'kn_groups', 'kn_order_channels', 'kn_categories', 'kn_actions'];

    private const array TYPE_LABELS = ['b2c' => 'Privatkunde', 'b2b-reseller' => 'Foto-Fachhändler / Reseller', 'b2b-pro' => 'LOOXIS-Pro', 'b2b-whitelabel' => 'White-Label-Kunde'];

    private const array CHANNEL_LABELS = ['looxis-de' => 'looxis.de', 'amazon' => 'Amazon', 'fachhaendler' => 'Fachhändler', 'looxis-pro' => 'LOOXIS-Pro', 'masterpics' => 'masterpics'];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $baseline = null;

    /**
     * Lays the saved values over the configuration. Nothing is touched where nothing was saved, and a database
     * or value problem leaves the configuration as it is.
     */
    public function apply(): void
    {
        $this->baseline();

        try {
            foreach (self::GROUP_KEYS as $key) {
                if ($this->store()->isDefault($key)) {
                    continue;
                }

                $this->overlay($key, $this->store()->value($key));
            }
        } catch (Throwable) {
            // The configuration of the app applies.
        }
    }

    /**
     * The configuration of the app, as it was before any saved value was laid over it.
     *
     * @return array<string, mixed>
     */
    public function baseline(): array
    {
        return $this->baseline ??= Arr::only((array) config('knowledge'), ['customer_types', 'sales_channels', 'customer_groups', 'order_channels', 'categories', 'category_labels', 'actions', 'action_hints']);
    }

    /**
     * The default value of a settings group, in the form the page edits.
     *
     * @return list<array<string, mixed>>
     */
    public function defaults(string $key): array
    {
        $base = $this->baseline();

        return match ($key) {
            'kn_customer_types' => array_map(fn (string $type): array => ['key' => $type, 'label' => self::TYPE_LABELS[$type] ?? $type], array_values($base['customer_types'])),
            'kn_sales_channels' => array_map(fn (string $channel): array => ['key' => $channel, 'label' => self::CHANNEL_LABELS[$channel] ?? $channel], array_values($base['sales_channels'])),
            'kn_groups' => array_values(array_map(fn (string $groupKey, array $group): array => [
                'key' => $groupKey, 'label' => $group['label'], 'customer_type' => $group['customer_type'] ?? null, 'sales_channel' => $group['sales_channel'] ?? null,
                'status' => $group['status'] ?? CustomerGroup::ACTIVE, 'released_by' => null, 'released_at' => null, 'confirmed_binding' => [],
            ], array_keys($base['customer_groups']), $base['customer_groups'])),
            'kn_order_channels' => array_values(array_map(fn (string $name, string $channel): array => ['name' => $name, 'channel' => $channel], array_keys($base['order_channels']), $base['order_channels'])),
            'kn_categories' => array_map(fn (string $category): array => ['key' => $category, 'label' => $base['category_labels'][$category] ?? $category], array_values($base['categories'])),
            'kn_actions' => array_values(array_map(fn (string $action, string $label): array => ['key' => $action, 'label' => $label, 'hint' => $base['action_hints'][$action] ?? ''], array_keys($base['actions']), $base['actions'])),
            default => [],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $value
     */
    private function overlay(string $key, array $value): void
    {
        match ($key) {
            'kn_customer_types' => config(['knowledge.customer_types' => array_column($value, 'key')]),
            'kn_sales_channels' => config(['knowledge.sales_channels' => array_column($value, 'key')]),
            'kn_groups' => config(['knowledge.customer_groups' => collect($value)->mapWithKeys(fn (array $group): array => [$group['key'] => [
                'label' => $group['label'], 'customer_type' => $group['customer_type'] ?: null, 'sales_channel' => $group['sales_channel'] ?: null, 'status' => $group['status'] ?? CustomerGroup::ACTIVE,
            ]])->all()]),
            'kn_order_channels' => config(['knowledge.order_channels' => collect($value)->mapWithKeys(fn (array $row): array => [$row['name'] => $row['channel']])->all()]),
            'kn_categories' => config(['knowledge.categories' => array_column($value, 'key'), 'knowledge.category_labels' => array_column($value, 'label', 'key')]),
            'kn_actions' => config(['knowledge.actions' => array_column($value, 'label', 'key'), 'knowledge.action_hints' => array_filter(array_column($value, 'hint', 'key'), fn (mixed $hint): bool => is_string($hint) && $hint !== '')]),
            default => null,
        };
    }

    /**
     * The IDs of the documents that name a value in a field of their head (all documents, whatever their state).
     *
     * @return list<string>
     */
    public function documentsUsing(string $field, string $value): array
    {
        return app(KnowledgeLibrary::class)->all()
            ->filter(fn (KnowledgeDocument $document): bool => in_array($value, $field === 'actions' ? [...$document->list('actions'), ...$document->list('action')] : $document->list($field), true))
            ->map(fn (KnowledgeDocument $document): string => (string) ($document->id ?? $document->path))
            ->values()
            ->all();
    }

    /**
     * The documents a customer group does not get because of their restriction: the list to check before the
     * group is released. Binding rules come first.
     *
     * @return array{binding: list<array<string, mixed>>, others: list<array<string, mixed>>}
     */
    public function checklist(CustomerGroup $group): array
    {
        $binding = [];
        $others = [];

        foreach (app(KnowledgeLibrary::class)->usable() as $document) {
            $reason = $group->exclusionReason($document);

            if ($reason === null) {
                continue;
            }

            $row = ['id' => (string) $document->id, 'title' => (string) $document->title, 'type' => (string) $document->type, 'retrieval' => $document->retrieval(), 'reason' => $reason, 'path' => $document->path];

            if ($document->retrieval() === 'binding') {
                $binding[] = $row;
            } else {
                $others[] = $row;
            }
        }

        return ['binding' => $binding, 'others' => $others];
    }

    /**
     * The allowed values as a text for the AI chat of the authors.
     */
    public function copyText(): string
    {
        $lines = ['Erlaubte Werte für den Kopf der Wissensdokumente (Schlüssel – Name):', ''];
        $section = function (string $title, array $rows) use (&$lines): void {
            $lines[] = $title.':';

            foreach ($rows as $row) {
                $lines[] = '- '.$row['key'].' – '.$row['label'];
            }

            $lines[] = '';
        };

        $section('customer_types (Kundenarten)', $this->current('kn_customer_types'));
        $section('sales_channels (Vertriebskanäle)', $this->current('kn_sales_channels'));
        $section('categories (Fallkategorien)', $this->current('kn_categories'));
        $section('actions (Vorgänge)', $this->current('kn_actions'));
        $lines[] = 'Leere Listen bei customer_types und sales_channels bedeuten: gilt für alle.';

        return implode("\n", $lines);
    }

    private function store(): SettingsStore
    {
        return app(SettingsStore::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function current(string $key): array
    {
        return (array) $this->store()->value($key);
    }
}

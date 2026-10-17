<?php

namespace App\Settings;

use App\Knowledge\CustomerGroup;

/**
 * Cleans and checks the six value groups of the knowledge base (PROJ-50) and keeps them consistent: a customer
 * group names an existing customer type and channel, nothing is removed that a document still names, new or
 * changed groups wait for their release.
 */
final class KnowledgeValueRules
{
    public const string UNCLEAR = 'unclear';

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<string>}
     */
    public static function normalize(string $groupKey, mixed $input, bool $enforce = false): array
    {
        $rows = [];
        $seen = [];

        foreach (is_array($input) ? $input : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $clean = self::row($groupKey, $row);

            if ($clean === null) {
                continue;
            }

            $id = $groupKey === 'kn_order_channels' ? mb_strtolower($clean['name']) : $clean['key'];

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $rows[] = $clean;
        }

        if ($enforce && $groupKey === 'kn_groups') {
            $rows = self::enforceGroups($rows, (array) app(SettingsStore::class)->value('kn_groups'));
        }

        return [$rows, []];
    }

    /**
     * @return list<string>
     */
    public static function errors(string $groupKey, mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return ['Es muss mindestens ein Eintrag geben.'];
        }

        $errors = count($value) > 200 ? ['Höchstens 200 Einträge.'] : [];
        $keys = [];

        foreach ($value as $index => $row) {
            $label = 'Eintrag '.($index + 1);

            if ($groupKey === 'kn_order_channels') {
                if ($row['name'] === '' || mb_strlen($row['name']) > 80) {
                    $errors[] = "{$label}: Der EOCS-Kanalname fehlt oder ist länger als 80 Zeichen.";
                }

                if ($row['channel'] === '') {
                    $errors[] = "{$label}: Bitte einen Vertriebskanal wählen.";
                }

                continue;
            }

            if (preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $row['key']) !== 1) {
                $errors[] = "{$label}: Der Schlüssel „{$row['key']}“ besteht aus Kleinbuchstaben, Ziffern und „-“ (2 bis 40 Zeichen).";
            }

            if ($row['label'] === '' || mb_strlen($row['label']) > 80) {
                $errors[] = "{$label} „{$row['key']}“: Der Name fehlt oder ist länger als 80 Zeichen.";
            }

            if (isset($row['hint']) && mb_strlen($row['hint']) > 300) {
                $errors[] = "{$label} „{$row['key']}“: Der Hinweis ist länger als 300 Zeichen.";
            }

            if ($groupKey === 'kn_groups') {
                if (! in_array($row['status'], [CustomerGroup::ACTIVE, CustomerGroup::PREPARING, CustomerGroup::INACTIVE], true)) {
                    $errors[] = "{$label} „{$row['key']}“: Ungültiger Status.";
                }

                if (($row['customer_type'] === null) !== ($row['sales_channel'] === null)) {
                    $errors[] = "{$label} „{$row['key']}“: Kundenart und Vertriebskanal gehören zusammen; beide oder keines (nur „Noch unklar“).";
                }
            }

            $keys[] = $row['key'];
        }

        if ($groupKey === 'kn_groups') {
            $unclear = collect($value)->firstWhere('key', self::UNCLEAR);

            if ($unclear === null || $unclear['customer_type'] !== null || $unclear['sales_channel'] !== null || $unclear['status'] !== CustomerGroup::ACTIVE) {
                $errors[] = 'Die Gruppe „Noch unklar“ (unclear) muss bleiben, ohne Kundenart und Kanal und aktiv.';
            }
        }

        return $errors;
    }

    /**
     * Checks that span several groups; uses the saved value for a group that was not sent.
     *
     * @param  array<string, mixed>  $drafts  Cleaned values of the groups that were sent.
     * @return array<string, list<string>> Messages by settings group.
     */
    public static function crossErrors(array $drafts): array
    {
        $store = app(SettingsStore::class);
        $values = app(KnowledgeValues::class);
        $final = fn (string $key): array => (array) ($drafts[$key] ?? $store->value($key));
        $errors = [];
        $types = array_column($final('kn_customer_types'), 'key');
        $channels = array_column($final('kn_sales_channels'), 'key');

        foreach ($final('kn_groups') as $group) {
            if ($group['customer_type'] !== null && ! in_array($group['customer_type'], $types, true)) {
                $errors['kn_groups'][] = "Gruppe „{$group['key']}“: Die Kundenart „{$group['customer_type']}“ gibt es nicht.";
            }

            if ($group['sales_channel'] !== null && ! in_array($group['sales_channel'], $channels, true)) {
                $errors['kn_groups'][] = "Gruppe „{$group['key']}“: Den Vertriebskanal „{$group['sales_channel']}“ gibt es nicht.";
            }
        }

        $combinations = [];

        foreach ($final('kn_groups') as $group) {
            if ($group['customer_type'] === null) {
                continue;
            }

            $combination = $group['customer_type'].'|'.$group['sales_channel'];

            if (isset($combinations[$combination])) {
                $errors['kn_groups'][] = "Die Gruppen „{$combinations[$combination]}“ und „{$group['key']}“ haben dieselbe Kombination aus Kundenart und Kanal.";
            }

            $combinations[$combination] = $group['key'];
        }

        foreach ($final('kn_order_channels') as $row) {
            if (! in_array($row['channel'], $channels, true)) {
                $errors['kn_order_channels'][] = "„{$row['name']}“: Den Vertriebskanal „{$row['channel']}“ gibt es nicht.";
            }
        }

        if (array_key_exists('kn_groups', $drafts)) {
            $removed = array_diff(array_column((array) $store->value('kn_groups'), 'key'), array_column($drafts['kn_groups'], 'key'));

            foreach ($removed as $key) {
                $errors['kn_groups'][] = "Die Kundengruppe „{$key}“ wird nicht entfernt, sondern deaktiviert (Status).";
            }
        }

        $fields = ['kn_customer_types' => 'customer_types', 'kn_sales_channels' => 'sales_channels', 'kn_categories' => 'categories', 'kn_actions' => 'actions'];

        foreach ($fields as $groupKey => $field) {
            if (! array_key_exists($groupKey, $drafts)) {
                continue;
            }

            foreach (array_diff(array_column((array) $store->value($groupKey), 'key'), array_column($drafts[$groupKey], 'key')) as $removed) {
                $documents = $values->documentsUsing($field, $removed);

                if ($documents !== []) {
                    $errors[$groupKey][] = "„{$removed}“ wird noch von ".count($documents).' '.(count($documents) === 1 ? 'Dokument' : 'Dokumenten').' verwendet ('.implode(', ', array_slice($documents, 0, 8)).(count($documents) > 8 ? ' …' : '').') und kann nicht entfernt werden.';
                }

                $used = $groupKey === 'kn_customer_types' ? 'customer_type' : ($groupKey === 'kn_sales_channels' ? 'sales_channel' : null);

                if ($used !== null && collect($final('kn_groups'))->contains(fn (array $group): bool => $group[$used] === $removed)) {
                    $errors[$groupKey][] = "„{$removed}“ wird noch von einer Kundengruppe verwendet und kann nicht entfernt werden.";
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private static function row(string $groupKey, array $row): ?array
    {
        $text = fn (string $field): string => trim(preg_replace('/\s+/u', ' ', (string) ($row[$field] ?? '')) ?? '');

        if ($groupKey === 'kn_order_channels') {
            $name = $text('name');
            $channel = mb_strtolower($text('channel'));

            return $name === '' && $channel === '' ? null : ['name' => $name, 'channel' => $channel];
        }

        $key = mb_strtolower(trim((string) ($row['key'] ?? '')));
        $label = $text('label');

        if ($key === '' && $label === '' && $text('hint') === '') {
            return null;
        }

        $clean = ['key' => $key, 'label' => $label];

        if ($groupKey === 'kn_actions') {
            $clean['hint'] = $text('hint');
        }

        if ($groupKey === 'kn_groups') {
            $type = mb_strtolower($text('customer_type'));
            $channel = mb_strtolower($text('sales_channel'));
            $clean += [
                'customer_type' => $type === '' ? null : $type,
                'sales_channel' => $channel === '' ? null : $channel,
                'status' => $text('status') ?: CustomerGroup::ACTIVE,
                'released_by' => isset($row['released_by']) && $row['released_by'] !== '' ? (string) $row['released_by'] : null,
                'released_at' => isset($row['released_at']) && $row['released_at'] !== '' ? (string) $row['released_at'] : null,
                'confirmed_binding' => array_values(array_map('strval', (array) ($row['confirmed_binding'] ?? []))),
            ];
        }

        return $clean;
    }

    /**
     * New and changed groups wait for their release; "unclear" stays as it is.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $old
     * @return list<array<string, mixed>>
     */
    private static function enforceGroups(array $rows, array $old): array
    {
        $before = collect($old)->keyBy('key');

        return array_map(function (array $row) use ($before): array {
            if ($row['key'] === self::UNCLEAR) {
                return [...$row, 'customer_type' => null, 'sales_channel' => null, 'status' => CustomerGroup::ACTIVE];
            }

            $was = $before->get($row['key']);

            if ($was === null) {
                return [...$row, 'status' => CustomerGroup::PREPARING, 'released_by' => null, 'released_at' => null, 'confirmed_binding' => []];
            }

            $kept = ['released_by' => $was['released_by'] ?? null, 'released_at' => $was['released_at'] ?? null, 'confirmed_binding' => $was['confirmed_binding'] ?? []];

            if ($was['status'] === CustomerGroup::PREPARING) {
                return [...$row, ...$kept, 'status' => CustomerGroup::PREPARING];
            }

            if ($row['customer_type'] !== $was['customer_type'] || $row['sales_channel'] !== $was['sales_channel']) {
                return [...$row, 'status' => CustomerGroup::PREPARING, 'released_by' => null, 'released_at' => null, 'confirmed_binding' => []];
            }

            return [...$row, ...$kept, 'status' => in_array($row['status'], [CustomerGroup::ACTIVE, CustomerGroup::INACTIVE], true) ? $row['status'] : $was['status']];
        }, $rows);
    }
}

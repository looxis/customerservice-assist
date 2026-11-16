<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * The channel "ehemals qobos" and the customer group "Globus" (PROJ-89) are defaults of the app. Where the lists
 * of channels or customer groups were saved in the settings, the defaults no longer reach them: this adds both
 * there and gives the channels that still carry their old default name the name shown in the form.
 */
return new class extends Migration
{
    private const array RENAMED = ['fachhaendler' => ['Fachhändler', 'fachhaendler.looxis.de'], 'looxis-pro' => ['LOOXIS-Pro', 'LOOXIS Pro'], 'masterpics' => ['masterpics', 'API (masterpics)']];

    public function up(): void
    {
        $channels = Setting::query()->where('group_key', 'kn_sales_channels')->first();

        if ($channels !== null) {
            $rows = array_map(function (array $row): array {
                [$old, $new] = self::RENAMED[$row['key'] ?? ''] ?? [null, null];

                return ($row['label'] ?? null) === $old && $old !== null ? [...$row, 'label' => $new] : $row;
            }, (array) $channels->value);

            if (! in_array('qobos', array_column($rows, 'key'), true)) {
                $rows[] = ['key' => 'qobos', 'label' => 'ehemals qobos'];
            }

            $channels->update(['value' => array_values($rows)]);
        }

        $groups = Setting::query()->where('group_key', 'kn_groups')->first();

        if ($groups !== null && ! in_array('globus', array_column((array) $groups->value, 'key'), true)) {
            $rows = array_values((array) $groups->value);
            $position = array_search('unclear', array_column($rows, 'key'), true);
            array_splice($rows, $position === false ? count($rows) : $position, 0, [[
                'key' => 'globus', 'label' => 'Globus', 'customer_type' => 'b2b-reseller', 'sales_channel' => 'qobos',
                'status' => 'active', 'released_by' => null, 'released_at' => null, 'confirmed_binding' => [],
            ]]);
            $groups->update(['value' => $rows]);
        }
    }

    public function down(): void
    {
        // The saved lists stay as they are: both entries can be removed in the settings.
    }
};

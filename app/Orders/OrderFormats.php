<?php

namespace App\Orders;

use App\Settings\SettingsStore;

/**
 * The order number formats in force (PROJ-49): the ones saved on the settings page, else the defaults.
 * The first format that fits a number counts.
 */
class OrderFormats
{
    public function __construct(private readonly SettingsStore $settings) {}

    /**
     * @return list<OrderNumberFormat>
     */
    public function all(): array
    {
        return self::from($this->settings->value('order_formats'), $this->claimPrefix());
    }

    /**
     * The letters in front of a claim number ("R" for "R1-302-…"), from the settings.
     */
    public function claimPrefix(): string
    {
        $prefix = $this->settings->value('claim_prefix');

        return is_string($prefix) && $prefix !== '' ? $prefix : 'R';
    }

    /**
     * The format whose layout the Amazon notice uses for the order number.
     */
    public function amazon(): OrderNumberFormat
    {
        foreach ($this->all() as $format) {
            if ($format->amazon) {
                return $format;
            }
        }

        return self::from(self::defaults(), $this->claimPrefix())[0];
    }

    /**
     * @param  mixed  $value  The value of the settings group.
     * @return list<OrderNumberFormat>
     */
    public static function from(mixed $value, string $claimPrefix = 'R'): array
    {
        return array_values(array_map(fn (array $data): OrderNumberFormat => OrderNumberFormat::fromArray($data, $claimPrefix), array_filter(is_array($value) ? $value : [], 'is_array')));
    }

    /**
     * The formats as the app brought them.
     *
     * @return list<array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            ['key' => 'amazon', 'name' => 'Amazon', 'layout' => '###-#######-#######', 'prefix' => null, 'upper' => false, 'needs' => [], 'internal' => false, 'amazon' => true,
                'examples' => ['402-4907715-1581912'], 'counter_examples' => ['402-4907715-158191']],
            ['key' => 'vanilo', 'name' => 'looxis.de / Fachhändler', 'layout' => '***-****-****', 'prefix' => null, 'upper' => true, 'needs' => ['digit', 'letter'], 'internal' => false, 'amazon' => false,
                'examples' => ['7JI-0WC1-6M49'], 'counter_examples' => ['123-4567-8901']],
            ['key' => 'looxis-pro', 'name' => 'LOOXIS-Pro', 'layout' => '300#####', 'prefix' => 'BEST-PRO', 'upper' => false, 'needs' => [], 'internal' => false, 'amazon' => false,
                'examples' => ['30019578', 'BEST-PRO30019578'], 'counter_examples' => ['30119578']],
            ['key' => 'looxis-fr', 'name' => 'looxis.fr', 'layout' => '700######', 'prefix' => null, 'upper' => false, 'needs' => [], 'internal' => false, 'amazon' => false,
                'examples' => ['700411247'], 'counter_examples' => ['70041124']],
            ['key' => 'masterpics', 'name' => 'masterpics', 'layout' => '**********', 'prefix' => null, 'upper' => false, 'needs' => ['digit', 'lower', 'upper'], 'internal' => false, 'amazon' => false,
                'examples' => ['LmagQ9PV25'], 'counter_examples' => ['lmagq9pv25']],
            ['key' => 'eocs-id', 'name' => 'EOCS-ID', 'layout' => '######', 'prefix' => null, 'upper' => false, 'needs' => [], 'internal' => true, 'amazon' => false,
                'examples' => ['753194'], 'counter_examples' => ['75319']],
        ];
    }
}

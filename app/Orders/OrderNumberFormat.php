<?php

namespace App\Orders;

/**
 * Known order number formats. The single place that knows them; the n8n AI
 * workflow may replace this detection later.
 */
enum OrderNumberFormat: string
{
    case Amazon = 'amazon';
    case Vanilo = 'vanilo';
    case LooxisPro = 'looxis-pro';
    case LooxisFr = 'looxis-fr';
    case Masterpics = 'masterpics';
    case EocsId = 'eocs-id';

    public function label(): string
    {
        return match ($this) {
            self::Amazon => 'Amazon',
            self::Vanilo => 'looxis.de / Fachhändler',
            self::LooxisPro => 'LOOXIS-Pro',
            self::LooxisFr => 'looxis.fr',
            self::Masterpics => 'masterpics',
            self::EocsId => 'EOCS-ID',
        };
    }

    /**
     * Pattern for finding the format in running text; group 1 is the number.
     */
    public function pattern(): string
    {
        return match ($this) {
            self::Amazon => '/(?<![\w-])(\d{3}-\d{7}-\d{7})(?![\w-])/u',
            self::Vanilo => '/(?<![\w-])((?=[A-Z0-9-]*\d)(?=[A-Z0-9-]*[A-Z])[A-Z0-9]{3}-[A-Z0-9]{4}-[A-Z0-9]{4})(?![\w-])/u',
            self::LooxisPro => '/(?<![\w-])(?:BEST-PRO)?(300\d{5})(?![\w-])/u',
            self::LooxisFr => '/(?<![\w-])(700\d{6})(?![\w-])/u',
            self::Masterpics => '/(?<![\w-])((?=[A-Za-z0-9]*[a-z])(?=[A-Za-z0-9]*[A-Z])(?=[A-Za-z0-9]*\d)[A-Za-z0-9]{10})(?![\w-])/u',
            self::EocsId => '/(?<![\w.,-])(\d{6})(?![\w.,-])/u',
        };
    }
}

<?php

namespace App\Zammad;

enum ArticleKind: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Vom Kunden',
            self::Agent => 'Von uns',
            self::Internal => 'Intern',
        };
    }
}

<?php

namespace App\ProductForge;

enum ProductForgeProblem: string
{
    case Misconfigured = 'misconfigured';
    case NotFound = 'not_found';
    case RateLimited = 'rate_limited';
    case Unavailable = 'unavailable';
    case Timeout = 'timeout';
    case UnexpectedResponse = 'unexpected_response';

    public function message(): string
    {
        return match ($this) {
            self::Misconfigured => 'Die Verbindung zu den Produktinformationen ist nicht eingerichtet oder ungültig. Bitte wende dich an den Entwickler.',
            self::NotFound => 'Zu diesem Produkt sind keine Produktinformationen hinterlegt.',
            self::RateLimited => 'Die Produktinformationen wurden zu oft abgefragt. Bitte gleich erneut versuchen.',
            self::Unavailable => 'Die Produktinformationen sind gerade nicht erreichbar.',
            self::Timeout => 'Die Produktinformationen haben nicht rechtzeitig geantwortet.',
            self::UnexpectedResponse => 'Die Produktinformationen hatten ein unerwartetes Format.',
        };
    }

    public function canRetry(): bool
    {
        return in_array($this, [self::RateLimited, self::Unavailable, self::Timeout], true);
    }
}

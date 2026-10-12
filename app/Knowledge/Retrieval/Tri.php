<?php

namespace App\Knowledge\Retrieval;

/**
 * A three-valued answer (PROJ-36): yes, no or unknown. A missing fact is
 * "unknown", never a silent "no".
 */
enum Tri: string
{
    case Yes = 'yes';
    case No = 'no';
    case Unknown = 'unknown';

    public static function fromBool(?bool $value): self
    {
        return match ($value) {
            true => self::Yes,
            false => self::No,
            null => self::Unknown,
        };
    }

    /**
     * AND: no as soon as one part is no, otherwise unknown as soon as one part is unknown.
     */
    public function and(self $other): self
    {
        return match (true) {
            $this === self::No, $other === self::No => self::No,
            $this === self::Unknown, $other === self::Unknown => self::Unknown,
            default => self::Yes,
        };
    }

    /**
     * OR: yes as soon as one part is yes, otherwise unknown as soon as one part is unknown.
     */
    public function or(self $other): self
    {
        return match (true) {
            $this === self::Yes, $other === self::Yes => self::Yes,
            $this === self::Unknown, $other === self::Unknown => self::Unknown,
            default => self::No,
        };
    }

    public function not(): self
    {
        return match ($this) {
            self::Yes => self::No,
            self::No => self::Yes,
            self::Unknown => self::Unknown,
        };
    }

    /**
     * @param  list<self>  $values
     */
    public static function all(array $values): self
    {
        return array_reduce($values, fn (self $carry, self $value): self => $carry->and($value), self::Yes);
    }

    /**
     * @param  list<self>  $values
     */
    public static function any(array $values): self
    {
        return array_reduce($values, fn (self $carry, self $value): self => $carry->or($value), self::No);
    }

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'erfüllt',
            self::No => 'nicht erfüllt',
            self::Unknown => 'unbekannt',
        };
    }
}

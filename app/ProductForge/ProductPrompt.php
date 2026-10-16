<?php

namespace App\ProductForge;

/**
 * The product section of one prompt and the record of what it contains.
 */
final readonly class ProductPrompt
{
    /**
     * @param  array<string, mixed>  $record  What was sent and what was left out, stored with the analysis.
     * @param  list<string>  $notes  Hints for the employee, shown with the result.
     */
    public function __construct(public string $text, public array $record, public array $notes) {}

    public static function none(): self
    {
        return new self('', [], []);
    }

    public function characters(): int
    {
        return mb_strlen($this->text);
    }
}

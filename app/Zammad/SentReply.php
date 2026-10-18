<?php

namespace App\Zammad;

final readonly class SentReply
{
    public function __construct(public ?int $articleId, public string $zammadUser) {}
}

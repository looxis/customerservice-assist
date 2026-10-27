<?php

namespace App\Zammad;

/**
 * A ticket the app created in Zammad (PROJ-64).
 */
final readonly class CreatedTicket
{
    public function __construct(public int $id, public string $number, public string $url) {}
}

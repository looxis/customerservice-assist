<?php

namespace App\ProductForge;

final readonly class ForgeSearchPage
{
    /**
     * @param  list<ForgeSearchHit>  $hits
     */
    public function __construct(public array $hits, public int $page, public int $perPage, public int $total, public int $lastPage) {}
}

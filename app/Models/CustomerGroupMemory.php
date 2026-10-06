<?php

namespace App\Models;

use Database\Factories\CustomerGroupMemoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer group last chosen for a Zammad customer or organization; holds no case content.
 */
#[Fillable(['customer_key', 'customer_group'])]
class CustomerGroupMemory extends Model
{
    /** @use HasFactory<CustomerGroupMemoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

        ];
    }
}

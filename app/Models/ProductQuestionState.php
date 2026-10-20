<?php

namespace App\Models;

use Database\Factories\ProductQuestionStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * What an admin decided about a topic of product questions (PROJ-57): done or ignored. A topic
 * without a row is open.
 */
#[Fillable(['product_key', 'topic', 'status', 'changed_by', 'changed_at'])]
class ProductQuestionState extends Model
{
    /** @use HasFactory<ProductQuestionStateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }
}

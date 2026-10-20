<?php

namespace App\Models;

use Database\Factories\ProductQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A detail question of a customer about a product that the product information does not answer
 * (PROJ-57). Counted silently; the wording is encrypted, the topic (a short keyword) is not, so the
 * admin page can group by it.
 */
#[Fillable(['analysis_id', 'ticket_number', 'product_key', 'product_name', 'topic', 'question', 'customer_group', 'test_run'])]
class ProductQuestion extends Model
{
    /** @use HasFactory<ProductQuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question' => 'encrypted',
            'test_run' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Analysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}

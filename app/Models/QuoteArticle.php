<?php

namespace App\Models;

use Database\Factories\QuoteArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An article of the offer calculator (PROJ-34): shop price (gross) and internal costs. The costs are complete
 * when the purchase cost and the machine time are entered.
 */
#[Fillable(['sku', 'name', 'category', 'shop_price_gross', 'active', 'raw_articles', 'purchase_cost', 'machine_minutes', 'labor_minutes', 'hour_rate', 'machine_rate', 'pack_cost', 'ship_cost', 'other_cost', 'target_margin', 'vat_rate', 'costs_updated_by'])]
class QuoteArticle extends Model
{
    /** @use HasFactory<QuoteArticleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shop_price_gross' => 'float',
            'active' => 'boolean',
            'raw_articles' => 'array',
            'purchase_cost' => 'float',
            'machine_minutes' => 'float',
            'labor_minutes' => 'float',
            'hour_rate' => 'float',
            'machine_rate' => 'float',
            'pack_cost' => 'float',
            'ship_cost' => 'float',
            'other_cost' => 'float',
            'target_margin' => 'float',
            'vat_rate' => 'float',
        ];
    }

    public function hasCompleteCosts(): bool
    {
        return $this->purchase_cost !== null && $this->machine_minutes !== null;
    }

    /**
     * What the calculator page needs for this article.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category,
            'price' => $this->shop_price_gross,
            'complete' => $this->hasCompleteCosts(),
            'raw_articles' => $this->raw_articles ?? [],
            'purchase_cost' => $this->purchase_cost,
            'machine_minutes' => $this->machine_minutes,
            'labor_minutes' => $this->labor_minutes,
            'hour_rate' => $this->hour_rate,
            'machine_rate' => $this->machine_rate,
            'pack_cost' => $this->pack_cost,
            'ship_cost' => $this->ship_cost,
            'other_cost' => $this->other_cost,
            'target_margin' => $this->target_margin,
            'vat_rate' => $this->vat_rate,
        ];
    }
}

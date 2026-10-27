<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Articles of the offer calculator (PROJ-34): the shop price and the internal costs per article. Costs are
 * null until an admin has entered them; the calculator then shows "costs incomplete" instead of a margin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_articles', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('shop_price_gross', 10, 2);
            $table->boolean('active')->default(true);
            $table->json('raw_articles')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->decimal('machine_minutes', 8, 2)->nullable();
            $table->decimal('labor_minutes', 8, 2)->default(3);
            $table->decimal('hour_rate', 8, 2)->default(60);
            $table->decimal('machine_rate', 8, 2)->default(60);
            $table->decimal('pack_cost', 10, 2)->default(0);
            $table->decimal('ship_cost', 10, 2)->default(0);
            $table->decimal('other_cost', 10, 2)->default(0);
            $table->decimal('target_margin', 5, 2)->default(30);
            $table->decimal('vat_rate', 5, 2)->default(19);
            $table->string('costs_updated_by', 60)->nullable();
            $table->timestamps();

            $table->index(['active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_articles');
    }
};

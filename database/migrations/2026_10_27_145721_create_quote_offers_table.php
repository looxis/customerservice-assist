<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved offers of the offer calculator (PROJ-34). The items are a snapshot (name, price, discount), so a later
 * price change does not alter an offer that was already made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number', 20)->unique();
            $table->date('created_on');
            $table->date('valid_until');
            $table->string('company')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->text('note')->nullable();
            $table->json('items');
            $table->string('ticket_number', 20)->nullable();
            $table->string('created_by', 60);
            $table->timestamps();

            $table->index('created_on');
            $table->index('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_offers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_case_choices', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->string('customer_group', 40);
            $table->json('products');
            $table->string('staff_name', 60);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_case_choices');
    }
};

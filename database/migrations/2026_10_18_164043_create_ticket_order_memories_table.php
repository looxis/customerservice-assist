<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order numbers typed in by hand and found in EOCS, remembered per ticket (PROJ-7). Kept apart from the
     * analyses, so deleting an analysis does not lose them.
     */
    public function up(): void
    {
        Schema::create('ticket_order_memories', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->json('orders');
            $table->string('staff_name', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_order_memories');
    }
};

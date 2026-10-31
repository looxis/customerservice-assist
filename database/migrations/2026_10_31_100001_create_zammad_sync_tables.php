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
        Schema::create('zammad_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20)->default('running');
            $table->timestamp('covered_until')->nullable();
            $table->unsignedInteger('tickets_seen')->default(0);
            $table->unsignedInteger('tickets_failed')->default(0);
            $table->string('error_code', 40)->nullable();

            $table->index(['type', 'covered_until']);
            $table->index(['type', 'finished_at']);
            $table->index('started_at');
        });

        Schema::create('zammad_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id', 100)->unique();
            $table->string('trigger_name', 120)->nullable();
            $table->unsignedBigInteger('zammad_ticket_id');
            $table->string('status', 20)->default('received');
            $table->string('error_code', 40)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();

            $table->index(['zammad_ticket_id', 'status']);
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zammad_webhook_deliveries');
        Schema::dropIfExists('zammad_sync_runs');
    }
};

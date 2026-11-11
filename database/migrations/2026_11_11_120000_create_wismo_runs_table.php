<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (PROJ-82: the log of the automatic WISMO replies, one row per ticket).
     */
    public function up(): void
    {
        Schema::create('wismo_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zammad_id')->unique();
            $table->string('ticket_number', 20)->index();
            $table->string('mode', 8)->default('on');
            $table->string('result', 16)->index();
            $table->string('reason', 255)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('order_number', 60)->nullable();
            $table->string('carrier', 60)->nullable();
            $table->string('tracking_number', 60)->nullable();
            $table->boolean('delivery_problem')->default(false);
            $table->string('delivery_problem_reason', 255)->nullable();
            $table->boolean('pickup_address_used')->default(false);
            $table->unsignedSmallInteger('stuck_workdays')->nullable();
            $table->string('prompt_version', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->longText('reply_text')->nullable();
            $table->longText('reply_text_de')->nullable();
            $table->timestamp('sending_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['result', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wismo_runs');
    }
};

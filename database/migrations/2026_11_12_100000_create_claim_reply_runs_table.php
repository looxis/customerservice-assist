<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (PROJ-85: the log of the automatic first replies to complaints, one row per ticket).
     */
    public function up(): void
    {
        Schema::create('claim_reply_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zammad_id')->unique();
            $table->string('ticket_number', 20)->index();
            $table->string('mode', 8)->default('on');
            $table->string('result', 16)->index();
            $table->string('reason', 255)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('order_number', 60)->nullable();
            $table->unsignedSmallInteger('days_since_shipped')->nullable();
            $table->unsignedSmallInteger('photo_count')->nullable();
            $table->string('scenario', 32)->nullable();
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
        Schema::dropIfExists('claim_reply_runs');
    }
};

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
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('ticket_number', 20)->index();
            $table->string('scope_key', 60);
            $table->string('status', 20);
            $table->string('error', 40)->nullable();
            $table->string('staff_name', 60);
            $table->timestamp('test_until')->nullable();
            $table->string('customer_group', 40)->nullable();
            $table->json('products')->nullable();
            $table->string('variant', 30)->nullable();
            $table->string('category', 40)->nullable();
            $table->string('assessment', 20)->nullable();
            $table->string('confidence', 10)->nullable();
            $table->json('actions')->nullable();
            $table->json('knowledge_ids')->nullable();
            $table->json('knowledge_fingerprints')->nullable();
            $table->string('provider', 30)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('prompt_version', 60)->nullable();
            $table->string('summary_prompt_version', 60)->nullable();
            $table->string('knowledge_state', 120)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedTinyInteger('attempts')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('content_purged_at')->nullable();
            $table->timestamp('content_deleted_at')->nullable();
            $table->string('content_deleted_by', 60)->nullable();
            $table->timestamps();

            $table->index(['scope_key', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};

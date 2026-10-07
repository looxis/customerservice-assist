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
        Schema::create('knowledge_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->nullable()->constrained('analyses')->nullOnDelete();
            $table->string('ticket_number', 20)->index();
            $table->string('staff_name', 60);
            $table->string('customer_group', 40)->nullable();
            $table->json('products')->nullable();
            $table->boolean('test_run')->default(false);
            $table->string('gap_topic_hash', 64)->nullable();
            $table->text('content')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('resolved_by', 60)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('knowledge_id', 40)->nullable();
            $table->timestamp('content_purged_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['analysis_id', 'gap_topic_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_gaps');
    }
};

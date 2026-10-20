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
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->nullable()->constrained('analyses')->nullOnDelete();
            $table->string('ticket_number', 20);
            $table->string('product_key', 80)->default('');
            $table->string('product_name', 160)->nullable();
            $table->string('topic', 80);
            $table->text('question')->nullable();
            $table->string('customer_group', 40)->nullable();
            $table->boolean('test_run')->default(false);
            $table->timestamps();

            $table->unique(['ticket_number', 'product_key', 'topic', 'test_run'], 'product_questions_case_unique');
            $table->index(['product_key', 'topic', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_questions');
    }
};

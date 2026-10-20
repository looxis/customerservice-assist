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
        Schema::create('product_question_states', function (Blueprint $table) {
            $table->id();
            $table->string('product_key', 80)->default('');
            $table->string('topic', 80);
            $table->string('status', 20);
            $table->string('changed_by', 60);
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->unique(['product_key', 'topic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_question_states');
    }
};

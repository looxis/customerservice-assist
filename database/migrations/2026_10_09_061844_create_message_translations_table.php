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
        Schema::create('message_translations', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->index();
            $table->unsignedBigInteger('article_id')->unique();
            $table->string('fingerprint', 64);
            $table->string('status', 20);
            $table->string('language', 40)->nullable();
            $table->longText('content')->nullable();
            $table->string('staff_name', 60);
            $table->string('model', 80)->nullable();
            $table->string('prompt_version', 60)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_translations');
    }
};

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
        Schema::table('analyses', function (Blueprint $table) {
            $table->string('feedback_level', 20)->nullable()->after('attempts');
            $table->string('feedback_suggested', 20)->nullable()->after('feedback_level');
            $table->string('feedback_by', 60)->nullable()->after('feedback_suggested');
            $table->timestamp('feedback_at')->nullable()->after('feedback_by');
            $table->text('feedback_comment')->nullable()->after('feedback_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['feedback_level', 'feedback_suggested', 'feedback_by', 'feedback_at', 'feedback_comment']);
        });
    }
};

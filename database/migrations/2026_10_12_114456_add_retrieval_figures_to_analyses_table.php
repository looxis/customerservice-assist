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
            $table->unsignedInteger('knowledge_characters')->nullable()->after('knowledge_state');
            $table->unsignedSmallInteger('documents_retrieved')->nullable()->after('knowledge_characters');
            $table->unsignedSmallInteger('documents_omitted')->nullable()->after('documents_retrieved');
            $table->unsignedTinyInteger('passes')->nullable()->after('documents_omitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['knowledge_characters', 'documents_retrieved', 'documents_omitted', 'passes']);
        });
    }
};

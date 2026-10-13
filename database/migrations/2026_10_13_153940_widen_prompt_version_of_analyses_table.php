<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The version of the prompt is a chain of the analysis prompt and the rules added to it (retrieval,
     * question without ticket); with all three it no longer fits 60 characters (PROJ-40, PROJ-44).
     */
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->string('prompt_version', 160)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->string('prompt_version', 60)->nullable()->change();
        });
    }
};

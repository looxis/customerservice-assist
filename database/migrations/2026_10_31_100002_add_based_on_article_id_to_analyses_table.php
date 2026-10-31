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
            $table->unsignedBigInteger('based_on_article_id')->nullable()->after('scope_key');
            $table->index(['ticket_number', 'based_on_article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropIndex(['ticket_number', 'based_on_article_id']);
            $table->dropColumn('based_on_article_id');
        });
    }
};

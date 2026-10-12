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
            $table->string('retrieval_version', 40)->nullable()->after('prompt_version');
            $table->string('rule_check', 20)->nullable()->after('passes');
            $table->string('rule_check_by', 60)->nullable()->after('rule_check');
            $table->timestamp('rule_check_at')->nullable()->after('rule_check_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['retrieval_version', 'rule_check', 'rule_check_by', 'rule_check_at']);
        });
    }
};

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
            $table->string('router_status', 20)->nullable()->after('rule_check_at');
            $table->unsignedSmallInteger('router_additions')->nullable()->after('router_status');
            $table->unsignedInteger('router_characters')->nullable()->after('router_additions');
            $table->unsignedInteger('router_duration_ms')->nullable()->after('router_characters');
            $table->unsignedInteger('router_input_tokens')->nullable()->after('router_duration_ms');
            $table->unsignedInteger('router_output_tokens')->nullable()->after('router_input_tokens');

            $table->index('router_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropIndex(['router_status']);
            $table->dropColumn(['router_status', 'router_additions', 'router_characters', 'router_duration_ms', 'router_input_tokens', 'router_output_tokens']);
        });
    }
};

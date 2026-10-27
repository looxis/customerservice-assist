<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers that sending a return label to the customer was started (PROJ-64): after an unclear answer of Zammad
 * the app does not send the same label again without checking. The target is a ticket number or "new".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_labels', function (Blueprint $table) {
            $table->timestamp('send_started_at')->nullable()->after('error');
            $table->string('send_started_target', 20)->nullable()->after('send_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('return_labels', function (Blueprint $table) {
            $table->dropColumn(['send_started_at', 'send_started_target']);
        });
    }
};

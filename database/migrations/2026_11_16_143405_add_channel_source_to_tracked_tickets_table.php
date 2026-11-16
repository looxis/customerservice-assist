<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who set the sales channel of a ticket (PROJ-89): an employee in the proposal form, or the app from the order or
 * the sender. A channel the app recognised never replaces the choice of an employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracked_tickets', function (Blueprint $table) {
            $table->string('channel_source', 10)->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('tracked_tickets', function (Blueprint $table) {
            $table->dropColumn('channel_source');
        });
    }
};

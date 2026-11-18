<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who is working on a ticket right now (PROJ-96). One row per ticket; a row whose last activity is
 * older than the lock time counts as free and is simply overwritten by the next lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_locks', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->string('staff_name');
            $table->timestamp('last_activity_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_locks');
    }
};

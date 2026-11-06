<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (PROJ-77: automatic reply with the tracking number).
     */
    public function up(): void
    {
        Schema::table('ticket_todos', function (Blueprint $table) {
            $table->boolean('auto_send')->default(false);
            $table->string('customer_language', 40)->nullable();
            $table->string('auto_state', 16)->nullable()->index();
            $table->string('auto_reason', 255)->nullable();
            $table->unsignedSmallInteger('auto_checks')->default(0);
            $table->unsignedSmallInteger('auto_errors')->default(0);
            $table->timestamp('auto_last_check_at')->nullable();
            $table->timestamp('auto_sending_at')->nullable();
            $table->timestamp('auto_sent_at')->nullable();
            $table->text('auto_tracking')->nullable();
            $table->text('auto_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_todos', function (Blueprint $table) {
            $table->dropColumn(['auto_send', 'customer_language', 'auto_state', 'auto_reason', 'auto_checks', 'auto_errors', 'auto_last_check_at', 'auto_sending_at', 'auto_sent_at', 'auto_tracking', 'auto_text']);
        });
    }
};

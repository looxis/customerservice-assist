<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subject and sender of the first customer message (PROJ-78): both help the language model and the admin to
     * see the occasion of a ticket. Encrypted like the text, emptied together with it.
     */
    public function up(): void
    {
        Schema::table('labeling_tickets', function (Blueprint $table) {
            $table->text('subject')->nullable()->after('text');
            $table->text('sender')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('labeling_tickets', function (Blueprint $table) {
            $table->dropColumn(['subject', 'sender']);
        });
    }
};

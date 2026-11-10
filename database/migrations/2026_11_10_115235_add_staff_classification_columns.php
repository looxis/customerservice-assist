<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classification of new tickets by the staff (PROJ-80): who was unsure about a ticket (a note, not a decision),
     * and whether the category in the ticket list was confirmed by a person or is a proposal of the model.
     */
    public function up(): void
    {
        Schema::table('labeling_tickets', function (Blueprint $table) {
            $table->string('staff_unsure_by', 60)->nullable()->after('decided_at');
            $table->timestamp('staff_unsure_at')->nullable()->after('staff_unsure_by');
        });

        Schema::table('tracked_tickets', function (Blueprint $table) {
            $table->boolean('category_confirmed')->default(false)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('labeling_tickets', function (Blueprint $table) {
            $table->dropColumn(['staff_unsure_by', 'staff_unsure_at']);
        });

        Schema::table('tracked_tickets', function (Blueprint $table) {
            $table->dropColumn('category_confirmed');
        });
    }
};

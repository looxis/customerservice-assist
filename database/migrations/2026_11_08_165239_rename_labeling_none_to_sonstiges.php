<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "keine" becomes the category "sonstiges" (PROJ-78): decisions and reviews carry the new value, a proposal
     * without category (the model answered "keine") gets it too.
     */
    public function up(): void
    {
        DB::table('labeling_tickets')->where('decision', 'keine')->update(['decision' => 'sonstiges']);
        DB::table('labeling_reviews')->where('decision', 'keine')->update(['decision' => 'sonstiges']);
        DB::table('labeling_predictions')->whereNull('category')->update(['category' => 'sonstiges']);
    }

    public function down(): void
    {
        DB::table('labeling_tickets')->where('decision', 'sonstiges')->update(['decision' => 'keine']);
        DB::table('labeling_reviews')->where('decision', 'sonstiges')->update(['decision' => 'keine']);
        DB::table('labeling_predictions')->where('category', 'sonstiges')->update(['category' => null]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The country of the sender of a return label (PROJ-64, returns from abroad): decides where the customer hands in
 * the parcel and is shown with the label. Only the two-letter code, never the address. Labels that exist are German.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_labels', function (Blueprint $table) {
            $table->char('country', 2)->default('DE')->after('eocs_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('return_labels', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};

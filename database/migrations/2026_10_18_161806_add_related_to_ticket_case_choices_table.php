<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The related tickets chosen for a ticket are remembered like the customer group (PROJ-52).
     */
    public function up(): void
    {
        Schema::table('ticket_case_choices', function (Blueprint $table): void {
            $table->json('related')->nullable()->after('products');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_case_choices', function (Blueprint $table): void {
            $table->dropColumn('related');
        });
    }
};

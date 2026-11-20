<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (PROJ-99: the case of the shipment read from the whole tracking history, its milestones and
     * whether the app closed the ticket with the reply; kept for later statistics).
     */
    public function up(): void
    {
        Schema::table('wismo_runs', function (Blueprint $table) {
            $table->string('shipment_case', 30)->nullable()->after('tracking_number')->index();
            $table->json('shipment_history')->nullable()->after('shipment_case');
            $table->boolean('auto_closed')->default(false)->after('result')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wismo_runs', function (Blueprint $table) {
            $table->dropIndex(['shipment_case']);
            $table->dropIndex(['auto_closed']);
            $table->dropColumn(['shipment_case', 'shipment_history', 'auto_closed']);
        });
    }
};

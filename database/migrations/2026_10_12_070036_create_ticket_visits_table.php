<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_visits', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->text('customer_name')->nullable();
            $table->text('title')->nullable();
            $table->timestamp('first_opened_at');
            $table->string('first_opened_by', 60)->nullable();
            $table->timestamp('last_opened_at')->index();
            $table->string('last_opened_by', 60)->nullable();
            $table->unsignedInteger('opened_count')->default(1);
            $table->timestamps();
        });

        $this->backfill();
    }

    /**
     * Tickets that were analysed or translated before this table existed get
     * an entry at the time of their first work; whether they were only opened
     * earlier is not known.
     */
    private function backfill(): void
    {
        $first = DB::table('analyses')->whereNull('test_until')->select('ticket_number', 'staff_name as staff', 'created_at')
            ->unionAll(DB::table('message_translations')->select('ticket_number', 'staff_name as staff', 'created_at'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('ticket_number');

        foreach ($first as $number => $rows) {
            $last = $rows->last();
            $now = now();

            DB::table('ticket_visits')->insert([
                'ticket_number' => (string) $number,
                'first_opened_at' => $rows->first()->created_at,
                'first_opened_by' => $rows->first()->staff ?: null,
                'last_opened_at' => $last->created_at,
                'last_opened_by' => $last->staff ?: null,
                'opened_count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_visits');
    }
};

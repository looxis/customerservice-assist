<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Empties the queue "Stichprobe" (PROJ-78): its open tickets were proposed by gpt-5.5 and older prompts, and the
     * release now counts only samples of the current model. They become clear proposals without review, as if they
     * had not been drawn; only tickets classified from now on are drawn into the sample.
     */
    public function up(): void
    {
        DB::table('labeling_predictions')
            ->where('queue', 'stichprobe')
            ->whereIn('label_ticket_id', DB::table('labeling_tickets')->select('id')->whereNull('decision'))
            ->update(['queue' => null]);
    }

    /**
     * Which proposals were drawn is not kept, so there is nothing to restore.
     */
    public function down(): void {}
};

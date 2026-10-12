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
        Schema::table('analyses', function (Blueprint $table) {
            $table->timestamp('reply_edited_at')->nullable()->after('feedback_comment');
            $table->timestamp('reply_copied_at')->nullable()->after('reply_edited_at');
            $table->string('reply_copied_by', 60)->nullable()->after('reply_copied_at');
            $table->boolean('reply_copied_changed')->nullable()->after('reply_copied_by');
        });

        $this->backfillEdits();
    }

    /**
     * The time of an earlier edit is only inside the encrypted content.
     */
    private function backfillEdits(): void
    {
        DB::table('analyses')->whereNotNull('content')->orderBy('id')->select('id', 'content')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                try {
                    $content = json_decode(decrypt($row->content, false), true);
                } catch (Throwable) {
                    continue;
                }

                $at = is_array($content) ? ($content['reply_edit']['at'] ?? null) : null;

                if (is_string($at)) {
                    DB::table('analyses')->where('id', $row->id)->update(['reply_edited_at' => date('Y-m-d H:i:s', (int) strtotime($at))]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['reply_edited_at', 'reply_copied_at', 'reply_copied_by', 'reply_copied_changed']);
        });
    }
};

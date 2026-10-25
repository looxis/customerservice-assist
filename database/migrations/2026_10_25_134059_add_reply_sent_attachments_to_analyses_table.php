<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PROJ-61: names and sizes of the attachments that went with the sent reply (the files themselves are not kept).
     */
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->string('reply_sent_attachments', 1000)->nullable()->after('reply_sent_article_id');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn('reply_sent_attachments');
        });
    }
};

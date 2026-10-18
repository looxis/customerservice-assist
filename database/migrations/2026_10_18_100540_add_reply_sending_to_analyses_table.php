<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sending the reply from the app (PROJ-19): who, when, with which subject and status, the Zammad article.
     * "reply_send_started_at" is the atomic marker against a double send.
     */
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table): void {
            $table->timestamp('reply_send_started_at')->nullable()->after('reply_copied_changed');
            $table->timestamp('reply_sent_at')->nullable()->after('reply_send_started_at');
            $table->string('reply_sent_by', 60)->nullable()->after('reply_sent_at');
            $table->string('reply_sent_zammad_user', 190)->nullable()->after('reply_sent_by');
            $table->string('reply_sent_subject', 300)->nullable()->after('reply_sent_zammad_user');
            $table->string('reply_sent_state', 40)->nullable()->after('reply_sent_subject');
            $table->unsignedBigInteger('reply_sent_article_id')->nullable()->after('reply_sent_state');
            $table->index('reply_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table): void {
            $table->dropIndex(['reply_sent_at']);
            $table->dropColumn(['reply_send_started_at', 'reply_sent_at', 'reply_sent_by', 'reply_sent_zammad_user', 'reply_sent_subject', 'reply_sent_state', 'reply_sent_article_id']);
        });
    }
};

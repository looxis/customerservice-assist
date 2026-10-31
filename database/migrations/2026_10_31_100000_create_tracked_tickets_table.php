<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tracked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zammad_id')->unique();
            $table->string('number', 20)->unique();
            $table->text('title')->nullable();
            $table->string('state', 40);
            $table->boolean('is_closed')->default(false);
            $table->timestamp('pending_until')->nullable();
            $table->string('group_name', 160)->nullable();
            $table->unsignedBigInteger('owner_zammad_id')->nullable();
            $table->string('owner_name', 160)->nullable();
            $table->unsignedBigInteger('customer_zammad_id')->nullable();
            $table->unsignedBigInteger('organization_zammad_id')->nullable();
            $table->dateTime('zammad_created_at', 3)->nullable();
            $table->dateTime('zammad_updated_at', 3);
            $table->dateTime('last_activity_at', 3);
            $table->dateTime('last_contact_customer_at', 3)->nullable();
            $table->unsignedBigInteger('last_customer_article_id')->nullable();
            $table->dateTime('last_customer_article_at', 3)->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->string('sync_status', 20)->default('synced');
            $table->timestamp('sync_problem_since')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->string('channel', 40)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('processing_state', 30)->default('not_analyzed');
            $table->unsignedBigInteger('processed_article_id')->nullable();
            $table->timestamps();

            $table->index(['is_closed', 'last_activity_at']);
            $table->index(['owner_zammad_id', 'is_closed', 'last_activity_at']);
            $table->index(['state', 'last_activity_at']);
            $table->index('channel');
            $table->index('category');
            $table->index('processing_state');
            $table->index('zammad_updated_at');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracked_tickets');
    }
};

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
        Schema::create('ticket_todos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zammad_id')->index();
            $table->string('ticket_number', 20)->index();
            $table->text('description');
            $table->string('assignee', 60)->nullable()->index();
            $table->date('due_on')->nullable();
            $table->string('kind', 16)->default('direct');
            $table->string('waiting_for', 255)->nullable();
            $table->string('status', 20)->default('open');
            $table->boolean('notify_customer')->default(false);
            $table->text('notify_hint')->nullable();
            $table->string('notify_analysis_uuid', 36)->nullable()->index();
            $table->boolean('notify_skipped')->default(false);
            $table->string('source', 16)->default('manual');
            $table->string('source_analysis_uuid', 36)->nullable();
            $table->string('external_ref', 120)->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->string('created_by', 60)->nullable();
            $table->string('updated_by', 60)->nullable();
            $table->string('completed_by', 60)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_todos');
    }
};

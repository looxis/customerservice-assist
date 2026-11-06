<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ticket tagging for training data (PROJ-78): one row per Zammad ticket with its first customer message,
     * the proposals of the language model, the decisions of the admins and the runs.
     */
    public function up(): void
    {
        Schema::create('labeling_runs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30);
            $table->string('state', 20)->default('queued');
            $table->string('admin', 60);
            $table->json('options')->nullable();
            $table->json('cursor')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('done')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('planned')->default(0);
            $table->boolean('cancel_requested')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('labeling_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zammad_id')->unique();
            $table->string('number', 30)->index();
            $table->string('group_name')->nullable();
            $table->unsignedBigInteger('article_id')->nullable();
            $table->longText('text')->nullable();
            $table->timestamp('zammad_created_at')->nullable();
            $table->string('source', 20)->default('pool');
            $table->string('split', 20)->default('training');
            $table->string('state', 30)->default('captured');
            $table->string('existing_tag', 80)->nullable();
            $table->string('problem', 120)->nullable();
            $table->string('decision', 40)->nullable();
            $table->string('decision_origin', 20)->nullable();
            $table->string('decided_by', 60)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('tag_kind', 20)->nullable();
            $table->string('tag_value', 80)->nullable();
            $table->timestamp('tag_written_at')->nullable();
            $table->string('tag_problem', 120)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'split']);
            $table->index('decision');
        });

        Schema::create('labeling_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_ticket_id')->constrained('labeling_tickets')->cascadeOnDelete();
            $table->foreignId('label_run_id')->nullable()->constrained('labeling_runs')->nullOnDelete();
            $table->string('category', 40)->nullable();
            $table->boolean('unclear')->default(false);
            $table->boolean('multiple_concerns')->default(false);
            $table->decimal('confidence', 4, 3)->default(0);
            $table->text('reason')->nullable();
            $table->text('evidence')->nullable();
            $table->string('queue', 20)->nullable()->index();
            $table->string('model', 80);
            $table->string('prompt_version', 80);
            $table->json('example_ids')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();

            $table->index(['label_ticket_id', 'id']);
            $table->index('category');
        });

        Schema::create('labeling_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_ticket_id')->constrained('labeling_tickets')->cascadeOnDelete();
            $table->foreignId('label_prediction_id')->nullable()->constrained('labeling_predictions')->nullOnDelete();
            $table->string('decision', 40);
            $table->string('origin', 20);
            $table->boolean('hit')->nullable();
            $table->string('reviewer', 60);
            $table->timestamps();

            $table->index(['label_ticket_id', 'id']);
            $table->index(['origin', 'hit']);
        });

        Schema::create('labeling_releases', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40)->unique();
            $table->boolean('released')->default(false);
            $table->string('released_by', 60)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('labeling_settings', function (Blueprint $table) {
            $table->string('key', 40)->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labeling_settings');
        Schema::dropIfExists('labeling_releases');
        Schema::dropIfExists('labeling_reviews');
        Schema::dropIfExists('labeling_predictions');
        Schema::dropIfExists('labeling_tickets');
        Schema::dropIfExists('labeling_runs');
    }
};

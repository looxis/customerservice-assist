<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff reports "possibly outdated, please check" about a knowledge document (PROJ-56).
     */
    public function up(): void
    {
        Schema::create('knowledge_review_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('document_id', 40);
            $table->string('document_fingerprint', 64)->nullable();
            $table->uuid('analysis_uuid')->nullable();
            $table->string('ticket_number', 20)->nullable();
            $table->string('staff_name', 60);
            $table->string('reason', 30);
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('resolved_by', 60)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_review_flags');
    }
};

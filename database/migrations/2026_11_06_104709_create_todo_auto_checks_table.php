<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (PROJ-77: the log of the automatic checks of a ToDo).
     */
    public function up(): void
    {
        Schema::create('todo_auto_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_todo_id')->constrained('ticket_todos')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->string('result', 16);
            $table->string('note', 255)->nullable();

            $table->index(['ticket_todo_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_auto_checks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The history of every settings group: appended only, never changed (PROJ-27).
     */
    public function up(): void
    {
        Schema::create('setting_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('group_key', 80);
            $table->unsignedInteger('version');
            $table->json('value');
            $table->string('kind', 20);
            $table->string('changed_by', 80);
            $table->timestamp('created_at')->nullable();
            $table->unique(['group_key', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_versions');
    }
};

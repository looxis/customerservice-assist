<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DHL return labels (PROJ-64). The shipment number stays for good (the tracking of PROJ-65 starts from it); the
 * PDF lies on the private disk "return-labels" and is deleted after the retention period. The address is not
 * stored, only a fingerprint of the request that finds a repeated order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number', 20)->nullable();
            $table->string('order_number', 60);
            $table->unsignedBigInteger('eocs_order_id');
            $table->string('shipment_no', 40)->nullable();
            $table->string('status', 20);
            $table->string('fingerprint', 64);
            $table->string('created_by', 60);
            $table->string('file_path')->nullable();
            $table->string('file_sha256', 64)->nullable();
            $table->string('error', 300)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_by', 60)->nullable();
            $table->string('sent_ticket_number', 20)->nullable();
            $table->boolean('sent_as_new_ticket')->default(false);
            $table->timestamps();

            $table->index(['ticket_number', 'created_at']);
            $table->index(['fingerprint', 'status']);
            $table->index('shipment_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_labels');
    }
};

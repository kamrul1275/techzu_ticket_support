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
    Schema::create('ticket_attachments', function (Blueprint $table) {
        $table->id();

        // Parent ticket
        $table->foreignId('ticket_id')
            ->constrained('tickets')
            ->restrictOnDelete();

        // Optional reply/message
        $table->foreignId('ticket_message_id')
            ->nullable()
            ->constrained('ticket_messages')
            ->nullOnDelete();

        // User who uploaded the file
        $table->foreignId('uploaded_by')
            ->constrained('users')
            ->restrictOnDelete();

        // Storage information
        $table->string('disk', 50)->default('local');
        $table->string('path');

        // File information
        $table->string('original_name');
        $table->string('mime_type', 100)->nullable();
        $table->unsignedBigInteger('size_bytes');

        $table->timestamps();

        $table->index(['ticket_id', 'created_at']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};

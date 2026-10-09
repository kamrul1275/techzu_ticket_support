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
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();

            // Which ticket this message belongs to
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->restrictOnDelete();

            // Who sent this message
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Message content
            $table->text('body');

            // True = internal agent note
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            // Faster conversation loading
            $table->index(['ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};

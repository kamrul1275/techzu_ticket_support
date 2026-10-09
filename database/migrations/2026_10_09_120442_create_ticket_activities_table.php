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
    Schema::create('ticket_activities', function (Blueprint $table) {
        $table->id();

        $table->foreignId('ticket_id')
            ->constrained('tickets')
            ->restrictOnDelete();

        // User who performed the action
        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // created, assigned, status_changed, etc.
        $table->string('action', 50);

        // Store changed values
        $table->json('old_values')->nullable();
        $table->json('new_values')->nullable();

        $table->timestamps();

        $table->index(['ticket_id', 'created_at']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_activities');
    }
};

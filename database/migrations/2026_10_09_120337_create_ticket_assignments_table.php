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
    Schema::create('ticket_assignments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('ticket_id')
            ->constrained('tickets')
            ->restrictOnDelete();

        // Agent receiving the ticket
        $table->foreignId('agent_id')
            ->constrained('users')
            ->restrictOnDelete();

        // Admin/Agent who performed assignment
        $table->foreignId('assigned_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // manual or automatic
        $table->string('method', 20);

        $table->timestamps();

        $table->index(['ticket_id', 'created_at']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};

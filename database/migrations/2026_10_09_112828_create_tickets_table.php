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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();

        // Human-readable unique ticket number
        $table->string('ticket_number', 30)
            ->nullable()
            ->unique();

        // Who created the ticket
        $table->foreignId('customer_id')
            ->constrained('users')
            ->restrictOnDelete();

        // Which agent is handling it
        $table->foreignId('assigned_agent_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // Ticket category
        $table->foreignId('category_id')
            ->constrained('ticket_categories')
            ->restrictOnDelete();

        // Ticket information
        $table->string('subject', 200);
        $table->text('description');

        $table->string('priority', 20);
        $table->string('status', 30)->default('open');

        // SLA dates
        $table->timestamp('sla_due_at')->nullable();
        $table->timestamp('resolved_at')->nullable();
        $table->timestamp('closed_at')->nullable();

        $table->timestamps();

        // Search and filter optimization
        $table->index(['customer_id', 'created_at']);
        $table->index(['assigned_agent_id', 'status']);
        $table->index(['status', 'sla_due_at']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

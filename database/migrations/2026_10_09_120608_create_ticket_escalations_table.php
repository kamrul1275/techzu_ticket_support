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
    Schema::create('ticket_escalations', function (Blueprint $table) {
        $table->id();

        $table->foreignId('ticket_id')
            ->constrained('tickets')
            ->restrictOnDelete();

        // approaching or breached
        $table->string('type', 30);

        // SLA deadline at the time of escalation
        $table->timestamp('sla_due_at');

        $table->timestamps();

        // Prevent duplicate events for same deadline
        $table->unique([
            'ticket_id',
            'type',
            'sla_due_at',
        ]);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_escalations');
    }
};

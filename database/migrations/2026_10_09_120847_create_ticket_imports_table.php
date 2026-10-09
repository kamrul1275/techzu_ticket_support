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
    Schema::create('ticket_imports', function (Blueprint $table) {
        $table->id();

        $table->foreignId('uploaded_by')
            ->constrained('users')
            ->restrictOnDelete();

        $table->string('original_name');

        // Private storage path
        $table->string('file_path');

        // pending, processing, completed, failed
        $table->string('status', 20)->default('pending');

        $table->unsignedInteger('total_rows')->default(0);
        $table->unsignedInteger('processed_rows')->default(0);
        $table->unsignedInteger('success_rows')->default(0);
        $table->unsignedInteger('failed_rows')->default(0);

        // Optional CSV error report
        $table->string('error_file_path')->nullable();

        $table->timestamp('started_at')->nullable();
        $table->timestamp('finished_at')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_imports');
    }
};

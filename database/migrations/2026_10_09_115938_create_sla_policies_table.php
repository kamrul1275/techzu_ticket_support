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
    Schema::create('sla_policies', function (Blueprint $table) {
        $table->id();

        $table->string('priority', 20)->unique();

        // Resolution time in working minutes
        $table->unsignedInteger('resolution_minutes');

        // Send warning this many minutes before deadline
        $table->unsignedInteger('warning_before_minutes')
            ->default(30);

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};

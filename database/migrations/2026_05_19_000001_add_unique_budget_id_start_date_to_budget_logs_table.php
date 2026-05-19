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
        Schema::table('budget_logs', function (Blueprint $table): void {
            // A budget can only have one log starting on a specific date
            $table->unique(['budget_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_logs', function (Blueprint $table): void {
            $table->dropUnique(['budget_id', 'start_date']);
        });
    }
};

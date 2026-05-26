<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table): void {
            $table->decimal('amount', 12, 2)->nullable()->change();
        });

        Schema::table('budget_logs', function (Blueprint $table): void {
            $table->decimal('allocated_amount', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table): void {
            $table->decimal('amount', 12, 2)->nullable(false)->default(0)->change();
        });

        Schema::table('budget_logs', function (Blueprint $table): void {
            $table->decimal('allocated_amount', 12, 2)->nullable(false)->default(0)->change();
        });
    }
};

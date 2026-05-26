<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('budget_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // Insert initial budget types directly in the migration so that
        // existing budgets can be successfully altered with the foreign key constraint.
        DB::table('budget_types')->insertOrIgnore([
            [
                'code' => 'budget',
                'name' => 'Budget',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'tracking',
                'name' => 'Tracking',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $budgetTypeId = DB::table('budget_types')->where('code', 'budget')->value('id');

        Schema::table('budgets', function (Blueprint $table) use ($budgetTypeId): void {
            $table->foreignId('budget_type_id')
                ->after('user_id')
                ->default($budgetTypeId)
                ->constrained('budget_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table): void {
            $table->dropForeign(['budget_type_id']);
            $table->dropColumn('budget_type_id');
        });

        Schema::dropIfExists('budget_types');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->default(1)->after('item');
            $table->decimal('total', 12, 2)->default(0)->after('price');
        });

        DB::table('expenses')->update([
            'total' => DB::raw('ROUND(price * quantity, 2)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn(['quantity', 'total']);
        });
    }
};

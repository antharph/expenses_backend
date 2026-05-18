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
            $table->foreignId('store_id')
                ->nullable()
                ->after('total')
                ->constrained()
                ->nullOnDelete();
            $table->string('transaction_number')->nullable()->after('store_id');
            $table->string('invoice_number')->nullable()->after('transaction_number');
            $table->timestamp('transaction_at')->nullable()->after('invoice_number');
        });

        DB::table('expenses')->update([
            'transaction_at' => DB::raw('created_at'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn(['transaction_number', 'invoice_number', 'transaction_at']);
        });
    }
};

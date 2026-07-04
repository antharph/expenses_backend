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
        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_provider', 32)->default('email')->after('password_auth_enabled');
        });

        DB::table('users')
            ->where('password_auth_enabled', true)
            ->update(['auth_provider' => 'email']);

        DB::table('users')
            ->where('password_auth_enabled', false)
            ->whereNotNull('firebase_uid')
            ->update(['auth_provider' => 'google']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('auth_provider');
        });
    }
};

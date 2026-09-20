<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('telegram_commands')
            ->where('command', 'transaksi')
            ->update(['is_active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('telegram_commands')
            ->where('command', 'transaksi')
            ->update(['is_active' => true]);
    }
};

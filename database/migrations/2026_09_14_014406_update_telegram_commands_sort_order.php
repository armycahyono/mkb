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
        // Update sort orders to reflect desired menu arrangement
        DB::table('telegram_commands')->where('command', 'transaksi')->update(['sort_order' => 1]);
        DB::table('telegram_commands')->where('command', 'pengeluaran')->update(['sort_order' => 2]);
        DB::table('telegram_commands')->where('command', 'pemasukan')->update(['sort_order' => 3]);
        DB::table('telegram_commands')->where('command', 'kasbon')->update(['sort_order' => 4]);
        DB::table('telegram_commands')->where('command', 'saldo')->update(['sort_order' => 5]);
        DB::table('telegram_commands')->where('command', 'foto')->update(['sort_order' => 6]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original orders as per initial seeding
        DB::table('telegram_commands')->where('command', 'transaksi')->update(['sort_order' => 1]);
        DB::table('telegram_commands')->where('command', 'pengeluaran')->update(['sort_order' => 1]);
        DB::table('telegram_commands')->where('command', 'pemasukan')->update(['sort_order' => 2]);
        DB::table('telegram_commands')->where('command', 'kasbon')->update(['sort_order' => 3]);
        DB::table('telegram_commands')->where('command', 'saldo')->update(['sort_order' => 2]);
        DB::table('telegram_commands')->where('command', 'foto')->update(['sort_order' => 3]);
    }
};

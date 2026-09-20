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
        $commands = [
            'transaksi' => ['description' => 'Transaksi', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 1],
            'pemasukan' => ['description' => 'Pemasukan', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 2],
            'pengeluaran' => ['description' => 'Pengeluaran', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 3],
            'kasbon' => ['description' => 'Kasbon', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 4],
            'saldo' => ['description' => 'Saldo', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 5],
            'foto' => ['description' => 'Foto', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 6],
            'help' => ['description' => 'Help', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 7],
            'batal' => ['description' => 'Batal', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 8],
            'logout' => ['description' => 'Logout', 'response_type' => 'system', 'response_text' => null, 'is_active' => true, 'sort_order' => 9],
        ];

        foreach ($commands as $command => $data) {
            DB::table('telegram_commands')->updateOrInsert(['command' => $command], $data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove commands that were added by this migration if they were not present before
        DB::table('telegram_commands')->whereIn('command', ['pemasukan', 'pengeluaran', 'kasbon'])->delete();
    }
};

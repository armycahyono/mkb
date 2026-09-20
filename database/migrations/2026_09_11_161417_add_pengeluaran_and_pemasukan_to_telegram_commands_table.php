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
        $now = now();
        $commands = [
            [
                'command' => 'pengeluaran',
                'description' => 'Catat transaksi pengeluaran kas BOSP langsung',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'command' => 'pemasukan',
                'description' => 'Catat transaksi penerimaan kas BOSP langsung',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($commands as $cmd) {
            DB::table('telegram_commands')->updateOrInsert(
                ['command' => $cmd['command']],
                $cmd
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('telegram_commands')->whereIn('command', ['pengeluaran', 'pemasukan'])->delete();
    }
};

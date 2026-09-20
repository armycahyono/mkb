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
        DB::table('telegram_commands')->updateOrInsert(
            ['command' => 'kasbon'],
            [
                'command' => 'kasbon',
                'description' => 'Cek rincian seluruh kasbon / panjar kerja aktif',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('telegram_commands')->where('command', 'kasbon')->delete();
    }
};

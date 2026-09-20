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
        Schema::create('telegram_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command', 50)->unique();
            $table->string('description', 255);
            $table->string('response_type', 20)->default('text'); // 'system' or 'text'
            $table->text('response_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default system commands
        $now = now();
        DB::table('telegram_commands')->insert([
            [
                'command' => 'transaksi',
                'description' => 'Catat transaksi pengeluaran/pemasukan kas & foto nota',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'command' => 'saldo',
                'description' => 'Cek saldo kas tunai BOSP & Komite real-time',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'command' => 'foto',
                'description' => 'Upload dokumentasi fisik barang belanja BOSP',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'command' => 'help',
                'description' => 'Panduan bantuan dan informasi perintah bot',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'command' => 'batal',
                'description' => 'Batalkan pengisian transaksi yang sedang aktif',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'command' => 'logout',
                'description' => 'Putuskan tautan akun sistem dari Telegram',
                'response_type' => 'system',
                'response_text' => null,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_commands');
    }
};

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
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('label', 150);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default categories from the existing hardcoded list
        $defaults = [
            ['key' => 'atk_fotokopi',     'label' => 'ATK & Fotokopi',              'sort_order' => 1],
            ['key' => 'sarpras_bangunan', 'label' => 'Sarpras & Bangunan',          'sort_order' => 2],
            ['key' => 'elektronik_it',    'label' => 'Elektronik & IT',             'sort_order' => 3],
            ['key' => 'konsumsi_katering', 'label' => 'Konsumsi & Katering',         'sort_order' => 4],
            ['key' => 'buku_pembelajaran', 'label' => 'Buku & Pembelajaran',         'sort_order' => 5],
            ['key' => 'jasa_servis',      'label' => 'Jasa & Servis / Pemeliharaan', 'sort_order' => 6],
            ['key' => 'lainnya',          'label' => 'Lain-lain',                   'sort_order' => 99],
        ];

        foreach ($defaults as $cat) {
            DB::table('supplier_categories')->insert([
                'key' => $cat['key'],
                'label' => $cat['label'],
                'is_default' => true,
                'sort_order' => $cat['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_categories');
    }
};

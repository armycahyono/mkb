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
        Schema::create('kas_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150);
            $table->string('type', 10)->index(); // masuk / keluar
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $defaults = [
            // Kas Masuk (Penerimaan)
            [
                'code' => 'droping_sekolah',
                'name' => 'Droping / Alokasi Dana Sekolah',
                'type' => 'masuk',
                'icon' => '🏫',
                'description' => 'Droping dana dari rekening utama sekolah atau yayasan',
                'is_system' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'tarik_bank',
                'name' => 'Tarik Bank (Droping Kas)',
                'type' => 'masuk',
                'icon' => '🏦',
                'description' => 'Penarikan dana tunai dari rekening bank operasional BOSP/BPOPP',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'sisa_panjar',
                'name' => 'Kembalian Sisa Kasbon / Panjar',
                'type' => 'masuk',
                'icon' => '💵',
                'description' => 'Uang sisa belanja kasbon yang dikembalikan oleh guru/staf ke bendahara',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'pengembalian_talangan',
                'name' => 'Pengembalian Dana Talangan',
                'type' => 'masuk',
                'icon' => '🔄',
                'description' => 'Penerimaan pengembalian atas dana talangan yang sebelumnya dikeluarkan',
                'is_system' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'sisa_perjadin',
                'name' => 'Kembalian Sisa Perjadin',
                'type' => 'masuk',
                'icon' => '🚗',
                'description' => 'Uang sisa perjalanan dinas yang dikembalikan',
                'is_system' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'dana_talangan_masuk',
                'name' => 'Penerimaan Dana Talangan',
                'type' => 'masuk',
                'icon' => '🤝',
                'description' => 'Dana pinjaman/talangan sementara dari pihak lain yang masuk ke kas tunai',
                'is_system' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'lainnya_masuk',
                'name' => 'Penerimaan Lainnya',
                'type' => 'masuk',
                'icon' => '📥',
                'description' => 'Penerimaan kas tunai lainnya di luar kategori utama',
                'is_system' => false,
                'sort_order' => 99,
            ],

            // Kas Keluar (Pengeluaran)
            [
                'code' => 'belanja_tunai',
                'name' => 'Belanja Barang / Nota Langsung',
                'type' => 'keluar',
                'icon' => '🛍️',
                'description' => 'Pembelian ATK, bahan praktik, konsumsi harian, dan operasional dengan nota langsung',
                'is_system' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'panjar_kerja',
                'name' => 'Kasbon / Panjar Belanja (Uang Muka)',
                'type' => 'keluar',
                'icon' => '💼',
                'description' => 'Uang muka kerja/belanja yang dibawa oleh guru/staf dan wajib di-SPJ-kan',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'talangan_belanja',
                'name' => 'Talangan Belanja Barang (Toko/SIPLah)',
                'type' => 'keluar',
                'icon' => '🏬',
                'description' => 'Pembayaran talangan belanja toko atau pesanan SIPLah yang dibayar tunai terlebih dahulu',
                'is_system' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'operasional_laporan',
                'name' => 'Operasional & Penunjang Laporan (Transport/Konsumsi/SPJ)',
                'type' => 'keluar',
                'icon' => '📋',
                'description' => 'Biaya penggandaan berkas, transport koordinasi dinas, konsumsi rapat penyusunan laporan',
                'is_system' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'honor_tim',
                'name' => 'Honor / Insentif Tim Pengelola',
                'type' => 'keluar',
                'icon' => '👥',
                'description' => 'Pembayaran honor atau insentif bagi tim pelaksana/pengelola kegiatan',
                'is_system' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'perjadin_tunai',
                'name' => 'Pembayaran Perjadin Tunai',
                'type' => 'keluar',
                'icon' => '🚗',
                'description' => 'Pembayaran uang harian dan transport perjalanan dinas pegawai secara tunai',
                'is_system' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'reimburse',
                'name' => 'Reimburse / Ganti Uang Guru',
                'type' => 'keluar',
                'icon' => '💸',
                'description' => 'Penggantian uang pribadi guru yang telah dibelanjakan terlebih dahulu untuk keperluan sekolah',
                'is_system' => true,
                'sort_order' => 7,
            ],
            [
                'code' => 'setor_bank',
                'name' => 'Setor Tunai ke Bank',
                'type' => 'keluar',
                'icon' => '🏦',
                'description' => 'Penyetoran sisa kas tunai kembali ke rekening bank sekolah',
                'is_system' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'bayar_talangan',
                'name' => 'Pengembalian Dana Talangan',
                'type' => 'keluar',
                'icon' => '🤝',
                'description' => 'Pembayaran kembali pinjaman talangan yang pernah diterima sekolah',
                'is_system' => false,
                'sort_order' => 9,
            ],
            [
                'code' => 'lainnya_keluar',
                'name' => 'Pengeluaran Lainnya',
                'type' => 'keluar',
                'icon' => '📦',
                'description' => 'Pengeluaran kas tunai operasional lainnya',
                'is_system' => false,
                'sort_order' => 99,
            ],
        ];

        foreach ($defaults as $cat) {
            DB::table('kas_categories')->insert([
                'code' => $cat['code'],
                'name' => $cat['name'],
                'type' => $cat['type'],
                'icon' => $cat['icon'],
                'description' => $cat['description'],
                'is_system' => $cat['is_system'],
                'is_active' => true,
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
        Schema::dropIfExists('kas_categories');
    }
};

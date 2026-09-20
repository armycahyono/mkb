<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengajuan_barangs', function (Blueprint $table) {
            $table->id();
            $table->year('tahun_anggaran');
            $table->foreignId('guru_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->foreignId('pemohon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_barang');
            $table->text('spesifikasi');
            $table->decimal('volume', 12, 2)->default(1);
            $table->string('satuan');
            $table->decimal('harga', 15, 2)->default(0);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('kegiatan');
            $table->string('bulan_pakai');
            $table->text('keterangan')->nullable();
            $table->string('realisasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_barangs');
    }
};

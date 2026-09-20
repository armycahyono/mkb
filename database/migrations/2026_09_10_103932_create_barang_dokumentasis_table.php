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
        Schema::create('barang_dokumentasis', function (Blueprint $table) {
            $table->id();
            $table->string('funding_source', 20)->default('BOS')->index();
            $table->integer('year')->index();
            $table->string('no_nota', 100)->index();
            $table->date('tanggal_terima');
            $table->string('nama_toko')->nullable();
            $table->text('uraian')->nullable();
            $table->decimal('nominal', 15, 2)->nullable();
            $table->foreignId('kas_tunai_id')->nullable()->constrained('kas_tunais')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('barang_dokumentasi_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_dokumentasi_id')->constrained('barang_dokumentasis')->cascadeOnDelete();
            $table->integer('urutan')->default(1);
            $table->string('file_path');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_dokumentasi_fotos');
        Schema::dropIfExists('barang_dokumentasis');
    }
};

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
        Schema::create('kegiatan_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 30)->nullable();
            $table->string('arkas_kegiatan_kode', 50)->nullable()->comment('Kode default kegiatan ARKAS resmi (contoh: 03.03.01)');
            $table->string('color', 20)->default('#3b82f6')->comment('Kode warna HEX untuk badge visual');
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan_sekolahs');
    }
};

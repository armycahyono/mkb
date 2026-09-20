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
        Schema::create('arkas_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->string('snp', 100)->index();
            $table->string('komponen', 150)->index();
            $table->string('kode_kegiatan', 50)->index();
            $table->text('kegiatan');
            $table->text('definisi_operasional')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arkas_kegiatans');
    }
};

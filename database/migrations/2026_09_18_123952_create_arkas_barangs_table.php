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
        Schema::create('arkas_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_barang', 100)->index();
            $table->string('kode_rekening', 50)->index();
            $table->string('nama_barang', 255)->index();
            $table->string('satuan', 50);
            $table->decimal('harga_min', 15, 2);
            $table->decimal('harga_max', 15, 2);
            $table->unsignedInteger('varian_count')->default(1);
            $table->string('kode_belanja', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arkas_barangs');
    }
};

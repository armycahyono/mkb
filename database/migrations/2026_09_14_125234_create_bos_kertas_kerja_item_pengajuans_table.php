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
        Schema::dropIfExists('bos_kertas_kerja_item_pengajuans');
        Schema::create('bos_kertas_kerja_item_pengajuans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bos_kertas_kerja_item_id');
            $table->unsignedBigInteger('pengajuan_barang_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('bos_kertas_kerja_item_id', 'fk_bkki_pengajuan')
                ->references('id')->on('bos_kertas_kerja_items')
                ->cascadeOnDelete();

            $table->foreign('pengajuan_barang_id', 'fk_pb_kertas_kerja')
                ->references('id')->on('pengajuan_barangs')
                ->cascadeOnDelete();

            $table->unique(['bos_kertas_kerja_item_id', 'pengajuan_barang_id'], 'bkki_pb_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bos_kertas_kerja_item_pengajuans');
    }
};

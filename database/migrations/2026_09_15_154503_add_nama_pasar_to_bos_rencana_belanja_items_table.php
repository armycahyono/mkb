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
        Schema::table('bos_rencana_belanja_items', function (Blueprint $table) {
            if (! Schema::hasColumn('bos_rencana_belanja_items', 'nama_pasar')) {
                $table->string('nama_pasar', 255)->nullable()->after('unit');
            }
            if (! Schema::hasColumn('bos_rencana_belanja_items', 'spesifikasi')) {
                $table->text('spesifikasi')->nullable()->after('nama_pasar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bos_rencana_belanja_items', function (Blueprint $table) {
            if (Schema::hasColumn('bos_rencana_belanja_items', 'spesifikasi')) {
                $table->dropColumn('spesifikasi');
            }
            if (Schema::hasColumn('bos_rencana_belanja_items', 'nama_pasar')) {
                $table->dropColumn('nama_pasar');
            }
        });
    }
};

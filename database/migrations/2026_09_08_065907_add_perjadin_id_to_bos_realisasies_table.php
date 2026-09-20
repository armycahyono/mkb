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
        Schema::table('bos_realisasies', function (Blueprint $table) {
            $table->foreignId('perjadin_id')
                ->nullable()
                ->after('bos_rencana_belanja_id')
                ->constrained('perjadins')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bos_realisasies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perjadin_id');
        });
    }
};

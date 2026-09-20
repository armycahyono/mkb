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
        Schema::table('perjadins', function (Blueprint $table) {
            $table->foreignId('bos_kertas_kerja_item_id')
                ->nullable()
                ->after('funding_source')
                ->constrained('bos_kertas_kerja_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perjadins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bos_kertas_kerja_item_id');
        });
    }
};

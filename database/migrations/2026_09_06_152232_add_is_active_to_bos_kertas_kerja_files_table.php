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
        Schema::table('bos_kertas_kerja_files', function (Blueprint $table) {
            if (! Schema::hasColumn('bos_kertas_kerja_files', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bos_kertas_kerja_files', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

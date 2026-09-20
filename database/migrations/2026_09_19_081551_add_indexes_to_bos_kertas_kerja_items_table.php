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
        Schema::table('bos_kertas_kerja_items', function (Blueprint $table) {
            $table->index(['year', 'month'], 'bos_kki_year_month_idx');
            $table->index('year', 'bos_kki_year_idx');
            $table->index('month', 'bos_kki_month_idx');
            $table->index('account_code', 'bos_kki_account_code_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bos_kertas_kerja_items', function (Blueprint $table) {
            $table->dropIndex('bos_kki_year_month_idx');
            $table->dropIndex('bos_kki_year_idx');
            $table->dropIndex('bos_kki_month_idx');
            $table->dropIndex('bos_kki_account_code_idx');
        });
    }
};

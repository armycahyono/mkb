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
        Schema::table('guru_jabatans', function (Blueprint $table) {
            $table->string('sk_file', 255)->nullable()->after('sk_date');
            $table->string('period_type', 30)->nullable()->default('tahun_anggaran')->after('period_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guru_jabatans', function (Blueprint $table) {
            $table->dropColumn(['sk_file', 'period_type']);
        });
    }
};

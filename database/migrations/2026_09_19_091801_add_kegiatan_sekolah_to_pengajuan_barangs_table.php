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
        Schema::table('pengajuan_barangs', function (Blueprint $table) {
            $table->string('kegiatan_sekolah')->nullable()->after('kegiatan');
            $table->foreignId('kegiatan_sekolah_id')->nullable()->after('kegiatan_sekolah')->constrained('kegiatan_sekolahs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_barangs', function (Blueprint $table) {
            $table->dropForeign(['kegiatan_sekolah_id']);
            $table->dropColumn(['kegiatan_sekolah_id', 'kegiatan_sekolah']);
        });
    }
};

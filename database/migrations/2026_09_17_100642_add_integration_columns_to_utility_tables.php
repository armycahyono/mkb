<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom pada utility_profiles
        Schema::table('utility_profiles', function (Blueprint $table) {
            $table->boolean('is_budgeted')->default(true)->after('is_active');
            $table->foreignId('default_reimburse_guru_id')->nullable()->after('is_budgeted')->constrained('gurus')->nullOnDelete();
        });

        // Tandai profil yang memuat SMP PGRI sebagai non-budgeted
        DB::table('utility_profiles')
            ->where('customer_name', 'like', '%SMP PGRI%')
            ->orWhere('name', 'like', '%SMP PGRI%')
            ->update(['is_budgeted' => false]);

        // 2. Tambah kolom pada pengajuan_barangs
        Schema::table('pengajuan_barangs', function (Blueprint $table) {
            $table->foreignId('utility_profile_id')->nullable()->after('pemohon_id')->constrained('utility_profiles')->nullOnDelete();
        });

        // 3. Tambah kolom pada bos_kertas_kerja_items
        Schema::table('bos_kertas_kerja_items', function (Blueprint $table) {
            $table->foreignId('utility_profile_id')->nullable()->after('bos_kertas_kerja_file_id')->constrained('utility_profiles')->nullOnDelete();
        });

        // 4. Tambah kolom pada utility_receipts
        Schema::table('utility_receipts', function (Blueprint $table) {
            $table->foreignId('bos_kertas_kerja_item_id')->nullable()->after('utility_profile_id')->constrained('bos_kertas_kerja_items')->nullOnDelete();
            $table->foreignId('pencairan_bank_item_id')->nullable()->after('bos_kertas_kerja_item_id')->constrained('pencairan_bank_items')->nullOnDelete();
            $table->boolean('is_paid')->default(false)->after('total_amount');
            $table->date('payment_date')->nullable()->after('is_paid');
            $table->string('bku_number', 100)->nullable()->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_receipts', function (Blueprint $table) {
            $table->dropForeign(['bos_kertas_kerja_item_id']);
            $table->dropForeign(['pencairan_bank_item_id']);
            $table->dropColumn(['bos_kertas_kerja_item_id', 'pencairan_bank_item_id', 'is_paid', 'payment_date', 'bku_number']);
        });

        Schema::table('bos_kertas_kerja_items', function (Blueprint $table) {
            $table->dropForeign(['utility_profile_id']);
            $table->dropColumn('utility_profile_id');
        });

        Schema::table('pengajuan_barangs', function (Blueprint $table) {
            $table->dropForeign(['utility_profile_id']);
            $table->dropColumn('utility_profile_id');
        });

        Schema::table('utility_profiles', function (Blueprint $table) {
            $table->dropForeign(['default_reimburse_guru_id']);
            $table->dropColumn(['is_budgeted', 'default_reimburse_guru_id']);
        });
    }
};

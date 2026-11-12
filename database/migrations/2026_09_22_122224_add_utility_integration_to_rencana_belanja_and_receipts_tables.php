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
        Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
            if (! Schema::hasColumn('bos_rencana_belanjas', 'type')) {
                $table->string('type', 30)->default('belanja')->after('title');
            }
            if (! Schema::hasColumn('bos_rencana_belanjas', 'guru_id')) {
                $table->foreignId('guru_id')->nullable()->after('supplier_id')->constrained('gurus')->nullOnDelete();
            }
        });

        Schema::table('utility_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('utility_receipts', 'bos_rencana_belanja_id')) {
                $table->foreignId('bos_rencana_belanja_id')->nullable()->after('bos_kertas_kerja_item_id')->constrained('bos_rencana_belanjas')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('utility_receipts', 'bos_rencana_belanja_id')) {
                $table->dropForeign(['bos_rencana_belanja_id']);
                $table->dropColumn('bos_rencana_belanja_id');
            }
        });

        Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
            if (Schema::hasColumn('bos_rencana_belanjas', 'guru_id')) {
                $table->dropForeign(['guru_id']);
                $table->dropColumn('guru_id');
            }
            if (Schema::hasColumn('bos_rencana_belanjas', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};

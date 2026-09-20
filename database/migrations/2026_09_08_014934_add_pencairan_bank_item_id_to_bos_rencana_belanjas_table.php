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
        if (Schema::hasTable('bos_rencana_belanjas')) {
            Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                if (! Schema::hasColumn('bos_rencana_belanjas', 'pencairan_bank_item_id')) {
                    $table->foreignId('pencairan_bank_item_id')->nullable()->after('status')->constrained('pencairan_bank_items')->nullOnDelete();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'bank_name')) {
                    $table->string('bank_name', 100)->nullable()->after('pencairan_bank_item_id');
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'account_number')) {
                    $table->string('account_number', 50)->nullable()->after('bank_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bos_rencana_belanjas')) {
            Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                if (Schema::hasColumn('bos_rencana_belanjas', 'pencairan_bank_item_id')) {
                    $table->dropForeign(['pencairan_bank_item_id']);
                    $table->dropColumn('pencairan_bank_item_id');
                }
                if (Schema::hasColumn('bos_rencana_belanjas', 'account_number')) {
                    $table->dropColumn('account_number');
                }
                if (Schema::hasColumn('bos_rencana_belanjas', 'bank_name')) {
                    $table->dropColumn('bank_name');
                }
            });
        }
    }
};

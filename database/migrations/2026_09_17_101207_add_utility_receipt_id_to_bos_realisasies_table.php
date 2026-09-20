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
            $table->foreignId('utility_receipt_id')->nullable()->after('perjadin_id')->constrained('utility_receipts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bos_realisasies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('utility_receipt_id');
        });
    }
};

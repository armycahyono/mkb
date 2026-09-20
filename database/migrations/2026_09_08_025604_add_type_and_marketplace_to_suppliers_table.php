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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('type', 20)->default('offline')->after('name'); // offline / online
            $table->string('marketplace_name', 100)->nullable()->after('type'); // Toko Ladang, SIPLah Blibli, dll.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['type', 'marketplace_name']);
        });
    }
};

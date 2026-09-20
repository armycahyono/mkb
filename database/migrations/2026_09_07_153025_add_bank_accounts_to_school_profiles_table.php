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
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->string('bosp_bank_name', 100)->nullable()->default('PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR');
            $table->string('bosp_bank_branch', 100)->nullable()->default('CABANG KEDIRI');
            $table->string('bosp_bank_address', 255)->nullable()->default('Jl. PK. Bangsa No. 122 Kediri');
            $table->string('bosp_account_number', 50)->nullable()->default('1001003336');
            $table->string('bosp_account_name', 150)->nullable()->default('20511923 SMKS PGRI KRAS KEDIRI (BOSP)');

            $table->string('bpopp_bank_name', 100)->nullable()->default('PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR');
            $table->string('bpopp_bank_branch', 100)->nullable()->default('CABANG KEDIRI');
            $table->string('bpopp_bank_address', 255)->nullable()->default('Jl. PK. Bangsa No. 122 Kediri');
            $table->string('bpopp_account_number', 50)->nullable()->default('1001001236');
            $table->string('bpopp_account_name', 150)->nullable()->default('SMKS PGRI KRAS KEDIRI (BPOPP)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'bosp_bank_name', 'bosp_bank_branch', 'bosp_bank_address', 'bosp_account_number', 'bosp_account_name',
                'bpopp_bank_name', 'bpopp_bank_branch', 'bpopp_bank_address', 'bpopp_account_number', 'bpopp_account_name',
            ]);
        });
    }
};

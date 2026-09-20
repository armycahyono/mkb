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
        Schema::create('pencairan_banks', function (Blueprint $table) {
            $table->id();
            $table->string('funding_source', 20)->default('BOSP')->index();
            $table->string('letter_number', 100);
            $table->date('letter_date');
            $table->string('bank_name', 100)->default('PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR');
            $table->string('bank_branch', 100)->default('CABANG KEDIRI');
            $table->string('bank_address', 255)->default('Jl. PK. Bangsa No. 122 Kediri');
            $table->string('source_account_number', 50)->default('1001003336');
            $table->string('source_account_name', 150)->default('20511923 SMKS PGRI KRAS KEDIRI (BOSP)');
            $table->foreignId('kepala_sekolah_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->foreignId('bendahara_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->integer('total_accounts')->default(0);
            $table->string('status', 20)->default('draft');
            $table->date('cair_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pencairan_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pencairan_bank_id')->constrained('pencairan_banks')->cascadeOnDelete();
            $table->string('type', 20)->default('perjadin');
            $table->string('recipient_name', 150);
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('perjadins', function (Blueprint $table) {
            $table->id();
            $table->string('funding_source', 20)->default('BOSP')->index();
            $table->string('bku_number', 100)->nullable();
            $table->string('account_code', 50)->nullable();
            $table->text('activity_purpose');
            $table->string('destination_name', 255);
            $table->text('destination_address')->nullable();
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('city', 100)->default('Kediri');
            $table->foreignId('kepala_sekolah_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->foreignId('bendahara_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('perjadin_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perjadin_id')->constrained('perjadins')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('guru_bank_account_id')->nullable()->constrained('guru_bank_accounts')->nullOnDelete();
            $table->foreignId('pencairan_bank_item_id')->nullable()->constrained('pencairan_bank_items')->nullOnDelete();
            $table->string('receipt_number', 100)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('pph23', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perjadin_items');
        Schema::dropIfExists('perjadins');
        Schema::dropIfExists('pencairan_bank_items');
        Schema::dropIfExists('pencairan_banks');
    }
};

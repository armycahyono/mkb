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
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->nullable()->index();
            $table->string('nik', 20)->nullable()->index();
            $table->string('nuptk', 30)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('name', 150);
            $table->string('title_prefix', 50)->nullable();
            $table->string('title_suffix', 50)->nullable();
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 30)->nullable();
            $table->enum('employment_status', ['PNS', 'PPPK', 'GTT', 'GTY', 'Honorer', 'PTT'])->default('GTT');
            $table->string('pangkat_golongan', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guru_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->string('bank_name', 50);
            $table->string('account_number', 50);
            $table->string('account_holder', 150);
            $table->boolean('is_primary')->default(false);
            $table->string('notes', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_bank_accounts');
        Schema::dropIfExists('gurus');
    }
};

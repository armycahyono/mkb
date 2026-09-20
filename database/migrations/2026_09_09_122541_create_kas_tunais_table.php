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
        Schema::create('kas_tunais', function (Blueprint $table) {
            $table->id();
            $table->string('funding_source', 20)->default('BOSP')->index();
            $table->date('transaction_date')->index();
            $table->string('transaction_type', 10)->index(); // masuk / keluar
            $table->string('category', 50)->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description');
            $table->string('person_name')->nullable();
            $table->foreignId('guru_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->foreignId('pencairan_bank_id')->nullable()->constrained('pencairan_banks')->nullOnDelete();
            $table->foreignId('bos_rencana_belanja_id')->nullable()->constrained('bos_rencana_belanjas')->nullOnDelete();
            $table->foreignId('perjadin_item_id')->nullable()->constrained('perjadin_items')->nullOnDelete();
            $table->foreignId('parent_kas_id')->nullable()->constrained('kas_tunais')->nullOnDelete();
            $table->string('panjar_status', 20)->default('none')->index(); // none, pending, settled
            $table->string('evidence_number')->nullable();
            $table->string('receipt_file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_tunais');
    }
};

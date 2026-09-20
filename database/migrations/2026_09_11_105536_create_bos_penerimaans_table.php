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
        Schema::create('bos_penerimaans', function (Blueprint $table) {
            $table->id();
            $table->string('funding_source', 20)->default('BOSP');
            $table->integer('year');
            $table->string('category', 50)->default('salur_tahap_1');
            $table->date('transaction_date');
            $table->string('reference_number', 50)->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->string('receipt_file', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['funding_source', 'year']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bos_penerimaans');
    }
};

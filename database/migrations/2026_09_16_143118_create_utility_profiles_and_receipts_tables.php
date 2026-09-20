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
        Schema::create('utility_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 50); // listrik_pasca, listrik_pra, telkom_speedy, internet, lainnya
            $table->string('customer_name');
            $table->string('customer_number')->nullable();
            $table->string('meter_number')->nullable();
            $table->string('idpel')->nullable();
            $table->string('tariff_power')->nullable();
            $table->string('provider')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('utility_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utility_profile_id')->nullable()->constrained('utility_profiles')->nullOnDelete();
            $table->integer('fiscal_year')->index();
            $table->string('billing_month', 20)->index();
            $table->dateTime('transaction_date')->index();
            $table->string('receipt_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_number')->nullable();
            $table->string('meter_number')->nullable();
            $table->string('tariff_power')->nullable();
            $table->decimal('kwh_amount', 12, 2)->nullable();
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('tax_pbjt', 15, 2)->default(0);
            $table->decimal('tax_ppn', 15, 2)->default(0);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('token_number')->nullable();
            $table->string('payment_merchant')->nullable();
            $table->string('receipt_file')->nullable();
            $table->json('ocr_raw_data')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_receipts');
        Schema::dropIfExists('utility_profiles');
    }
};

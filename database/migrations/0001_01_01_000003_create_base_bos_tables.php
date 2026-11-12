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
        if (! Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('npsn')->nullable();
                $table->text('address')->nullable();
                $table->string('foundation_name')->nullable();
                $table->string('logo_school')->nullable();
                $table->string('logo_foundation')->nullable();
                $table->string('letterhead_landscape')->nullable();
                $table->string('letterhead_portrait')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->year('established_year')->nullable();
                $table->string('ownership', 50)->nullable();
                $table->string('school_status', 50)->nullable();
                $table->string('sk_pendirian')->nullable();
                $table->date('sk_date')->nullable();
                $table->string('accreditation', 10)->nullable();
                $table->year('accreditation_year')->nullable();
                $table->string('curriculum')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->string('month');
                $table->string('filename');
                $table->string('file_path');
                $table->string('version')->default('1.0');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id');
                $table->string('arkas_id')->nullable();
                $table->integer('year');
                $table->string('month');
                $table->string('account_code');
                $table->string('program_code')->nullable();
                $table->text('kegiatan')->nullable();
                $table->text('description');
                $table->decimal('volume', 15, 2)->default(1.00);
                $table->string('unit')->default('pcs');
                $table->decimal('price', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->integer('year');
                $table->string('month', 20);
                $table->string('title');
                $table->string('type', 30)->default('belanja');
                $table->string('supplier_name')->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status', 50)->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanja_items')) {
            Schema::create('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_rencana_belanja_id');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id');
                $table->decimal('volume_plan', 12, 2);
                $table->string('unit', 50);
                $table->decimal('price', 15, 2);
                $table->decimal('total_amount', 15, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id');
                $table->string('no_transaksi', 50)->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
                $table->integer('year');
                $table->string('month', 20);
                $table->date('transaction_date');
                $table->date('receipt_date')->nullable();
                $table->decimal('volume', 12, 2);
                $table->string('unit', 50);
                $table->decimal('price', 15, 2);
                $table->decimal('total_amount', 15, 2);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bos_realisasies');
        Schema::dropIfExists('bos_rencana_belanja_items');
        Schema::dropIfExists('bos_rencana_belanjas');
        Schema::dropIfExists('bos_kertas_kerja_items');
        Schema::dropIfExists('bos_kertas_kerja_files');
        Schema::dropIfExists('school_profiles');
    }
};

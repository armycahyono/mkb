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
        Schema::table('utility_receipts', function (Blueprint $table) {
            $table->string('ocr_status', 25)->default('completed')->after('ocr_raw_data')->index();
            $table->integer('ocr_attempts')->default(0)->after('ocr_status');
            $table->text('ocr_error_message')->nullable()->after('ocr_attempts');
            $table->timestamp('ocr_processed_at')->nullable()->after('ocr_error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_receipts', function (Blueprint $table) {
            $table->dropColumn(['ocr_status', 'ocr_attempts', 'ocr_error_message', 'ocr_processed_at']);
        });
    }
};

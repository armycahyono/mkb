<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bos_rencana_belanjas')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `bos_rencana_belanjas` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
            } else {
                Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                    $table->string('status', 50)->default('draft')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bos_rencana_belanjas')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `bos_rencana_belanjas` MODIFY `status` ENUM('draft', 'realized') NOT NULL DEFAULT 'draft'");
            } else {
                Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                    $table->string('status', 20)->default('draft')->change();
                });
            }
        }
    }
};

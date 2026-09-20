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
        Schema::table('kas_tunais', function (Blueprint $table) {
            $table->foreignId('kas_category_id')
                ->nullable()
                ->after('category')
                ->constrained('kas_categories')
                ->nullOnDelete();
        });

        try {
            DB::statement('
                UPDATE kas_tunais kt
                JOIN kas_categories kc ON kt.category = kc.code
                SET kt.kas_category_id = kc.id
            ');
        } catch (Exception) {
            // Silently ignore if SQLite syntax or during tests
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_tunais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kas_category_id');
        });
    }
};

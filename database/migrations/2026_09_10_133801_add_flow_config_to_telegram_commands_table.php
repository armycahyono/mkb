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
        Schema::table('telegram_commands', function (Blueprint $table) {
            $table->json('flow_config')->nullable()->after('response_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_commands', function (Blueprint $table) {
            $table->dropColumn('flow_config');
        });
    }
};

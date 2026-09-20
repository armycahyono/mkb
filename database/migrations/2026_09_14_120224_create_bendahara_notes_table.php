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
        Schema::create('bendahara_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipe', 20)->default('bosp');
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('color', 20)->default('yellow');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipe', 'is_pinned', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bendahara_notes');
    }
};

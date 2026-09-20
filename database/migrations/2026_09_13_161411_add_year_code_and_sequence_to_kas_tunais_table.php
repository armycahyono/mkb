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
            $table->unsignedSmallInteger('year')->nullable()->after('transaction_date');
            $table->unsignedInteger('sequence')->nullable()->after('year');
            $table->index(['year', 'sequence']);
        });

        // Backfill existing rows with year and sequential numbers per year
        if (DB::getDriverName() === 'mysql') {
            DB::statement('UPDATE kas_tunais SET year = YEAR(transaction_date) WHERE transaction_date IS NOT NULL');
            DB::statement('
                UPDATE kas_tunais kt
                JOIN (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY YEAR(transaction_date) ORDER BY transaction_date ASC, id ASC) AS seq
                    FROM kas_tunais
                ) sub ON kt.id = sub.id
                SET kt.sequence = sub.seq
            ');
        } else {
            $records = DB::table('kas_tunais')
                ->select('id', 'transaction_date')
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $seqs = [];
            foreach ($records as $r) {
                $year = (int) date('Y', strtotime($r->transaction_date ?: 'now'));
                if (! isset($seqs[$year])) {
                    $seqs[$year] = 0;
                }
                $seqs[$year]++;
                DB::table('kas_tunais')->where('id', $r->id)->update([
                    'year' => $year,
                    'sequence' => $seqs[$year],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_tunais', function (Blueprint $table) {
            $table->dropIndex(['year', 'sequence']);
            $table->dropColumn(['year', 'sequence']);
        });
    }
};

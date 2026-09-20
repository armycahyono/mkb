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
            Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                if (! Schema::hasColumn('bos_rencana_belanjas', 'no_nota')) {
                    $table->string('no_nota', 50)->nullable()->after('id')->index();
                }
            });

            // Backfill existing records in bos_rencana_belanjas
            $groups = DB::table('bos_rencana_belanjas')->orderBy('id', 'asc')->get();
            foreach ($groups as $group) {
                $year = $group->year ?? date('Y');
                $monthNum = date('m');
                $months = [
                    'Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04',
                    'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08',
                    'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12',
                ];
                if (! empty($group->month) && isset($months[$group->month])) {
                    $monthNum = $months[$group->month];
                }
                $noNota = sprintf('NOTA/%s/%s/%04d', $year, $monthNum, $group->id);
                DB::table('bos_rencana_belanjas')
                    ->where('id', $group->id)
                    ->whereNull('no_nota')
                    ->update(['no_nota' => $noNota]);
            }
        }

        if (Schema::hasTable('bos_realisasies')) {
            Schema::table('bos_realisasies', function (Blueprint $table) {
                if (! Schema::hasColumn('bos_realisasies', 'no_nota')) {
                    $table->string('no_nota', 50)->nullable()->after('no_transaksi')->index();
                }
                if (! Schema::hasColumn('bos_realisasies', 'bos_rencana_belanja_id')) {
                    $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable()->after('no_nota')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bos_realisasies')) {
            Schema::table('bos_realisasies', function (Blueprint $table) {
                if (Schema::hasColumn('bos_realisasies', 'bos_rencana_belanja_id')) {
                    $table->dropColumn('bos_rencana_belanja_id');
                }
                if (Schema::hasColumn('bos_realisasies', 'no_nota')) {
                    $table->dropColumn('no_nota');
                }
            });
        }

        if (Schema::hasTable('bos_rencana_belanjas')) {
            Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                if (Schema::hasColumn('bos_rencana_belanjas', 'no_nota')) {
                    $table->dropColumn('no_nota');
                }
            });
        }
    }
};

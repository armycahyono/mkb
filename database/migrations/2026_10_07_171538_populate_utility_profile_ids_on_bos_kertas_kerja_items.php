<?php

use App\Models\BosKertasKerjaItem;
use App\Models\UtilityProfile;
use App\Models\UtilityReceipt;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $profiles = UtilityProfile::all()->keyBy('code');

        $pListrikStm = $profiles->get('listrik_stm')?->id;
        $pListrikMasjid = $profiles->get('listrik_masjid')?->id;
        $pTokenSmk = $profiles->get('token_smk_kras')?->id;
        $pTokenStm = $profiles->get('token_stm_pgri')?->id;
        $pSpeedyImam = $profiles->get('speedy_imam')?->id;
        $pSpeedySmk = $profiles->get('speedy_smk_kras')?->id;
        $pInternetMenaksopal = $profiles->get('internet_menaksopal')?->id;

        // 1. Sinkronisasi utility_profile_id pada bos_kertas_kerja_items
        if ($pListrikStm) {
            BosKertasKerjaItem::where('description', 'like', '%Tarif Listrik Golongan Rumah Tangga Besar%')
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pListrikStm]);
        }

        if ($pListrikMasjid) {
            BosKertasKerjaItem::where('description', 'like', '%Tarif Listrik Golongan Rumah Tangga Kecil%')
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pListrikMasjid]);
        }

        if ($pTokenSmk) {
            BosKertasKerjaItem::where('description', 'like', '%Tarif Listrik Golongan Rumah Tangga Menengah%')
                ->where(function ($q) {
                    $q->where('total_amount', '>', 700000)->orWhere('price', '>', 700000);
                })
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pTokenSmk]);
        }

        if ($pTokenStm) {
            BosKertasKerjaItem::where('description', 'like', '%Tarif Listrik Golongan Rumah Tangga Menengah%')
                ->where(function ($q) {
                    $q->where('total_amount', '<=', 700000)->where('price', '<=', 700000);
                })
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pTokenStm]);
        }

        if ($pSpeedyImam) {
            BosKertasKerjaItem::where('description', 'like', '%Abonemen Telepon%')
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pSpeedyImam]);
        }

        if ($pSpeedySmk) {
            BosKertasKerjaItem::where('description', 'like', '%Langganan Internet + Telepon%')
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pSpeedySmk]);
        }

        if ($pInternetMenaksopal) {
            BosKertasKerjaItem::where('description', 'like', '%Langganan Internet - Dedicated%')
                ->whereNull('utility_profile_id')
                ->update(['utility_profile_id' => $pInternetMenaksopal]);
        }

        // 2. Hubungkan struk yang utility_profile_id dan bulannya jelas ke item Kertas Kerja yang sesuai
        $receipts = UtilityReceipt::with('profile')->where('fiscal_year', 2026)->get();
        $indonesianMonths = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
            '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
            '9' => 'September',
        ];

        foreach ($receipts as $r) {
            if (! $r->utility_profile_id || ($r->profile && ! $r->profile->is_budgeted)) {
                if ($r->bos_kertas_kerja_item_id) {
                    $r->update(['bos_kertas_kerja_item_id' => null]);
                }

                continue;
            }

            $monthStr = $r->billing_month;
            if (isset($indonesianMonths[$monthStr])) {
                $monthStr = $indonesianMonths[$monthStr];
            }

            // Cari KK item yang pas untuk profil dan bulan ini
            $kkItem = BosKertasKerjaItem::where('year', $r->fiscal_year)
                ->where('month', $monthStr)
                ->where('utility_profile_id', $r->utility_profile_id)
                ->whereHas('file', fn ($f) => $f->where('is_active', true))
                ->first();

            if ($kkItem && $r->bos_kertas_kerja_item_id !== $kkItem->id) {
                $r->update(['bos_kertas_kerja_item_id' => $kkItem->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        BosKertasKerjaItem::whereNotNull('utility_profile_id')->update(['utility_profile_id' => null]);
    }
};

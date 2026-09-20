<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\KasTunaiResource;
use App\Models\KasTunai;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', FiscalYearHelper::getActiveYear());
        $fundingSource = $request->query('funding_source', 'BOSP');

        $saldoKas = KasTunai::getAvailableCashBalance($fundingSource);

        $totalPanjarPending = KasTunai::where('funding_source', $fundingSource)
            ->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->sum('amount');

        // Check if there are settlements reducing the totalPanjarPending
        // Actually, KasTunai model might have a remaining_panjar_amount accessor, or we can just calculate it simply
        // For dashboard simplicity, let's use the sum of amount, but if there's partial settlement, it should be adjusted
        // Assuming remaining_panjar_amount is the actual outstanding

        $panjars = KasTunai::where('funding_source', $fundingSource)
            ->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->with('childKass')
            ->get();

        $totalPanjarPending = $panjars->sum(function ($kas) {
            return $kas->remaining_panjar_amount ?? $kas->amount;
        });

        $kasSiapPakai = $saldoKas - $totalPanjarPending;

        $today = now()->toDateString();

        $todayTransactions = KasTunai::where('funding_source', $fundingSource)
            ->whereDate('transaction_date', $today)
            ->get();

        $transaksiHariIni = $todayTransactions->count();
        $totalKeluarHariIni = $todayTransactions->where('transaction_type', 'keluar')->sum('amount');
        $totalMasukHariIni = $todayTransactions->where('transaction_type', 'masuk')->sum('amount');

        $lastTransactions = KasTunai::where('funding_source', $fundingSource)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->with(['kasCategory', 'guru'])
            ->get();

        $kasbonCount = KasTunai::where('funding_source', $fundingSource)
            ->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->count();

        return response()->json([
            'saldo_kas' => $saldoKas,
            'total_panjar_pending' => $totalPanjarPending,
            'kas_siap_pakai' => $kasSiapPakai,
            'transaksi_hari_ini' => $transaksiHariIni,
            'total_keluar_hari_ini' => $totalKeluarHariIni,
            'total_masuk_hari_ini' => $totalMasukHariIni,
            'last_transactions' => KasTunaiResource::collection($lastTransactions),
            'kasbon_count' => $kasbonCount,
        ]);
    }
}

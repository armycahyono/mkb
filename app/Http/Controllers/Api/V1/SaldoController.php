<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Models\KasTunai;
use Illuminate\Http\Request;

class SaldoController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', FiscalYearHelper::getActiveYear());

        return response()->json([
            'bosp' => $this->getSaldoForSource('BOSP', $year),
            'bpopp' => $this->getSaldoForSource('BPOPP', $year),
        ]);
    }

    private function getSaldoForSource(string $source, $year): array
    {
        $saldoKas = KasTunai::getAvailableCashBalance($source);

        $panjars = KasTunai::where('funding_source', $source)
            ->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->with('childKass')
            ->get();

        $totalPanjarPending = $panjars->sum(function ($kas) {
            return $kas->remaining_panjar_amount ?? $kas->amount;
        });

        $kasSiapPakai = $saldoKas - $totalPanjarPending;

        return [
            'saldo_kas' => $saldoKas,
            'total_panjar_pending' => $totalPanjarPending,
            'kas_siap_pakai' => $kasSiapPakai,
        ];
    }
}

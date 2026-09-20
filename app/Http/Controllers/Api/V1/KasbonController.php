<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\KasbonResource;
use App\Models\KasTunai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', FiscalYearHelper::getActiveYear());
        $fundingSource = $request->query('funding_source', 'BOSP');
        $status = $request->query('status'); // e.g. "pending,partial"
        $page = $request->query('page', 1);

        $query = KasTunai::where('funding_source', $fundingSource)
            ->where('category', 'panjar_kerja')
            ->where('year', $year)
            ->with(['guru', 'childKass']);

        if ($status) {
            $statuses = explode(',', $status);
            $query->whereIn('panjar_status', $statuses);
        }

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        return KasbonResource::collection($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'person_name' => 'required|string',
            'guru_id' => 'nullable|exists:gurus,id',
            'funding_source' => 'required|string',
        ]);

        $saldo = KasTunai::getAvailableCashBalance($validated['funding_source']);
        if ($validated['amount'] > $saldo) {
            throw ValidationException::withMessages([
                'amount' => ['Saldo tidak mencukupi untuk memberikan kasbon.'],
            ]);
        }

        $validated['transaction_type'] = 'keluar';
        $validated['category'] = 'panjar_kerja';
        $validated['panjar_status'] = 'pending';
        $validated['year'] = FiscalYearHelper::getActiveYear();

        $kasbon = KasTunai::create($validated);

        return new KasbonResource($kasbon->load('guru'));
    }

    public function show(KasTunai $kasTunai)
    {
        if ($kasTunai->category !== 'panjar_kerja') {
            abort(404, 'Bukan data kasbon.');
        }

        return new KasbonResource($kasTunai->load(['guru', 'childKass']));
    }

    public function settle(Request $request, KasTunai $kasTunai)
    {
        if ($kasTunai->category !== 'panjar_kerja') {
            abort(400, 'Bukan data kasbon.');
        }

        if (in_array($kasTunai->panjar_status, ['settled', 'void'])) {
            throw ValidationException::withMessages([
                'kasTunai' => ['Kasbon ini sudah diselesaikan atau dibatalkan.'],
            ]);
        }

        $validated = $request->validate([
            'receipts' => 'nullable|array',
            'receipts.*.description' => 'required|string',
            'receipts.*.amount' => 'required|numeric|min:1',
            'receipts.*.person_name' => 'nullable|string',
            'cash_return_amount' => 'nullable|numeric|min:0',
            'is_partial' => 'boolean',
        ]);

        $receipts = $validated['receipts'] ?? [];
        $cashReturnAmount = $validated['cash_return_amount'] ?? 0;
        $isPartial = $validated['is_partial'] ?? false;

        $totalReceipts = collect($receipts)->sum('amount');
        $totalSettled = $totalReceipts + $cashReturnAmount;

        $previouslySettled = $kasTunai->childKass->where('transaction_type', 'keluar')->sum('amount')
                           - $kasTunai->childKass->where('transaction_type', 'masuk')->sum('amount');

        $remainingAmount = $kasTunai->amount - $previouslySettled;

        if (! $isPartial && $totalSettled != $remainingAmount) {
            throw ValidationException::withMessages([
                'amount' => ['Total penyelesaian (belanja + sisa kas) harus sama dengan sisa kasbon (Rp '.number_format($remainingAmount, 0, ',', '.').').'],
            ]);
        }

        DB::transaction(function () use ($kasTunai, $receipts, $cashReturnAmount, $isPartial) {
            $year = FiscalYearHelper::getActiveYear();

            // Create expense entries (belanja_tunai)
            foreach ($receipts as $receipt) {
                KasTunai::create([
                    'transaction_date' => now(),
                    'transaction_type' => 'keluar',
                    'category' => 'belanja_tunai',
                    'amount' => $receipt['amount'],
                    'description' => 'Penyelesaian Panjar: '.$receipt['description'],
                    'person_name' => $receipt['person_name'] ?? $kasTunai->person_name,
                    'guru_id' => $kasTunai->guru_id,
                    'funding_source' => $kasTunai->funding_source,
                    'parent_kas_id' => $kasTunai->id,
                    'year' => $year,
                ]);
            }

            // Create return entry (pengembalian_panjar)
            if ($cashReturnAmount > 0) {
                KasTunai::create([
                    'transaction_date' => now(),
                    'transaction_type' => 'masuk',
                    'category' => 'pengembalian_panjar',
                    'amount' => $cashReturnAmount,
                    'description' => 'Pengembalian Sisa Panjar',
                    'person_name' => $kasTunai->person_name,
                    'guru_id' => $kasTunai->guru_id,
                    'funding_source' => $kasTunai->funding_source,
                    'parent_kas_id' => $kasTunai->id,
                    'year' => $year,
                ]);
            }

            $kasTunai->panjar_status = $isPartial ? 'partial' : 'settled';
            $kasTunai->save();
        });

        return new KasbonResource($kasTunai->fresh(['guru', 'childKass']));
    }
}

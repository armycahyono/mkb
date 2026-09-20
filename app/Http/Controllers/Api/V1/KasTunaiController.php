<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\KasTunaiResource;
use App\Models\KasTunai;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KasTunaiController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', FiscalYearHelper::getActiveYear());
        $fundingSource = $request->query('funding_source', 'BOSP');
        $month = $request->query('month');
        $category = $request->query('category');
        $transactionType = $request->query('transaction_type');
        $perPage = $request->query('per_page', 20);

        $query = KasTunai::where('funding_source', $fundingSource)
            ->where('year', $year)
            ->with(['kasCategory', 'guru']);

        if ($month) {
            $query->whereMonth('transaction_date', $month);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        $kasTunai = $query->paginate($perPage);

        return KasTunaiResource::collection($kasTunai)->additional([
            'summary' => [
                'total_masuk' => (clone $query)->where('transaction_type', 'masuk')->sum('amount'),
                'total_keluar' => (clone $query)->where('transaction_type', 'keluar')->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:masuk,keluar',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'person_name' => 'nullable|string',
            'guru_id' => 'nullable|exists:gurus,id',
            'funding_source' => 'nullable|string',
            'evidence_number' => 'nullable|string',
        ]);

        $fundingSource = $validated['funding_source'] ?? 'BOSP';
        $validated['funding_source'] = $fundingSource;
        $validated['year'] = FiscalYearHelper::getActiveYear();

        if ($validated['transaction_type'] === 'keluar') {
            $saldo = KasTunai::getAvailableCashBalance($fundingSource);
            if ($validated['amount'] > $saldo) {
                throw ValidationException::withMessages([
                    'amount' => ['Saldo tidak mencukupi. Saldo saat ini: Rp '.number_format($saldo, 0, ',', '.')],
                ]);
            }
        }

        $kasTunai = KasTunai::create($validated);

        return new KasTunaiResource($kasTunai->load(['kasCategory', 'guru']));
    }

    public function show(KasTunai $kasTunai)
    {
        return new KasTunaiResource($kasTunai->load(['kasCategory', 'guru', 'items']));
    }
}

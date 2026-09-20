<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UtilityProfileResource;
use App\Http\Resources\Api\V1\UtilityReceiptResource;
use App\Models\UtilityProfile;
use App\Models\UtilityReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UtilityController extends Controller
{
    /**
     * Display a listing of active utility profiles.
     */
    public function profiles(): JsonResponse
    {
        $profiles = UtilityProfile::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => UtilityProfileResource::collection($profiles),
        ]);
    }

    /**
     * Display a listing of utility receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?: FiscalYearHelper::getActiveYear());
        $profileId = $request->input('utility_profile_id');
        $billingMonth = $request->input('billing_month');
        $perPage = min(50, max(1, (int) ($request->input('per_page') ?: 20)));

        $query = UtilityReceipt::with('utilityProfile')
            ->where('fiscal_year', $year);

        if ($profileId) {
            $query->where('utility_profile_id', $profileId);
        }

        if ($billingMonth) {
            $query->where('billing_month', $billingMonth);
        }

        $receipts = $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => UtilityReceiptResource::collection($receipts),
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'last_page' => $receipts->lastPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
                'year' => $year,
            ],
        ]);
    }

    /**
     * Store a newly created utility receipt with photo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'utility_profile_id' => 'required|exists:utility_profiles,id',
            'fiscal_year' => 'nullable|integer',
            'billing_month' => 'required|integer|between:1,12',
            'transaction_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:100',
            'token_number' => 'nullable|string|max:100',
            'total_amount' => 'required|numeric|min:1',
            'receipt_file' => 'nullable|image|max:10240', // max 10MB
            'notes' => 'nullable|string',
        ]);

        $profile = UtilityProfile::findOrFail($validated['utility_profile_id']);
        $year = (int) ($validated['fiscal_year'] ?? FiscalYearHelper::getActiveYear());
        $user = Auth::user();

        $filePath = null;
        if ($request->hasFile('receipt_file')) {
            $file = $request->file('receipt_file');
            $filename = time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs("utility_receipts/{$year}/{$profile->code}", $filename, 'public');
        }

        $receipt = UtilityReceipt::create([
            'utility_profile_id' => $profile->id,
            'fiscal_year' => $year,
            'billing_month' => $validated['billing_month'],
            'transaction_date' => $validated['transaction_date'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'customer_name' => $profile->customer_name,
            'customer_number' => $profile->customer_number ?? $profile->idpel,
            'meter_number' => $profile->meter_number,
            'tariff_power' => $profile->tariff_power,
            'token_number' => $validated['token_number'] ?? null,
            'total_amount' => $validated['total_amount'],
            'base_amount' => $validated['total_amount'],
            'receipt_file' => $filePath,
            'is_paid' => false,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        $receipt->load('utilityProfile');

        return response()->json([
            'status' => 'success',
            'message' => 'Tagihan utilitas berhasil disimpan.',
            'data' => new UtilityReceiptResource($receipt),
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TntResource;
use App\Models\PencairanBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TntController extends Controller
{
    /**
     * Display a listing of PencairanBank (Surat TNT).
     */
    public function index(Request $request): JsonResponse
    {
        $fundingSource = strtoupper($request->input('funding_source') ?: 'BOSP');
        if ($fundingSource === 'BOS') {
            $fundingSource = 'BOSP';
        }

        $year = (int) ($request->input('year') ?: FiscalYearHelper::getActiveYear());
        $status = $request->input('status');
        $search = $request->input('search');
        $perPage = min(50, max(1, (int) ($request->input('per_page') ?: 20)));

        $query = PencairanBank::with('items')
            ->withCount('items')
            ->where(function ($q) use ($fundingSource) {
                if ($fundingSource === 'BOSP') {
                    $q->whereIn('funding_source', ['BOS', 'BOSP']);
                } else {
                    $q->where('funding_source', $fundingSource);
                }
            })
            ->whereYear('letter_date', $year);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('letter_number', 'like', "%{$search}%")
                    ->orWhere('source_account_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => TntResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'funding_source' => $fundingSource,
                'year' => $year,
            ],
        ]);
    }

    /**
     * Display the specified PencairanBank with items.
     */
    public function show(int $id): JsonResponse
    {
        $tnt = PencairanBank::with('items')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new TntResource($tnt),
        ]);
    }
}

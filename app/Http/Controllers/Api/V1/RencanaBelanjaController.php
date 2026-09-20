<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RencanaBelanjaResource;
use App\Models\BosRencanaBelanja;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RencanaBelanjaController extends Controller
{
    /**
     * Display a listing of BosRencanaBelanja.
     */
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?: FiscalYearHelper::getActiveYear());
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        $status = $request->input('status');
        $search = $request->input('search');
        $perPage = min(50, max(1, (int) ($request->input('per_page') ?: 20)));

        $query = BosRencanaBelanja::with(['supplier', 'items.kertasKerjaItem'])
            ->withCount('items')
            ->where('year', $year);

        if ($month) {
            $query->where('month', $month);
        }

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhere('no_nota', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('planned_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => RencanaBelanjaResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'year' => $year,
            ],
        ]);
    }

    /**
     * Display the specified BosRencanaBelanja with its items.
     */
    public function show(int $id): JsonResponse
    {
        $rencana = BosRencanaBelanja::with(['supplier', 'items.kertasKerjaItem'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new RencanaBelanjaResource($rencana),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\KertasKerjaResource;
use App\Models\BosKertasKerjaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KertasKerjaController extends Controller
{
    /**
     * Display a listing of BosKertasKerjaItem (Read-Only).
     */
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?: FiscalYearHelper::getActiveYear());
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        $search = $request->input('search');
        $status = $request->input('status'); // belum, sebagian, selesai
        $perPage = min(100, max(1, (int) ($request->input('per_page') ?: 20)));

        $query = BosKertasKerjaItem::with('realisasis')
            ->where('year', $year);

        if ($month) {
            $query->where('month', $month);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kegiatan', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('account_code', 'like', "%{$search}%")
                    ->orWhere('program_code', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('month')
            ->orderBy('account_code')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => KertasKerjaResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }
}

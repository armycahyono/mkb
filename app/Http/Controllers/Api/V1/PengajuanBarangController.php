<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PengajuanBarangResource;
use App\Models\PengajuanBarang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengajuanBarangController extends Controller
{
    /**
     * Display a listing of PengajuanBarang.
     */
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?: FiscalYearHelper::getActiveYear());
        $search = $request->input('search');
        $onlyMine = $request->boolean('only_mine', false);
        $perPage = min(50, max(1, (int) ($request->input('per_page') ?: 20)));

        $query = PengajuanBarang::with(['pemohon', 'guru'])
            ->where('tahun_anggaran', $year);

        if ($onlyMine && Auth::check()) {
            $user = Auth::user();
            $query->where(function ($q) use ($user) {
                $q->where('pemohon_id', $user->id);
                if ($user->guru_id) {
                    $q->orWhere('guru_id', $user->guru_id);
                }
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => PengajuanBarangResource::collection($items),
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
     * Store a newly created PengajuanBarang.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'volume' => 'required|numeric|min:0.01',
            'satuan' => 'required|string|max:50',
            'harga' => 'required|numeric|min:0',
            'kegiatan' => 'nullable|string|max:255',
            'bulan_pakai' => 'nullable|integer|between:1,12',
            'keterangan' => 'nullable|string',
            'tahun_anggaran' => 'nullable|integer',
        ]);

        $year = (int) ($validated['tahun_anggaran'] ?? FiscalYearHelper::getActiveYear());
        $user = Auth::user();
        $jumlah = (float) $validated['volume'] * (float) $validated['harga'];

        $pengajuan = PengajuanBarang::create([
            'tahun_anggaran' => $year,
            'pemohon_id' => $user->id,
            'guru_id' => $user->guru_id,
            'nama_barang' => $validated['nama_barang'],
            'spesifikasi' => $validated['spesifikasi'] ?? null,
            'volume' => $validated['volume'],
            'satuan' => $validated['satuan'],
            'harga' => $validated['harga'],
            'jumlah' => $jumlah,
            'kegiatan' => $validated['kegiatan'] ?? null,
            'bulan_pakai' => $validated['bulan_pakai'] ?? (int) date('n'),
            'keterangan' => $validated['keterangan'] ?? null,
            'realisasi' => false,
        ]);

        $pengajuan->load(['pemohon', 'guru']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan barang berhasil dikirim.',
            'data' => new PengajuanBarangResource($pengajuan),
        ], 201);
    }
}

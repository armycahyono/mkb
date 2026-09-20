<?php

namespace App\Http\Controllers;

use App\Models\ArkasBarang;
use App\Models\ArkasKegiatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ArkasReferenceController extends Controller
{
    /**
     * Tampilkan halaman utama Referensi ARKAS dengan 2 Tab.
     */
    public function index(Request $request): View
    {
        $snps = ArkasKegiatan::select('snp')
            ->whereNotNull('snp')
            ->where('snp', '!=', '')
            ->distinct()
            ->orderBy('snp')
            ->pluck('snp');

        $komponens = ArkasKegiatan::select('komponen')
            ->whereNotNull('komponen')
            ->where('komponen', '!=', '')
            ->distinct()
            ->orderBy('komponen')
            ->pluck('komponen');

        $jenisBarangs = ArkasBarang::select('jenis_barang')
            ->whereNotNull('jenis_barang')
            ->where('jenis_barang', '!=', '')
            ->distinct()
            ->orderBy('jenis_barang')
            ->pluck('jenis_barang');

        $kodeRekenings = ArkasBarang::select('kode_rekening')
            ->whereNotNull('kode_rekening')
            ->where('kode_rekening', '!=', '')
            ->distinct()
            ->orderBy('kode_rekening')
            ->pluck('kode_rekening');

        $totalKegiatan = ArkasKegiatan::count();
        $totalBarang = ArkasBarang::count();

        return view('bos.referensi-arkas.index', compact(
            'snps',
            'komponens',
            'jenisBarangs',
            'kodeRekenings',
            'totalKegiatan',
            'totalBarang'
        ));
    }

    /**
     * DataTables Server-side untuk Tab Kegiatan
     */
    public function dataKegiatan(Request $request): JsonResponse
    {
        $query = ArkasKegiatan::query();

        if ($request->filled('snp')) {
            $query->where('snp', $request->snp);
        }

        if ($request->filled('komponen')) {
            $query->where('komponen', $request->komponen);
        }

        if ($request->filled('search_custom')) {
            $search = $request->search_custom;
            $query->where(function ($q) use ($search) {
                $q->where('kode_kegiatan', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%")
                    ->orWhere('definisi_operasional', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('snp_badge', function ($row) {
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.76rem;">'.e($row->snp).'</span>';
            })
            ->addColumn('kode_kegiatan_display', function ($row) {
                return '<span class="font-monospace fw-bold text-dark" style="letter-spacing: 0.5px;">'.e($row->kode_kegiatan).'</span>';
            })
            ->addColumn('definisi_display', function ($row) {
                if (empty($row->definisi_operasional)) {
                    return '<span class="text-muted fst-italic" style="font-size: 0.78rem;">- Tidak ada keterangan -</span>';
                }

                $preview = Str::limit(e($row->definisi_operasional), 85);
                $escapedFull = htmlspecialchars($row->definisi_operasional, ENT_QUOTES, 'UTF-8');
                $escapedKegiatan = htmlspecialchars($row->kegiatan, ENT_QUOTES, 'UTF-8');
                $escapedKode = htmlspecialchars($row->kode_kegiatan, ENT_QUOTES, 'UTF-8');

                return '
                    <div style="font-size: 0.8125rem; line-height: 1.35; color: #495057; margin-bottom: 4px;">'.$preview.'</div>
                    <button type="button" class="btn btn-xs btn-outline-primary btn-lihat-definisi py-0 px-1" style="font-size: 0.72rem; font-weight: 600;"
                        data-kode="'.$escapedKode.'"
                        data-kegiatan="'.$escapedKegiatan.'"
                        data-definisi="'.$escapedFull.'">
                        <i class="fas fa-circle-info mr-1"></i>Lihat Lengkap
                    </button>
                ';
            })
            ->rawColumns(['snp_badge', 'kode_kegiatan_display', 'definisi_display'])
            ->make(true);
    }

    /**
     * DataTables Server-side untuk Tab Referensi Barang
     */
    public function dataBarang(Request $request): JsonResponse
    {
        $query = ArkasBarang::query();

        if ($request->filled('jenis_barang')) {
            $query->where('jenis_barang', $request->jenis_barang);
        }

        if ($request->filled('kode_rekening')) {
            $query->where('kode_rekening', $request->kode_rekening);
        }

        if ($request->filled('search_custom')) {
            $search = $request->search_custom;
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_rekening', 'like', "%{$search}%")
                    ->orWhere('satuan', 'like', "%{$search}%")
                    ->orWhere('kode_belanja', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('jenis_badge', function ($row) {
                $color = strtolower($row->jenis_barang) === 'modal' ? 'badge-success' : 'badge-secondary';

                return '<span class="badge '.$color.'" style="font-size: 0.65rem; font-weight: 600; padding: 2px 6px; letter-spacing: 0.3px;">'.e($row->jenis_barang).'</span>';
            })
            ->addColumn('kode_rekening_display', function ($row) {
                return '<span class="font-monospace text-secondary" style="font-size: 0.8125rem; letter-spacing: 0.3px;">'.e($row->kode_rekening).'</span>';
            })
            ->addColumn('nama_barang_display', function ($row) {
                return '<span class="text-dark" style="font-size: 0.82rem; font-weight: 400;">'.e($row->nama_barang).'</span>';
            })
            ->addColumn('harga_range_display', function ($row) {
                return '<span class="font-tabular text-dark" style="font-size: 0.82rem; font-weight: 500;">'.e($row->harga_range_display).'</span>';
            })
            ->addColumn('kode_belanja_display', function ($row) {
                if (empty($row->kode_belanja)) {
                    return '-';
                }

                return '<span class="text-secondary" style="font-size: 0.8125rem;">'.e($row->kode_belanja).'</span>';
            })
            ->addColumn('keterangan_varian', function ($row) {
                return '<span class="text-muted" style="font-size: 0.78rem;">'.e($row->varian_count).' varian harga</span>';
            })
            ->rawColumns(['jenis_badge', 'kode_rekening_display', 'nama_barang_display', 'harga_range_display', 'kode_belanja_display', 'keterangan_varian'])
            ->make(true);
    }
}

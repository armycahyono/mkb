<?php

namespace App\Http\Controllers;

use App\Exports\BosKertasKerjaExport;
use App\Imports\BosKertasKerjaImport;
use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Services\BosKertasKerjaParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class BosKertasKerjaDataController extends Controller
{
    public function index(Request $request)
    {
        $selectedYear = $request->input('year', (string) session('fiscal_year', date('Y')));

        if ($request->ajax()) {
            $query = BosKertasKerjaItem::query()
                ->when($selectedYear && $selectedYear !== 'all', function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear);
                })
                ->when($request->month, function ($q) use ($request) {
                    $q->where('month', $request->month);
                })
                ->when($request->kegiatan, function ($q) use ($request) {
                    $q->where('kegiatan', 'like', '%'.$request->kegiatan.'%');
                })
                ->when($request->version_filter !== 'all', function ($q) use ($request, $selectedYear) {
                    if (is_numeric($request->version_filter)) {
                        $q->where('bos_kertas_kerja_file_id', $request->version_filter);
                    } elseif ($request->version_filter === 'archived') {
                        $inactiveIds = BosKertasKerjaFile::where('is_active', false)
                            ->when($selectedYear && $selectedYear !== 'all', fn ($f) => $f->where('year', $selectedYear))
                            ->pluck('id');
                        $q->whereIn('bos_kertas_kerja_file_id', $inactiveIds);
                    } elseif ($request->filled('file_id')) {
                        $q->where('bos_kertas_kerja_file_id', $request->file_id);
                    } else {
                        // Default: hanya tampilkan versi aktif
                        $activeIds = BosKertasKerjaFile::where('is_active', true)
                            ->when($selectedYear && $selectedYear !== 'all', fn ($f) => $f->where('year', $selectedYear))
                            ->pluck('id');
                        $q->whereIn('bos_kertas_kerja_file_id', $activeIds);
                    }
                })
                ->when(! $request->has('order'), function ($q) {
                    $monthOrderSql = "CASE month
                        WHEN 'Januari' THEN 1
                        WHEN 'Februari' THEN 2
                        WHEN 'Maret' THEN 3
                        WHEN 'April' THEN 4
                        WHEN 'Mei' THEN 5
                        WHEN 'Juni' THEN 6
                        WHEN 'Juli' THEN 7
                        WHEN 'Agustus' THEN 8
                        WHEN 'September' THEN 9
                        WHEN 'Oktober' THEN 10
                        WHEN 'November' THEN 11
                        WHEN 'Desember' THEN 12
                        ELSE 13
                    END";
                    $q->orderBy('year', 'desc')
                        ->orderByRaw("{$monthOrderSql} ASC")
                        ->orderBy('id', 'asc');
                });

            $totalGrandFiltered = (float) $query->clone()->sum('total_amount');

            return DataTables::of($query)
                ->addIndexColumn()
                ->setRowClass(function ($row) {
                    return $row->is_exhausted ? 'row-exhausted' : '';
                })
                ->addColumn('is_exhausted', function ($row) {
                    return $row->is_exhausted;
                })
                ->addColumn('arkas_id_display', function ($row) {
                    return $row->arkas_id ?? $row->id;
                })
                ->addColumn('kegiatan_display', function ($row) {
                    $keg = e($row->kegiatan);

                    return '<span class="d-inline-block text-truncate text-start" style="max-width: 140px; text-align: left;" data-toggle="tooltip" data-placement="top" title="'.$keg.'">'.$keg.'</span>';
                })
                ->addColumn('volume_display', function ($row) {
                    $vol = (float) $row->volume == (int) $row->volume
                        ? number_format($row->volume, 0, ',', '.')
                        : number_format($row->volume, 2, ',', '.');

                    $unitHtml = $row->unit ? ' <span class="text-muted small">'.e($row->unit).'</span>' : '';

                    return '<span class="font-tabular font-weight-bold">'.$vol.'</span>'.$unitHtml;
                })
                ->addColumn('price_display', function ($row) {
                    return number_format($row->price, 0, ',', '.');
                })
                ->addColumn('total_amount_display', function ($row) {
                    return number_format($row->total_amount, 0, ',', '.');
                })
                ->addColumn('action', function ($row) {
                    if ($row->is_locked) {
                        $title = $row->is_exhausted
                            ? 'Terkunci: Alokasi Anggaran Bulan Ini Sudah Terserap Habis'
                            : 'Terkunci: Sudah Mulai Terealisasi (Tidak Dapat Diedit/Dihapus)';
                        $lockColor = $row->is_exhausted ? '#8a58dc' : '#f59e0b';
                        $btn = '<span class="d-inline-flex align-items-center justify-content-center" style="color: '.$lockColor.'; font-size: 0.85rem;" data-bs-toggle="tooltip" data-toggle="tooltip" title="'.$title.'"><i class="fas fa-lock"></i></span>';
                    } else {
                        $editData = [
                            'id' => $row->id,
                            'arkas_id' => $row->arkas_id,
                            'account_code' => $row->account_code,
                            'program_code' => $row->program_code,
                            'kegiatan' => $row->kegiatan,
                            'description' => $row->description,
                            'volume' => $row->volume,
                            'unit' => $row->unit,
                            'price' => $row->price,
                        ];
                        $btn = '<button class="btn btn-warning btn-xs btn-edit mr-1" data-id="'.$row->id.'" data-json="'.e(json_encode($editData)).'" title="Edit Rincian"><i class="fas fa-edit"></i></button>';
                        $btn .= '<button class="btn btn-danger btn-xs btn-delete" data-url="'.route('bos.data.destroy', $row->id).'" title="Hapus Rincian"><i class="fas fa-trash"></i></button>';
                    }

                    return $btn;
                })
                ->with('grandTotal', 'Rp '.number_format($totalGrandFiltered, 0, ',', '.'))
                ->rawColumns(['action', 'kegiatan_display', 'volume_display'])
                ->make(true);
        }

        $years = BosKertasKerjaItem::select('year')->distinct()->pluck('year')
            ->concat(BosKertasKerjaFile::select('year')->distinct()->pluck('year'))
            ->unique()
            ->sortDesc()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([(int) $selectedYear]);
        }
        $files = BosKertasKerjaFile::orderBy('year', 'desc')->orderBy('month', 'asc')->get();
        $filesData = $files->map(fn ($f) => [
            'id' => $f->id,
            'year' => (int) $f->year,
            'month' => $f->month,
            'version' => $f->version,
            'is_active' => (bool) $f->is_active,
            'filename' => $f->filename,
        ]);

        return view('bos.data.index', compact('years', 'files', 'filesData', 'selectedYear'));
    }

    public function store(Request $request)
    {
        // Option 1: Regenerate/re-parse PDF items from selected file
        if ($request->has('action') && $request->action === 'parse_file') {
            $request->validate([
                'bos_kertas_kerja_file_id' => 'required|exists:bos_kertas_kerja_files,id',
            ]);

            $file = BosKertasKerjaFile::findOrFail($request->bos_kertas_kerja_file_id);
            $fullPath = Storage::disk('public')->path($file->file_path);

            if (! file_exists($fullPath)) {
                return response()->json(['message' => 'File PDF tidak ditemukan di server.'], 404);
            }

            // Smart Sync / Soft Update:
            // 1. Dapatkan daftar item ID dari file ini yang SUDAH memiliki transaksi realisasi
            $realizedItemIds = BosKertasKerjaItem::where('bos_kertas_kerja_file_id', $file->id)
                ->has('realisasis')
                ->pluck('id')
                ->toArray();

            // 2. Hapus HANYA item yang BELUM memiliki transaksi realisasi
            BosKertasKerjaItem::where('bos_kertas_kerja_file_id', $file->id)
                ->whereNotIn('id', $realizedItemIds)
                ->forceDelete();

            $extractedCount = BosKertasKerjaParserService::parsePdf($file, $fullPath);

            $preservedCount = count($realizedItemIds);
            $msg = "Berhasil menerjemahkan $extractedCount item baru dari PDF Kertas Kerja Revisi.";
            if ($preservedCount > 0) {
                $msg .= " ($preservedCount item yang sudah memiliki transaksi realisasi tetap dilindungi & dipertahankan).";
            }

            return response()->json([
                'message' => $msg,
                'count' => $extractedCount,
            ]);
        }

        // Option 2: Manual item creation
        if ($request->has('price')) {
            $request->merge(['price' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', (string) $request->price)))]);
        }
        if ($request->has('volume')) {
            $request->merge(['volume' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $request->volume))]);
        }

        $request->validate([
            'bos_kertas_kerja_file_id' => 'required|exists:bos_kertas_kerja_files,id',
            'arkas_id' => 'nullable|string|max:50',
            'account_code' => 'required|string|max:100',
            'program_code' => 'nullable|string|max:100',
            'kegiatan' => 'required|string',
            'description' => 'required|string',
            'volume' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
        ]);

        $file = BosKertasKerjaFile::findOrFail($request->bos_kertas_kerja_file_id);
        $totalAmount = $request->volume * $request->price;

        BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'arkas_id' => $request->arkas_id,
            'year' => $file->year,
            'month' => $file->month,
            'account_code' => $request->account_code,
            'program_code' => $request->program_code,
            'kegiatan' => $request->kegiatan,
            'description' => $request->description,
            'volume' => $request->volume,
            'unit' => $request->unit,
            'price' => $request->price,
            'total_amount' => $totalAmount,
        ]);

        return response()->json(['message' => 'Rincian data kertas kerja berhasil ditambahkan.']);
    }

    public function update(Request $request, BosKertasKerjaItem $item)
    {
        if ($item->is_locked) {
            return response()->json(['message' => 'Rincian Kertas Kerja ini alokasi anggarannya sudah terserap oleh transaksi realisasi dan tidak dapat diubah.'], 422);
        }

        if ($request->has('price')) {
            $request->merge(['price' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', (string) $request->price)))]);
        }
        if ($request->has('volume')) {
            $request->merge(['volume' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $request->volume))]);
        }

        $request->validate([
            'arkas_id' => 'nullable|string|max:50',
            'account_code' => 'required|string|max:100',
            'program_code' => 'nullable|string|max:100',
            'kegiatan' => 'required|string',
            'description' => 'required|string',
            'volume' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
        ]);

        $totalAmount = $request->volume * $request->price;

        $item->update([
            'arkas_id' => $request->arkas_id,
            'account_code' => $request->account_code,
            'program_code' => $request->program_code,
            'kegiatan' => $request->kegiatan,
            'description' => $request->description,
            'volume' => $request->volume,
            'unit' => $request->unit,
            'price' => $request->price,
            'total_amount' => $totalAmount,
        ]);

        return response()->json(['message' => 'Rincian data kertas kerja berhasil diperbarui.']);
    }

    public function destroy(BosKertasKerjaItem $item)
    {
        if ($item->is_locked) {
            return response()->json(['message' => 'Rincian Kertas Kerja ini tidak dapat dihapus karena alokasi anggarannya sudah terserap oleh transaksi realisasi.'], 422);
        }

        $item->delete();

        return response()->json(['message' => 'Rincian data kertas kerja berhasil dihapus.']);
    }

    public function export(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        $versionFilter = $request->query('version_filter');
        $kegiatan = $request->query('kegiatan');
        $filename = 'Kertas_Kerja_BOS_'.($year ?? 'Semua').'_'.($month ?? 'Semua').'_'.date('Ymd_His').'.xlsx';

        return Excel::download(new BosKertasKerjaExport($year, $month, $versionFilter, $kegiatan), $filename);
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new BosKertasKerjaImport;
            Excel::import($import, $request->file('excel_file'));

            $counts = $import->getCounts();
            $msg = "Berhasil mengunggah data Excel. Ditambahkan: {$counts['created']} data baru, Diperbarui: {$counts['updated']} data.";
            if ($counts['skipped'] > 0) {
                $msg .= " ({$counts['skipped']} data dilewati & dilindungi karena sudah memiliki transaksi realisasi).";
            }

            return response()->json(['message' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memproses file Excel: '.$e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use App\Models\BosRencanaBelanjaItem;
use App\Services\BosKertasKerjaParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class BosKertasKerjaFileController extends Controller
{
    public function index(Request $request)
    {
        $selectedYear = $request->input('year', (string) session('fiscal_year', date('Y')));

        if ($request->ajax()) {
            $versionFilter = $request->input('version_filter', 'active');

            $query = BosKertasKerjaFile::with('uploader')->withSum('items', 'total_amount')->withCount('items')
                ->when($selectedYear && $selectedYear !== 'all', function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear);
                })
                ->when($request->month, function ($q) use ($request) {
                    $q->where('month', $request->month);
                })
                ->when($versionFilter === 'active', function ($q) {
                    $q->where('is_active', true);
                })
                ->when($versionFilter === 'archived', function ($q) {
                    $q->where('is_active', false);
                })
                ->when($versionFilter !== 'all' && $versionFilter !== 'active' && $versionFilter !== 'archived', function ($q) use ($versionFilter) {
                    $q->where('version', $versionFilter);
                })
                ->orderBy('year', 'desc')
                ->orderByRaw("FIELD(month, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') ASC")
                ->orderBy('created_at', 'desc');

            // Hitung total anggaran dari seluruh item di tahun terpilih
            $fileIds = $query->clone()->pluck('id');
            if ($versionFilter !== 'all' && $versionFilter !== 'active') {
                $yearTotalSum = (float) BosKertasKerjaItem::whereIn('bos_kertas_kerja_file_id', $fileIds)->sum('total_amount');
            } else {
                $yearTotalSum = (float) BosKertasKerjaItem::whereIn('bos_kertas_kerja_file_id', $fileIds)
                    ->whereHas('file', function ($q) {
                        $q->where('is_active', true);
                    })
                    ->sum('total_amount');
            }

            // Hitung jumlah versi per tahun-bulan untuk menentukan tombol komparasi
            $versionCounts = BosKertasKerjaFile::selectRaw('year, month, count(*) as count')
                ->groupBy('year', 'month')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->year.'_'.$item->month => (int) $item->count];
                })
                ->toArray();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('file_url', function ($row) {
                    $base = rtrim(request()->getSchemeAndHttpHost().request()->getBasePath(), '/');

                    return $base.'/bos/files/serve/'.$row->id;
                })
                ->addColumn('version_display', function ($row) {
                    $status = $row->is_active ? 'Aktif' : 'Arsip';
                    $color = $row->is_active ? '#198754' : '#212529';
                    $title = $row->is_active ? 'Versi Aktif (Acuan Rencana & Realisasi Belanja)' : 'Arsip Riwayat Sebelum Perubahan';
                    $verText = ucfirst(e($row->version)).'-'.$status;

                    return '<span class="font-tabular font-weight-bold" style="color: '.$color.' !important; font-size: 0.8125rem;" title="'.$title.'">'.$verText.'</span>';
                })
                ->addColumn('total_amount_display', function ($row) {
                    $sum = $row->items_sum_total_amount ?? 0;

                    return '<span class="text-danger" style="color: #dc3545 !important;">Rp '.number_format($sum, 0, ',', '.').'</span>';
                })
                ->addColumn('description_display', function ($row) {
                    $count = $row->items_count ?? 0;
                    $itemBadge = '<span class="badge badge-light border border-secondary text-dark mb-1">'.$count.' item</span>';
                    $desc = e($row->description ?? '');

                    return $desc ? $itemBadge.'<br><small class="text-dark">'.$desc.'</small>' : $itemBadge;
                })
                ->addColumn('action', function ($row) use ($versionCounts) {
                    $btn = '<div class="text-right text-end text-nowrap">';

                    // Tombol Komparasi jika terdapat lebih dari 1 versi di periode (tahun & bulan) tersebut (icon saja)
                    $periodKey = $row->year.'_'.$row->month;
                    if (($versionCounts[$periodKey] ?? 0) > 1) {
                        $btn .= '<button class="btn btn-info btn-xs btn-compare mr-1" data-id="'.$row->id.'" data-year="'.$row->year.'" data-month="'.$row->month.'" data-version="'.e($row->version).'" title="Bandingkan dengan Versi Lain"><i class="fas fa-code-branch"></i></button>';
                    }

                    // Tombol Set Aktif jika berstatus arsip/non-aktif (icon saja)
                    if (! $row->is_active) {
                        $btn .= '<button class="btn btn-success btn-xs btn-set-active mr-1" data-id="'.$row->id.'" data-version="'.e($row->version).'" data-month="'.$row->month.'" data-year="'.$row->year.'" title="Jadikan Versi Aktif"><i class="fas fa-check"></i></button>';
                    }

                    $btn .= '<button class="btn btn-warning btn-xs btn-edit mr-1" data-id="'.$row->id.'" data-version="'.e($row->version).'" data-description="'.e($row->description).'" title="Edit Informasi"><i class="fas fa-edit"></i></button>';
                    $btn .= '<button class="btn btn-danger btn-xs btn-delete" data-url="'.route('bos.files.destroy', $row->id).'" title="Hapus File"><i class="fas fa-trash"></i></button>';

                    $btn .= '</div>';

                    return $btn;
                })
                ->filter(function ($query) {
                    $search = request()->input('search.value');
                    if ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('year', 'like', "%{$search}%")
                                ->orWhere('month', 'like', "%{$search}%")
                                ->orWhere('filename', 'like', "%{$search}%")
                                ->orWhere('version', 'like', "%{$search}%");
                        });
                    }
                }, true)
                ->with('grandTotal', 'Rp '.number_format($yearTotalSum, 0, ',', '.'))
                ->rawColumns(['action', 'version_display', 'total_amount_display', 'description_display'])
                ->make(true);
        }

        $years = BosKertasKerjaFile::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([(int) $selectedYear]);
        }
        $filesData = BosKertasKerjaFile::select('year', 'version')->distinct()->orderBy('version', 'asc')->get();

        return view('bos.files.index', compact('years', 'filesData', 'selectedYear'));
    }

    public function checkVersion(Request $request)
    {
        $year = (int) $request->query('year');
        $month = trim((string) $request->query('month'));

        if (! $year || ! $month) {
            return response()->json(['exists' => false, 'next_version' => 'v1.0']);
        }

        $files = BosKertasKerjaFile::where('year', $year)
            ->where('month', $month)
            ->orderBy('id', 'asc')
            ->get();

        if ($files->isEmpty()) {
            return response()->json([
                'exists' => false,
                'count' => 0,
                'latest_version' => null,
                'next_version' => 'v1.0',
                'files' => [],
            ]);
        }

        $highestNum = 1;
        foreach ($files as $f) {
            if (preg_match('/(\d+)/', $f->version, $m)) {
                $num = (int) $m[1];
                if ($num > $highestNum) {
                    $highestNum = $num;
                }
            }
        }
        $nextVersion = 'v'.($highestNum + 1).'.0';
        $latest = $files->last();

        return response()->json([
            'exists' => true,
            'count' => $files->count(),
            'latest_version' => $latest->version,
            'next_version' => $nextVersion,
            'message' => "File Kertas Kerja untuk {$month} {$year} sudah ada di sistem ({$files->count()} file, versi terakhir: {$latest->version}). Upload ini otomatis disiapkan sebagai Versi: {$nextVersion} (Perubahan/Revisi).",
            'files' => $files->map(fn ($f) => [
                'id' => $f->id,
                'version' => $f->version,
                'filename' => $f->filename,
                'is_active' => (bool) $f->is_active,
                'created_at' => $f->created_at ? $f->created_at->format('d/m/Y H:i') : '-',
            ]),
        ]);
    }

    public function compare(BosKertasKerjaFile $file, Request $request)
    {
        $compareWithId = $request->query('with');
        $otherFile = null;

        if ($compareWithId) {
            $otherFile = BosKertasKerjaFile::find($compareWithId);
        }

        if (! $otherFile) {
            if ($file->is_active) {
                // Cari versi non-aktif (sebelumnya)
                $otherFile = BosKertasKerjaFile::where('year', $file->year)
                    ->where('month', $file->month)
                    ->where('id', '!=', $file->id)
                    ->orderBy('id', 'desc')
                    ->first();
            } else {
                // Cari versi aktif
                $otherFile = BosKertasKerjaFile::where('year', $file->year)
                    ->where('month', $file->month)
                    ->where('is_active', true)
                    ->first();
            }
        }

        if (! $otherFile) {
            return response()->json([
                'message' => "Tidak ditemukan versi lain untuk Kertas Kerja {$file->month} {$file->year} sebagai pembanding.",
            ], 404);
        }

        $oldFile = ($file->id < $otherFile->id) ? $file : $otherFile;
        $newFile = ($file->id < $otherFile->id) ? $otherFile : $file;

        $diff = BosKertasKerjaParserService::compareFiles($oldFile, $newFile);

        $allVersions = BosKertasKerjaFile::where('year', $file->year)
            ->where('month', $file->month)
            ->orderBy('id', 'asc')
            ->get(['id', 'version', 'filename', 'is_active']);

        $diff['all_versions'] = $allVersions;

        return response()->json($diff);
    }

    public function setActive(BosKertasKerjaFile $file)
    {
        $file->makeActive();

        return response()->json([
            'message' => "Versi {$file->version} berhasil ditetapkan sebagai Versi Aktif untuk {$file->month} {$file->year}.",
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|string',
            'version' => 'required|string|max:50',
            'description' => 'nullable|string',
            'file' => 'required|mimes:pdf|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Cek duplikasi file: tahun, bulan, versi, dan nama file yang sama
        $duplicate = BosKertasKerjaFile::where('year', $request->year)
            ->where('month', $request->month)
            ->where('version', $request->version)
            ->where('filename', $originalName)
            ->first();

        if ($duplicate) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Upload Ditolak: File dengan Nama ('{$originalName}'), Tahun ({$request->year}), Bulan ({$request->month}), dan Versi ('{$request->version}') sudah ada di sistem. Harap ubah versi atau gunakan file lain untuk mencegah data ganda.");
        }

        $prevActive = BosKertasKerjaFile::where('year', $request->year)
            ->where('month', $request->month)
            ->where('is_active', true)
            ->first();

        // Jika sudah ada file untuk periode ini, nonaktifkan versi lama agar file baru menjadi versi aktif
        if ($prevActive) {
            BosKertasKerjaFile::where('year', $request->year)
                ->where('month', $request->month)
                ->update(['is_active' => false]);
        }

        $path = $file->storeAs('bos_kertas_kerja', time().'_'.$originalName, 'public');

        $fileRecord = BosKertasKerjaFile::create([
            'year' => $request->year,
            'month' => $request->month,
            'filename' => $originalName,
            'file_path' => $path,
            'version' => $request->version,
            'description' => $request->description,
            'is_active' => true,
            'uploaded_by' => Auth::id(),
        ]);

        // Parse PDF to extract data items into bos_kertas_kerja_items
        $fullPath = Storage::disk('public')->path($path);
        $extractedCount = 0;
        try {
            $extractedCount = BosKertasKerjaParserService::parsePdf($fileRecord, $fullPath);
        } catch (\Exception $e) {
            // Silence parsing exception to still allow manual input if parsing fails
        }

        if ($prevActive) {
            session()->flash('comparison_available', [
                'file_id' => $fileRecord->id,
                'old_id' => $prevActive->id,
                'new_id' => $fileRecord->id,
                'old_version' => $prevActive->version,
                'new_version' => $fileRecord->version,
                'year' => $fileRecord->year,
                'month' => $fileRecord->month,
            ]);
        }

        return redirect()->route('bos.files.index')
            ->with('success', "File Kertas Kerja berhasil diupload. ($extractedCount item rincian berhasil diekstrak otomatis)");
    }

    public function update(Request $request, BosKertasKerjaFile $file)
    {
        $request->validate([
            'version' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        $file->update([
            'version' => $request->version,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Data file kertas kerja berhasil diperbarui.']);
    }

    public function destroy(BosKertasKerjaFile $file)
    {
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $itemIds = $file->items()->pluck('id');

        if ($itemIds->isNotEmpty()) {
            // Hapus transaksi realisasi terkait
            BosRealisasi::whereIn('bos_kertas_kerja_item_id', $itemIds)->delete();

            // Hapus item panduan belanja terkait
            $rencanaGroupIds = BosRencanaBelanjaItem::whereIn('bos_kertas_kerja_item_id', $itemIds)
                ->pluck('bos_rencana_belanja_id')
                ->unique();

            BosRencanaBelanjaItem::whereIn('bos_kertas_kerja_item_id', $itemIds)->delete();

            // Hapus kelompok rencana belanja jika seluruh itemnya sudah terhapus
            foreach ($rencanaGroupIds as $rId) {
                if (BosRencanaBelanjaItem::where('bos_rencana_belanja_id', $rId)->count() === 0) {
                    BosRencanaBelanja::where('id', $rId)->delete();
                }
            }
        }

        $file->items()->delete();
        $file->delete();

        return response()->json(['message' => 'File kertas kerja dan seluruh data terkait berhasil dihapus.']);
    }
}

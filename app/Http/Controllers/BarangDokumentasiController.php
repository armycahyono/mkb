<?php

namespace App\Http\Controllers;

use App\Models\BarangDokumentasi;
use App\Models\BarangDokumentasiFoto;
use App\Models\BosRealisasi;
use App\Models\GuruJabatan;
use App\Models\KasTunai;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BarangDokumentasiController extends Controller
{
    public function index(Request $request)
    {
        $selectedYear = (int) $request->input('year', session('fiscal_year', date('Y')));
        $selectedMonth = $request->input('month');
        $search = $request->input('search');

        $query = BarangDokumentasi::with(['fotos', 'kasTunai', 'creator.guru'])
            ->where('funding_source', 'BOS')
            ->where('year', $selectedYear);

        if (! empty($selectedMonth) && $selectedMonth !== 'all') {
            $query->whereMonth('tanggal_terima', $selectedMonth);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('no_nota', 'like', "%{$search}%")
                    ->orWhere('nama_toko', 'like', "%{$search}%")
                    ->orWhere('uraian', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('telegram_username', 'like', "%{$search}%")
                            ->orWhereHas('guru', function ($gq) use ($search) {
                                $gq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $dokumentasis = $query->orderBy('tanggal_terima', 'desc')->orderBy('id', 'desc')->paginate(16)->withQueryString();

        $years = range(date('Y') + 1, 2024);
        $months = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];

        // Daftar nota siap hubungkan (Pre-load)
        $availableNotas = $this->fetchAvailableNotas($selectedYear);

        return view('bos.dokumentasi_barang.index', compact(
            'dokumentasis',
            'selectedYear',
            'selectedMonth',
            'search',
            'years',
            'months',
            'availableNotas'
        ));
    }

    public function availableNotas(Request $request)
    {
        $year = (int) $request->input('year', session('fiscal_year', date('Y')));

        return response()->json($this->fetchAvailableNotas($year));
    }

    public function fetchAvailableNotas(int $year): array
    {
        $results = [];

        // 1. Dari Realisasi BOS yang memiliki no_nota
        if (class_exists(BosRealisasi::class)) {
            try {
                $realisasis = BosRealisasi::with('kertasKerjaItem')
                    ->where('year', $year)
                    ->whereNotNull('no_nota')
                    ->where('no_nota', '!=', '')
                    ->get()
                    ->groupBy('no_nota');

                foreach ($realisasis as $noNota => $items) {
                    $first = $items->first();
                    $totalNominal = (float) $items->sum('total_amount');
                    $kegiatan = $first->kertasKerjaItem?->description ?: $first->notes;
                    $tgl = $first->receipt_date ? $first->receipt_date->format('Y-m-d') : ($first->transaction_date ? $first->transaction_date->format('Y-m-d') : null);

                    $results[] = [
                        'kas_id' => null,
                        'no_nota' => $noNota,
                        'tanggal' => $tgl,
                        'uraian' => $kegiatan ?: 'Belanja Realisasi BOS',
                        'nominal' => $totalNominal,
                        'toko' => '',
                        'source' => 'Realisasi BOS',
                    ];
                }
            } catch (\Exception $e) {
                // Ignore if table doesn't exist in testing
            }
        }

        // 2. Dari Kas Tunai (BOS / BOSP)
        try {
            $kasItems = KasTunai::whereIn('funding_source', ['BOS', 'BOSP'])
                ->whereYear('transaction_date', $year)
                ->where(function ($q) {
                    $q->whereNotNull('evidence_number')
                        ->where('evidence_number', '!=', '')
                        ->where('evidence_number', '!=', '-')
                        ->orWhereIn('category', ['belanja_tunai', 'talangan_belanja', 'panjar_kerja', 'operasional_laporan']);
                })
                ->orderBy('transaction_date', 'desc')
                ->limit(50)
                ->get();

            foreach ($kasItems as $k) {
                $noNota = $k->evidence_number ?: ('KAS-'.$k->id);
                $exists = false;
                foreach ($results as $r) {
                    if ($r['no_nota'] === $noNota) {
                        $exists = true;
                        break;
                    }
                }
                if (! $exists) {
                    $results[] = [
                        'kas_id' => $k->id,
                        'no_nota' => $noNota,
                        'tanggal' => $k->transaction_date ? $k->transaction_date->format('Y-m-d') : null,
                        'uraian' => $k->description,
                        'nominal' => (float) $k->amount,
                        'toko' => $k->person_name ?: '',
                        'source' => 'Kas Tunai',
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignore if table doesn't exist in testing
        }

        return $results;
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_nota' => 'nullable|string|max:100',
            'tanggal_terima' => 'required|date',
            'nama_toko' => 'nullable|string|max:150',
            'uraian' => 'required|string',
            'nominal' => 'nullable|numeric',
            'kas_tunai_id' => 'nullable',
            'photos' => 'nullable|array',
        ]);

        $year = (int) date('Y', strtotime($request->tanggal_terima));

        $dokumentasi = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => $year,
            'no_nota' => $request->filled('no_nota') ? trim($request->no_nota) : null,
            'tanggal_terima' => $request->tanggal_terima,
            'nama_toko' => $request->nama_toko,
            'uraian' => $request->uraian,
            'nominal' => $request->nominal,
            'kas_tunai_id' => $request->kas_tunai_id,
            'created_by' => Auth::id(),
        ]);

        $this->processUploadedPhotos($dokumentasi, $request);

        $label = $dokumentasi->no_nota ?: ($dokumentasi->uraian ?: 'Barang');

        return redirect()->route('bos.dokumentasi-barang.index')
            ->with('success', "Dokumentasi '{$label}' berhasil disimpan.");
    }

    public function show($id)
    {
        $dokumentasi = BarangDokumentasi::with(['fotos', 'kasTunai', 'creator.guru'])->findOrFail($id);

        return response()->json([
            'dokumentasi' => $dokumentasi,
            'sender_name' => $dokumentasi->sender_name,
            'sender_detail' => $dokumentasi->sender_detail,
            'creator_role_label' => $dokumentasi->creator_role_label,
            'fotos' => $dokumentasi->fotos->map(function ($f) {
                return [
                    'id' => $f->id,
                    'urutan' => $f->urutan,
                    'keterangan' => $f->keterangan,
                    'file_url' => Storage::disk('public')->url($f->file_path),
                    'download_name' => $f->download_filename,
                ];
            }),
        ]);
    }

    public function update(Request $request, $id)
    {
        $dokumentasi = BarangDokumentasi::findOrFail($id);

        $request->validate([
            'no_nota' => 'nullable|string|max:100',
            'tanggal_terima' => 'required|date',
            'nama_toko' => 'nullable|string|max:150',
            'uraian' => 'required|string',
            'nominal' => 'nullable|numeric',
            'kas_tunai_id' => 'nullable',
            'photos' => 'nullable|array',
        ]);

        $year = (int) date('Y', strtotime($request->tanggal_terima));

        $dokumentasi->update([
            'year' => $year,
            'no_nota' => $request->filled('no_nota') ? trim($request->no_nota) : null,
            'tanggal_terima' => $request->tanggal_terima,
            'nama_toko' => $request->nama_toko,
            'uraian' => $request->uraian,
            'nominal' => $request->nominal,
            'kas_tunai_id' => $request->kas_tunai_id,
        ]);

        // Perbarui keterangan foto mengikuti uraian jika diubah
        if ($request->filled('uraian')) {
            $dokumentasi->fotos()->update(['keterangan' => trim($request->uraian)]);
        }

        // Tambahkan foto baru jika ada
        $this->processUploadedPhotos($dokumentasi, $request);

        $label = $dokumentasi->no_nota ?: ($dokumentasi->uraian ?: 'Barang');

        return redirect()->route('bos.dokumentasi-barang.index')
            ->with('success', "Dokumentasi '{$label}' berhasil diperbarui.");
    }

    public function destroyFoto($fotoId)
    {
        $foto = BarangDokumentasiFoto::findOrFail($fotoId);
        $dokumentasi = $foto->barangDokumentasi;

        if (Storage::disk('public')->exists($foto->file_path)) {
            Storage::disk('public')->delete($foto->file_path);
        }

        $foto->delete();

        // Susun ulang nomor urut foto (1, 2, 3...)
        $remaining = $dokumentasi->fotos()->orderBy('urutan', 'asc')->get();
        $order = 1;
        foreach ($remaining as $item) {
            $item->update(['urutan' => $order++]);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Foto berhasil dihapus.']);
        }

        return redirect()->back()->with('success', 'Foto berhasil dihapus.');
    }

    public function destroy($id)
    {
        $dokumentasi = BarangDokumentasi::with('fotos')->findOrFail($id);

        foreach ($dokumentasi->fotos as $foto) {
            if (Storage::disk('public')->exists($foto->file_path)) {
                Storage::disk('public')->delete($foto->file_path);
            }
            $foto->delete();
        }

        $label = $dokumentasi->no_nota ?: ($dokumentasi->uraian ?: 'Barang');
        $dokumentasi->delete();

        return redirect()->route('bos.dokumentasi-barang.index')
            ->with('success', "Dokumentasi '{$label}' beserta fotonya berhasil dihapus.");
    }

    public function downloadFoto($fotoId)
    {
        $foto = BarangDokumentasiFoto::with('barangDokumentasi')->findOrFail($fotoId);

        if (! Storage::disk('public')->exists($foto->file_path)) {
            abort(404, 'File foto tidak ditemukan di server.');
        }

        $fullPath = Storage::disk('public')->path($foto->file_path);
        $downloadName = $foto->download_filename;

        return response()->download($fullPath, $downloadName);
    }

    public function downloadZip($id)
    {
        $dokumentasi = BarangDokumentasi::with('fotos')->findOrFail($id);

        if ($dokumentasi->fotos->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada foto yang tersimpan.');
        }

        $zipFileName = 'Dokumentasi_'.$dokumentasi->safe_nota_name.'.zip';
        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipFilePath = $tempDir.'/'.Str::random(16).'.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP arsip.');
        }

        foreach ($dokumentasi->fotos as $foto) {
            if (Storage::disk('public')->exists($foto->file_path)) {
                $fileSystemPath = Storage::disk('public')->path($foto->file_path);
                $entryName = $foto->download_filename;
                $zip->addFile($fileSystemPath, $entryName);
            }
        }

        $zip->close();

        return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function printDokumentasi($id)
    {
        $dokumentasi = BarangDokumentasi::with('fotos')->findOrFail($id);
        $school = SchoolProfile::first();

        $targetDate = $dokumentasi->tanggal_terima ?: now();
        $kepsek = GuruJabatan::getPejabatByDate('kepala_sekolah', $targetDate)?->guru;
        $kepsekName = $kepsek ? $kepsek->full_name_with_title : 'ENDANG PATMOWATI, S.Pd';

        $bendahara = GuruJabatan::getPejabatByDate('bendahara_bosp', $targetDate)?->guru;
        $bendaharaName = $bendahara ? $bendahara->full_name_with_title : 'ALIYA NUSAIBA, S.Pd.';

        return view('bos.dokumentasi_barang.print', compact(
            'dokumentasi',
            'school',
            'kepsekName',
            'bendaharaName'
        ));
    }

    /**
     * Helper to process photos (file upload or canvas base64)
     */
    protected function processUploadedPhotos(BarangDokumentasi $dokumentasi, Request $request): void
    {
        $photos = $request->input('photos', []);
        $files = $request->file('photo_files', []);
        $caption = ! empty(trim((string) $request->input('uraian'))) ? trim($request->input('uraian')) : 'Dokumentasi Barang';

        // Tentukan nomor urut awal
        $currentMaxUrutan = (int) $dokumentasi->fotos()->max('urutan') ?: 0;

        $dir = "dokumentasi_barang/{$dokumentasi->year}/{$dokumentasi->safe_nota_name}";

        // 1. Process files from regular multipart upload
        if (is_array($files)) {
            foreach ($files as $uploadedFile) {
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $currentMaxUrutan++;
                    $ext = $uploadedFile->getClientOriginalExtension() ?: 'jpg';
                    $filename = "foto_{$currentMaxUrutan}_".Str::random(8).".{$ext}";
                    $storedPath = $uploadedFile->storeAs($dir, $filename, 'public');

                    BarangDokumentasiFoto::create([
                        'barang_dokumentasi_id' => $dokumentasi->id,
                        'urutan' => $currentMaxUrutan,
                        'file_path' => $storedPath,
                        'keterangan' => $caption,
                    ]);
                }
            }
        }

        // 2. Process base64 images (e.g. from canvas client-side compression)
        if (is_array($photos)) {
            foreach ($photos as $base64) {
                if (! empty($base64) && is_string($base64) && Str::startsWith($base64, 'data:image/')) {
                    $currentMaxUrutan++;

                    // Extract image data and mime
                    [$mimePart, $dataPart] = explode(';', $base64);
                    [, $dataPart] = explode(',', $dataPart);
                    $decodedImage = base64_decode($dataPart);

                    $ext = 'jpg';
                    if (str_contains($mimePart, 'png')) {
                        $ext = 'png';
                    } elseif (str_contains($mimePart, 'webp')) {
                        $ext = 'webp';
                    }

                    $filename = "foto_{$currentMaxUrutan}_".Str::random(8).".{$ext}";
                    $filePath = "{$dir}/{$filename}";

                    Storage::disk('public')->put($filePath, $decodedImage);

                    BarangDokumentasiFoto::create([
                        'barang_dokumentasi_id' => $dokumentasi->id,
                        'urutan' => $currentMaxUrutan,
                        'file_path' => $filePath,
                        'keterangan' => $caption,
                    ]);
                }
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengajuanBarangStoreRequest;
use App\Models\Guru;
use App\Models\PengajuanBarang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class PengajuanBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentYear = (int) date('Y');

        $tahunList = PengajuanBarang::select('tahun_anggaran')
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran')
            ->map(fn ($val) => (int) $val)
            ->toArray();

        if (! in_array($currentYear, $tahunList)) {
            $tahunList[] = $currentYear;
            rsort($tahunList);
        }

        // Default tahun adalah tahun sekarang
        $selectedTahun = $request->has('tahun_anggaran')
            ? $request->input('tahun_anggaran')
            : (string) $currentYear;

        $query = PengajuanBarang::with(['pemohon', 'guru'])->orderByDesc('created_at');

        if ($selectedTahun !== '' && $selectedTahun !== 'all' && $selectedTahun !== null) {
            $query->where('tahun_anggaran', $selectedTahun);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%")
                    ->orWhereHas('pemohon', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('guru', function ($qg) use ($search) {
                        $qg->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $pengajuans = $query->paginate(15)->appends(array_merge($request->query(), ['tahun_anggaran' => $selectedTahun]));

        return view('pengajuan_barang.index', compact('pengajuans', 'tahunList', 'selectedTahun'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $gurus = Guru::where('is_active', true)->orderBy('name')->get();
        if ($gurus->isEmpty()) {
            $gurus = Guru::orderBy('name')->get();
        }

        $existingSatuan = PengajuanBarang::whereNotNull('satuan')
            ->where('satuan', '!=', '')
            ->distinct()
            ->pluck('satuan')
            ->toArray();
        $defaultSatuan = ['Rim', 'Pcs', 'Box', 'Unit', 'Pak', 'Buku', 'Set', 'Lembar', 'Lusin', 'Roll', 'Botol', 'Meter', 'Buah', 'Batang'];
        $satuanList = array_values(array_unique(array_merge($defaultSatuan, $existingSatuan)));
        sort($satuanList);

        $existingKegiatan = PengajuanBarang::whereNotNull('kegiatan')
            ->where('kegiatan', '!=', '')
            ->distinct()
            ->pluck('kegiatan')
            ->toArray();
        $defaultKegiatan = [
            'Operasional Sekolah / Kantor',
            'KBM Semester Ganjil',
            'KBM Semester Genap',
            'Penilaian Tengah Semester (PTS)',
            'Penilaian Akhir Semester (PAS)',
            'Asesmen Nasional / Ujian Sekolah',
            'Kegiatan Ekstrakurikuler',
            'Pemeliharaan Sarana & Prasarana',
        ];
        $kegiatanList = array_values(array_unique(array_merge($defaultKegiatan, $existingKegiatan)));
        sort($kegiatanList);

        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return view('pengajuan_barang.create', compact('gurus', 'satuanList', 'kegiatanList', 'months'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PengajuanBarangStoreRequest $request)
    {
        $validated = $request->validated();
        $guruId = $validated['guru_id'] ?? null;
        $pemohonId = $validated['pemohon_id'] ?? null;

        if (! $pemohonId && $guruId) {
            $guru = Guru::with('user')->find($guruId);
            if ($guru?->user) {
                $pemohonId = $guru->user->id;
            }
        }
        $pemohonId = $pemohonId ?: Auth::id();
        $tahunAnggaran = $validated['tahun_anggaran'];

        if ($request->has('items') && is_array($request->input('items'))) {
            $createdCount = 0;
            DB::transaction(function () use ($validated, $pemohonId, $guruId, $tahunAnggaran, &$createdCount) {
                foreach ($validated['items'] as $item) {
                    $volume = (float) ($item['volume'] ?? 1);
                    $harga = (float) ($item['harga'] ?? 0);
                    $jumlah = ! empty($item['jumlah']) ? (float) $item['jumlah'] : ($volume * $harga);

                    PengajuanBarang::create([
                        'tahun_anggaran' => $tahunAnggaran,
                        'guru_id' => $guruId,
                        'pemohon_id' => $pemohonId,
                        'nama_barang' => $item['nama_barang'],
                        'spesifikasi' => $item['spesifikasi'] ?? '-',
                        'volume' => $volume,
                        'satuan' => $item['satuan'],
                        'harga' => $harga,
                        'jumlah' => $jumlah,
                        'kegiatan' => ! empty($item['kegiatan']) ? $item['kegiatan'] : ($validated['kegiatan'] ?? '-'),
                        'bulan_pakai' => ! empty($item['bulan_pakai']) ? $item['bulan_pakai'] : ($validated['bulan_pakai'] ?? '-'),
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                    $createdCount++;
                }
            });

            return Redirect::route('pengajuan-barang.index')
                ->with('success', "Berhasil menyimpan {$createdCount} rencana pengajuan barang.");
        }

        if (empty($validated['pemohon_id'])) {
            $validated['pemohon_id'] = $pemohonId;
        }
        if (! isset($validated['guru_id'])) {
            $validated['guru_id'] = $guruId;
        }

        PengajuanBarang::create($validated);

        return Redirect::route('pengajuan-barang.index')
            ->with('success', 'Pengajuan barang berhasil disimpan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PengajuanBarang $pengajuanBarang)
    {
        Gate::authorize('update', $pengajuanBarang);
        $users = User::orderBy('name')->get();

        return view('pengajuan_barang.edit', compact('pengajuanBarang', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PengajuanBarangStoreRequest $request, PengajuanBarang $pengajuanBarang)
    {
        Gate::authorize('update', $pengajuanBarang);
        $data = $request->validated();
        if (empty($data['pemohon_id'])) {
            $data['pemohon_id'] = $pengajuanBarang->pemohon_id;
        }

        $pengajuanBarang->update($data);

        return Redirect::route('pengajuan-barang.index')
            ->with('success', 'Pengajuan barang berhasil diubah.');
    }

    /**
     * Update realisasi field (by authorized officer).
     */
    public function realisasi(Request $request, PengajuanBarang $pengajuanBarang)
    {
        Gate::authorize('realisasi', $pengajuanBarang);
        $request->validate([
            'realisasi' => 'required|string|max:255',
        ]);

        $pengajuanBarang->update(['realisasi' => $request->input('realisasi')]);

        return Redirect::back()->with('success', 'Realisasi barang berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PengajuanBarang $pengajuanBarang)
    {
        Gate::authorize('delete', $pengajuanBarang);
        $pengajuanBarang->delete();

        return Redirect::route('pengajuan-barang.index')
            ->with('success', 'Pengajuan barang berhasil dihapus.');
    }
}

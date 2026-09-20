<?php

namespace App\Http\Controllers;

use App\Models\ArkasKegiatan;
use App\Models\KegiatanSekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KegiatanSekolahController extends Controller
{
    /**
     * Tampilkan daftar master Kegiatan / Program Sekolah.
     */
    public function index(Request $request): View
    {
        $kegiatans = KegiatanSekolah::with('arkasKegiatan')
            ->withCount('pengajuanBarangs')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $arkasKegiatans = ArkasKegiatan::orderBy('kode_kegiatan')
            ->get(['kode_kegiatan', 'kegiatan', 'komponen']);

        return view('master.kegiatan_sekolah.index', compact('kegiatans', 'arkasKegiatans'));
    }

    /**
     * Simpan data Kegiatan Sekolah baru.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'arkas_kegiatan_kode' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:25'],
            'urutan' => ['nullable', 'integer'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $maxUrutan = KegiatanSekolah::max('urutan') ?? 0;

        $kegiatan = KegiatanSekolah::create([
            'nama' => trim($validated['nama']),
            'kode' => ! empty($validated['kode']) ? strtoupper(trim($validated['kode'])) : null,
            'arkas_kegiatan_kode' => $this->parseArkasKode($validated['arkas_kegiatan_kode'] ?? null),
            'color' => ! empty($validated['color']) ? trim($validated['color']) : '#4f46e5',
            'urutan' => $validated['urutan'] ?? ($maxUrutan + 1),
            'keterangan' => ! empty($validated['keterangan']) ? trim($validated['keterangan']) : null,
            'is_active' => true,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Program kegiatan '{$kegiatan->nama}' berhasil ditambahkan.",
                'data' => $kegiatan,
            ]);
        }

        return redirect()->back()->with('success', "Program kegiatan '{$kegiatan->nama}' berhasil ditambahkan.");
    }

    /**
     * Perbarui data Kegiatan Sekolah.
     */
    public function update(Request $request, KegiatanSekolah $kegiatanSekolah): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'arkas_kegiatan_kode' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:25'],
            'urutan' => ['nullable', 'integer'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $kegiatanSekolah->update([
            'nama' => trim($validated['nama']),
            'kode' => ! empty($validated['kode']) ? strtoupper(trim($validated['kode'])) : null,
            'arkas_kegiatan_kode' => $this->parseArkasKode($validated['arkas_kegiatan_kode'] ?? null),
            'color' => ! empty($validated['color']) ? trim($validated['color']) : '#4f46e5',
            'urutan' => $validated['urutan'] ?? $kegiatanSekolah->urutan,
            'keterangan' => ! empty($validated['keterangan']) ? trim($validated['keterangan']) : null,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Program kegiatan '{$kegiatanSekolah->nama}' berhasil diperbarui.",
                'data' => $kegiatanSekolah,
            ]);
        }

        return redirect()->back()->with('success', "Program kegiatan '{$kegiatanSekolah->nama}' berhasil diperbarui.");
    }

    /**
     * Hapus Kegiatan Sekolah (Soft Delete).
     */
    public function destroy(KegiatanSekolah $kegiatanSekolah): RedirectResponse
    {
        $nama = $kegiatanSekolah->nama;
        $kegiatanSekolah->delete();

        return redirect()->back()->with('success', "Program kegiatan '{$nama}' berhasil dihapus.");
    }

    /**
     * Toggle status aktif / non-aktif.
     */
    public function toggleStatus(KegiatanSekolah $kegiatanSekolah): RedirectResponse|JsonResponse
    {
        $kegiatanSekolah->update([
            'is_active' => ! $kegiatanSekolah->is_active,
        ]);

        $statusText = $kegiatanSekolah->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Program kegiatan '{$kegiatanSekolah->nama}' berhasil {$statusText}.");
    }

    /**
     * Parse input kode kegiatan ARKAS menjadi format kode valid (contoh: 03.03.01) atau teks kustom.
     */
    protected function parseArkasKode(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $trimmed = trim($input);

        // 1. Jika diawali pola angka kode ARKAS (misal: "03.03.06 - Pengelolaan Perpustakaan" atau "03.03.06")
        if (preg_match('/^([0-9]{2}\.[0-9]{2}\.[0-9]{2})/', $trimmed, $matches)) {
            return $matches[1];
        }

        // 2. Cek apakah teks persis cocok dengan kode kegiatan atau nama kegiatan
        $exact = ArkasKegiatan::where('kode_kegiatan', $trimmed)
            ->orWhere('kegiatan', $trimmed)
            ->first();

        if ($exact) {
            return $exact->kode_kegiatan;
        }

        // 3. Cek pencarian kata kunci sebagian (LIKE) jika pengguna mengetik nama kegiatan
        if (mb_strlen($trimmed) >= 3) {
            $fuzzy = ArkasKegiatan::where('kegiatan', 'LIKE', "%{$trimmed}%")
                ->orWhere('komponen', 'LIKE', "%{$trimmed}%")
                ->first();

            if ($fuzzy) {
                return $fuzzy->kode_kegiatan;
            }
        }

        return mb_substr($trimmed, 0, 50);
    }
}

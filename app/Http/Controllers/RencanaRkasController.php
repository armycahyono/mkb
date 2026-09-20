<?php

namespace App\Http\Controllers;

use App\Exports\RencanaRkasTemplateExport;
use App\Imports\RencanaRkasImport;
use App\Models\ArkasBarang;
use App\Models\ArkasKegiatan;
use App\Models\BosPaguAnggaran;
use App\Models\Guru;
use App\Models\KegiatanSekolah;
use App\Models\PengajuanBarang;
use App\Models\UtilityProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RencanaRkasController extends Controller
{
    private array $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Tampilkan halaman utama Rencana RKAS BOSP.
     */
    public function index(Request $request): View
    {
        $selectedYear = (int) $request->input('year', session('fiscal_year', date('Y')));
        $selectedMonth = (string) $request->input('month', 'Semua');
        $selectedKarakter = (string) $request->input('karakter', 'all');
        $selectedKegiatan = $request->input('kegiatan');
        $selectedKegiatanSekolahId = $request->input('kegiatan_sekolah_id');
        $search = $request->input('search');

        // Ambil daftar tahun unik
        $years = PengajuanBarang::select('tahun_anggaran')
            ->distinct()
            ->pluck('tahun_anggaran')
            ->merge([$selectedYear, (int) date('Y')])
            ->unique()
            ->sortDesc()
            ->values();

        // Data Pagu Anggaran tahun ini
        $pagu = BosPaguAnggaran::where('year', $selectedYear)
            ->where('tipe', 'bosp')
            ->first();

        // Base query untuk item RKAS tahun ini
        $baseQuery = PengajuanBarang::where('tahun_anggaran', $selectedYear);

        $totalTeranggarkan = (float) (clone $baseQuery)->sum('jumlah');
        $paguAmount = $pagu ? (float) $pagu->amount : 0.0;
        $sisaPagu = $paguAmount - $totalTeranggarkan;
        $persenPagu = $paguAmount > 0 ? round(($totalTeranggarkan / $paguAmount) * 100, 1) : 0.0;
        $totalItemsCount = (clone $baseQuery)->count();
        $recurringCount = (clone $baseQuery)->where('is_recurring', true)->count();

        // Hitung jumlah item per bulan untuk badge pada tab bulan
        $monthCounts = [];
        foreach ($this->months as $m) {
            $monthCounts[$m] = (clone $baseQuery)->where('bulan_pakai', $m)->count();
        }

        // Query data item untuk tabel dengan filter
        $query = PengajuanBarang::with(['guru', 'pemohon', 'kertasKerjaItems', 'utilityProfile', 'kegiatanSekolah', 'recurringSiblings'])
            ->where('tahun_anggaran', $selectedYear);

        if ($selectedMonth !== '' && $selectedMonth !== 'Semua') {
            $query->where('bulan_pakai', $selectedMonth);
        }

        if ($selectedKarakter === 'sekali') {
            $query->where('is_recurring', false);
        } elseif ($selectedKarakter === 'berulang') {
            $query->where('is_recurring', true);
        }

        if ($request->filled('kegiatan')) {
            $query->where('kegiatan', $selectedKegiatan);
        }

        if ($request->filled('kegiatan_sekolah_id')) {
            $query->where('kegiatan_sekolah_id', $selectedKegiatanSekolahId);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%")
                    ->orWhere('kegiatan_sekolah', 'like', "%{$search}%")
                    ->orWhereHas('kegiatanSekolah', fn ($qks) => $qks->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('guru', fn ($qg) => $qg->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('pemohon', fn ($qu) => $qu->where('name', 'like', "%{$search}%"));
            });
        }

        // Urutkan berdasarkan bulan kalender, lalu id desc
        $items = $query->orderByRaw("CASE bulan_pakai 
            WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 
            WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 
            WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 
            WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 
            ELSE 99 END ASC")
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        // Daftar kegiatan unik untuk filter dropdown
        $kegiatanList = PengajuanBarang::where('tahun_anggaran', $selectedYear)
            ->whereNotNull('kegiatan')
            ->where('kegiatan', '!=', '')
            ->distinct()
            ->pluck('kegiatan');

        // Daftar kegiatan sekolah aktif untuk modal dan filter
        $kegiatanSekolahs = KegiatanSekolah::where('is_active', true)
            ->with('arkasKegiatan')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        // Daftar guru aktif
        $gurus = Guru::where('is_active', true)->orderBy('name')->get();

        // Daftar profil utilitas aktif untuk modal RKAS
        $utilityProfiles = UtilityProfile::where('is_active', true)
            ->with(['defaultReimburseGuru.primaryBankAccount'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $utilityProfilesFormatted = $utilityProfiles->map(function ($up) {
            $specs = [];
            $isListrik = str_contains($up->category, 'listrik');
            $isTelkom = str_contains($up->category, 'telkom');

            if ($up->idpel || $up->customer_number) {
                $label = $isListrik ? 'IDPEL' : ($isTelkom ? 'No. Telp' : 'No. Pelanggan');
                $specs[] = $label.': '.($up->idpel ?: $up->customer_number);
            }
            if ($up->meter_number) {
                $specs[] = 'No. Meter: '.$up->meter_number;
            }
            if ($up->tariff_power) {
                $specs[] = 'Daya: '.$up->tariff_power;
            }
            if ($up->customer_name) {
                $specs[] = 'a/n '.$up->customer_name;
            }
            if ($up->category === 'listrik_pasca') {
                $specs[] = '(Pascaprabayar)';
            } elseif ($up->category === 'listrik_pra') {
                $specs[] = '(Prabayar / Token)';
            } elseif ($up->payment_method && str_contains(strtolower($up->payment_method), 'bca')) {
                $specs[] = '('.$up->payment_method.')';
            }

            $shortLabel = $up->customer_name ?: $up->name;
            if ($up->category === 'listrik_pra') {
                $shortLabel .= ' (Token)';
            }

            return [
                'id' => $up->id,
                'code' => $up->code,
                'name' => $up->name,
                'category' => $up->category,
                'customer_name' => $up->customer_name,
                'customer_number' => $up->customer_number,
                'meter_number' => $up->meter_number,
                'idpel' => $up->idpel,
                'tariff_power' => $up->tariff_power,
                'provider' => $up->provider,
                'payment_method' => $up->payment_method,
                'default_guru_id' => $up->default_reimburse_guru_id,
                'formatted_spec' => implode(' | ', $specs),
                'short_label' => $shortLabel,
            ];
        });

        $stats = [
            'pagu' => $paguAmount,
            'teranggarkan' => $totalTeranggarkan,
            'sisa' => $sisaPagu,
            'persen' => $persenPagu,
            'total_items' => $totalItemsCount,
            'recurring_items' => $recurringCount,
            'month_counts' => $monthCounts,
        ];

        $months = $this->months;

        return view('bos.rkas.index', compact(
            'items',
            'pagu',
            'years',
            'months',
            'kegiatanList',
            'kegiatanSekolahs',
            'gurus',
            'utilityProfiles',
            'utilityProfilesFormatted',
            'selectedYear',
            'selectedMonth',
            'selectedKarakter',
            'selectedKegiatan',
            'selectedKegiatanSekolahId',
            'search',
            'stats'
        ));
    }

    /**
     * Cari rekomendasi Kegiatan ARKAS (autokomplit > 2 huruf).
     */
    public function searchKegiatan(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = ArkasKegiatan::query()
            ->where(function ($query) use ($q) {
                $query->where('kode_kegiatan', 'like', "%{$q}%")
                    ->orWhere('kegiatan', 'like', "%{$q}%")
                    ->orWhere('komponen', 'like', "%{$q}%");
            })
            ->orderBy('kode_kegiatan')
            ->limit(15)
            ->get(['id', 'kode_kegiatan', 'kegiatan', 'snp', 'komponen']);

        return response()->json($results);
    }

    /**
     * Cari rekomendasi Uraian Barang ARKAS (autokomplit > 2 huruf).
     */
    public function searchBarang(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([
                'total' => 0,
                'count' => 0,
                'items' => [],
            ]);
        }

        $words = array_values(array_filter(explode(' ', $q)));

        $query = ArkasBarang::query();
        if (count($words) > 1) {
            $query->where(function ($sub) use ($words) {
                foreach ($words as $w) {
                    $sub->where('nama_barang', 'like', "%{$w}%");
                }
            });
        } else {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_barang', 'like', "%{$q}%")
                    ->orWhere('kode_rekening', 'like', "%{$q}%");
            });
        }

        $total = (clone $query)->count();

        $firstWord = strtolower($words[0] ?? $q);
        $fullQuery = strtolower($q);

        $results = $query
            ->orderByRaw('CASE 
                WHEN LOWER(nama_barang) LIKE ? THEN 1 
                WHEN LOWER(nama_barang) LIKE ? THEN 2 
                WHEN LOWER(nama_barang) LIKE ? THEN 3 
                ELSE 4 
            END', [
                $fullQuery.'%',
                $firstWord.'%',
                '% '.$firstWord.'%',
            ])
            ->orderBy('nama_barang')
            ->limit(50)
            ->get(['id', 'nama_barang', 'satuan', 'harga_min', 'harga_max', 'kode_rekening', 'kode_belanja', 'jenis_barang'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_barang' => $item->nama_barang,
                    'satuan' => $item->satuan,
                    'harga_max' => (float) $item->harga_max,
                    'harga_min' => (float) $item->harga_min,
                    'harga_range' => $item->harga_range_display,
                    'kode_rekening' => $item->kode_rekening,
                    'kode_belanja' => $item->kode_belanja,
                    'jenis_barang' => $item->jenis_barang,
                ];
            });

        return response()->json([
            'total' => $total,
            'count' => $results->count(),
            'items' => $results,
        ]);
    }

    /**
     * Cari Rekening Belanja ARKAS.
     */
    public function searchRekening(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = ArkasBarang::query()
            ->select('kode_rekening')
            ->distinct()
            ->where('kode_rekening', 'like', "%{$q}%")
            ->orderBy('kode_rekening')
            ->limit(15)
            ->pluck('kode_rekening');

        return response()->json($results);
    }

    /**
     * Simpan item Rencana RKAS (Pengajuan Sekali, Berulang, atau Multi-Bulan Card Dinamis).
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('harga')) {
            $request->merge(['harga' => str_replace('.', '', (string) $request->input('harga'))]);
        }
        if ($request->filled('volume')) {
            $request->merge(['volume' => str_replace('.', '', (string) $request->input('volume'))]);
        }

        $validated = $request->validate([
            'tahun_anggaran' => ['required', 'integer'],
            'tipe_pengajuan' => ['nullable', 'in:umum,utilitas'],
            'utility_profile_id' => ['nullable', 'exists:utility_profiles,id'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'kegiatan_sekolah' => ['nullable', 'string', 'max:255'],
            'kegiatan_sekolah_id' => ['nullable', 'exists:kegiatan_sekolahs,id'],
            'kode_rekening' => ['nullable', 'string', 'max:50'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
            'volume' => ['nullable', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'karakter' => ['nullable', 'in:sekali,berulang'],
            'bulan_pakai' => ['nullable', 'string'],
            'months' => ['nullable', 'array'],
            'months_json' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $harga = (float) $validated['harga'];
        $pemohonId = Auth::id();
        $spesifikasi = ! empty($validated['spesifikasi']) ? trim($validated['spesifikasi']) : '-';
        $keterangan = ! empty($validated['keterangan']) ? trim($validated['keterangan']) : null;
        $utilityProfileId = ! empty($validated['utility_profile_id']) ? (int) $validated['utility_profile_id'] : null;
        $kodeRekening = ! empty($validated['kode_rekening']) ? trim($validated['kode_rekening']) : null;
        $kegiatanSekolahId = ! empty($validated['kegiatan_sekolah_id']) ? (int) $validated['kegiatan_sekolah_id'] : null;
        $kegiatanSekolah = ! empty($validated['kegiatan_sekolah']) ? trim($validated['kegiatan_sekolah']) : null;
        if ($kegiatanSekolahId && ! $kegiatanSekolah) {
            $kegiatanSekolah = KegiatanSekolah::find($kegiatanSekolahId)?->nama;
        }

        // Cek apakah ada data kartu bulan (format baru)
        $monthsCards = [];
        if (! empty($validated['months_json'])) {
            $decoded = json_decode($validated['months_json'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $monthsCards = $decoded;
            }
        }

        if (! empty($monthsCards)) {
            $isMultiMonth = count($monthsCards) > 1;
            $groupId = $isMultiMonth ? Str::uuid()->toString() : null;

            foreach ($monthsCards as $c) {
                $cardVol = isset($c['volume']) ? (float) $c['volume'] : 1.0;
                $cardUnit = ! empty($c['unit']) ? trim($c['unit']) : ($validated['satuan'] ?? 'Unit');
                $cardMonth = ! empty($c['month']) ? trim($c['month']) : 'Januari';
                $cardJumlah = $cardVol * $harga;

                PengajuanBarang::create([
                    'tahun_anggaran' => $validated['tahun_anggaran'],
                    'guru_id' => $validated['guru_id'] ?? null,
                    'pemohon_id' => $pemohonId,
                    'utility_profile_id' => $utilityProfileId,
                    'nama_barang' => trim($validated['nama_barang']),
                    'spesifikasi' => $spesifikasi,
                    'kegiatan' => trim($validated['kegiatan']),
                    'kegiatan_sekolah' => $kegiatanSekolah,
                    'kegiatan_sekolah_id' => $kegiatanSekolahId,
                    'kode_rekening' => $kodeRekening,
                    'volume' => $cardVol,
                    'satuan' => $cardUnit,
                    'harga' => $harga,
                    'jumlah' => $cardJumlah,
                    'bulan_pakai' => $cardMonth,
                    'keterangan' => $keterangan,
                    'is_recurring' => $isMultiMonth,
                    'recurring_group_id' => $groupId,
                ]);
            }

            $countMonths = count($monthsCards);
            $msg = $isMultiMonth
                ? "Rencana RKAS '{$validated['nama_barang']}' berhasil disimpan untuk {$countMonths} bulan."
                : "Item rencana RKAS '{$validated['nama_barang']}' berhasil disimpan.";

            return redirect()->back()->with('success', $msg);
        }

        // Fallback untuk backward compatibility (karakter berulang / sekali)
        $volume = isset($validated['volume']) ? (float) $validated['volume'] : 1.0;
        $satuan = ! empty($validated['satuan']) ? trim($validated['satuan']) : 'Unit';
        $jumlahPerBulan = $volume * $harga;

        if (($validated['karakter'] ?? '') === 'berulang' && ! empty($validated['months'])) {
            $groupId = Str::uuid()->toString();
            $months = $validated['months'];

            foreach ($months as $month) {
                PengajuanBarang::create([
                    'tahun_anggaran' => $validated['tahun_anggaran'],
                    'guru_id' => $validated['guru_id'] ?? null,
                    'pemohon_id' => $pemohonId,
                    'utility_profile_id' => $utilityProfileId,
                    'nama_barang' => trim($validated['nama_barang']),
                    'spesifikasi' => $spesifikasi,
                    'kegiatan' => trim($validated['kegiatan']),
                    'kegiatan_sekolah' => $kegiatanSekolah,
                    'kegiatan_sekolah_id' => $kegiatanSekolahId,
                    'kode_rekening' => $kodeRekening,
                    'volume' => $volume,
                    'satuan' => $satuan,
                    'harga' => $harga,
                    'jumlah' => $jumlahPerBulan,
                    'bulan_pakai' => $month,
                    'keterangan' => $keterangan,
                    'is_recurring' => true,
                    'recurring_group_id' => $groupId,
                ]);
            }

            $countMonths = count($months);

            return redirect()->back()->with('success', "Rencana pengajuan berulang '{$validated['nama_barang']}' berhasil dibuat untuk {$countMonths} bulan.");
        }

        // Pengajuan Sekali
        $bulanPakai = ! empty($validated['bulan_pakai']) ? $validated['bulan_pakai'] : 'Januari';
        PengajuanBarang::create([
            'tahun_anggaran' => $validated['tahun_anggaran'],
            'guru_id' => $validated['guru_id'] ?? null,
            'pemohon_id' => $pemohonId,
            'utility_profile_id' => $utilityProfileId,
            'nama_barang' => trim($validated['nama_barang']),
            'spesifikasi' => $spesifikasi,
            'kegiatan' => trim($validated['kegiatan']),
            'kegiatan_sekolah' => $kegiatanSekolah,
            'kegiatan_sekolah_id' => $kegiatanSekolahId,
            'kode_rekening' => $kodeRekening,
            'volume' => $volume,
            'satuan' => $satuan,
            'harga' => $harga,
            'jumlah' => $jumlahPerBulan,
            'bulan_pakai' => $bulanPakai,
            'keterangan' => $keterangan,
            'is_recurring' => false,
            'recurring_group_id' => null,
        ]);

        return redirect()->back()->with('success', "Item rencana RKAS '{$validated['nama_barang']}' berhasil disimpan.");
    }

    /**
     * Perbarui data item Rencana RKAS.
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $item = PengajuanBarang::findOrFail($id);

        if ($request->filled('harga')) {
            $request->merge(['harga' => str_replace('.', '', (string) $request->input('harga'))]);
        }
        if ($request->filled('volume')) {
            $request->merge(['volume' => str_replace('.', '', (string) $request->input('volume'))]);
        }

        $validated = $request->validate([
            'months_json' => ['nullable', 'string'],
            'utility_profile_id' => ['nullable', 'exists:utility_profiles,id'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'kegiatan_sekolah' => ['nullable', 'string', 'max:255'],
            'kegiatan_sekolah_id' => ['nullable', 'exists:kegiatan_sekolahs,id'],
            'kode_rekening' => ['nullable', 'string', 'max:50'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
            'volume' => ['nullable', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'bulan_pakai' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'update_all_recurring' => ['nullable', 'boolean'],
        ]);

        $harga = (float) $validated['harga'];
        $spesifikasi = ! empty($validated['spesifikasi']) ? trim($validated['spesifikasi']) : '-';
        $keterangan = ! empty($validated['keterangan']) ? trim($validated['keterangan']) : null;
        $kodeRekening = array_key_exists('kode_rekening', $validated)
            ? (! empty($validated['kode_rekening']) ? trim($validated['kode_rekening']) : null)
            : $item->kode_rekening;
        $utilityProfileId = array_key_exists('utility_profile_id', $validated)
            ? (! empty($validated['utility_profile_id']) ? (int) $validated['utility_profile_id'] : null)
            : $item->utility_profile_id;
        $kegiatanSekolahId = array_key_exists('kegiatan_sekolah_id', $validated)
            ? (! empty($validated['kegiatan_sekolah_id']) ? (int) $validated['kegiatan_sekolah_id'] : null)
            : $item->kegiatan_sekolah_id;
        $kegiatanSekolah = array_key_exists('kegiatan_sekolah', $validated)
            ? (! empty($validated['kegiatan_sekolah']) ? trim($validated['kegiatan_sekolah']) : null)
            : $item->kegiatan_sekolah;
        if ($kegiatanSekolahId && ! $kegiatanSekolah) {
            $kegiatanSekolah = KegiatanSekolah::find($kegiatanSekolahId)?->nama;
        }

        // Cek apakah ada data kartu bulan (format multi-bulan)
        $monthsCards = [];
        if (! empty($validated['months_json'])) {
            $decoded = json_decode($validated['months_json'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $monthsCards = $decoded;
            }
        }

        if (! empty($monthsCards)) {
            $isMultiMonth = count($monthsCards) > 1;

            // Tentukan recurring group ID
            $oldGroupId = $item->recurring_group_id;
            if ($isMultiMonth) {
                $groupId = ($item->is_recurring && $oldGroupId)
                    ? $oldGroupId
                    : Str::uuid()->toString();
            } else {
                $groupId = null;
            }

            // Dapatkan item-item lama dalam grup (jika ada)
            $existingGroupItems = ($item->is_recurring && $oldGroupId)
                ? PengajuanBarang::where('recurring_group_id', $oldGroupId)->get()->keyBy('id')
                : collect([$item->id => $item]);

            $savedIds = [];

            if ($isMultiMonth) {
                foreach ($monthsCards as $c) {
                    $cardVol = isset($c['volume']) ? (float) $c['volume'] : 1.0;
                    $cardUnit = ! empty($c['unit']) ? trim($c['unit']) : ($validated['satuan'] ?? 'Unit');
                    $cardMonth = ! empty($c['month']) ? trim($c['month']) : 'Januari';
                    $cardJumlah = $cardVol * $harga;
                    $cardId = ! empty($c['id']) ? (int) $c['id'] : null;

                    // Cari target item yang akan diupdate:
                    // 1. Berdasarkan ID yang cocok dalam grup
                    // 2. Atau berdasarkan bulan yang cocok dalam grup yang belum tersimpan
                    $targetItem = null;
                    if ($cardId && $existingGroupItems->has($cardId) && ! in_array($cardId, $savedIds, true)) {
                        $targetItem = $existingGroupItems->get($cardId);
                    } else {
                        $targetItem = $existingGroupItems->first(function ($ex) use ($cardMonth, $savedIds) {
                            return $ex->bulan_pakai === $cardMonth && ! in_array($ex->id, $savedIds, true);
                        });
                    }

                    if ($targetItem) {
                        $targetItem->update([
                            'utility_profile_id' => $utilityProfileId,
                            'nama_barang' => trim($validated['nama_barang']),
                            'spesifikasi' => $spesifikasi,
                            'kegiatan' => trim($validated['kegiatan']),
                            'kegiatan_sekolah' => $kegiatanSekolah,
                            'kegiatan_sekolah_id' => $kegiatanSekolahId,
                            'kode_rekening' => $kodeRekening,
                            'guru_id' => $validated['guru_id'] ?? null,
                            'volume' => $cardVol,
                            'satuan' => $cardUnit,
                            'harga' => $harga,
                            'jumlah' => $cardJumlah,
                            'bulan_pakai' => $cardMonth,
                            'keterangan' => $keterangan,
                            'is_recurring' => true,
                            'recurring_group_id' => $groupId,
                        ]);
                        $savedIds[] = $targetItem->id;
                    } else {
                        // Buat item baru untuk bulan tambahan
                        $newItem = PengajuanBarang::create([
                            'tahun_anggaran' => $item->tahun_anggaran,
                            'guru_id' => $validated['guru_id'] ?? null,
                            'pemohon_id' => $item->pemohon_id ?: Auth::id(),
                            'utility_profile_id' => $utilityProfileId,
                            'nama_barang' => trim($validated['nama_barang']),
                            'spesifikasi' => $spesifikasi,
                            'kegiatan' => trim($validated['kegiatan']),
                            'kegiatan_sekolah' => $kegiatanSekolah,
                            'kegiatan_sekolah_id' => $kegiatanSekolahId,
                            'kode_rekening' => $kodeRekening,
                            'volume' => $cardVol,
                            'satuan' => $cardUnit,
                            'harga' => $harga,
                            'jumlah' => $cardJumlah,
                            'bulan_pakai' => $cardMonth,
                            'keterangan' => $keterangan,
                            'is_recurring' => true,
                            'recurring_group_id' => $groupId,
                        ]);
                        $savedIds[] = $newItem->id;
                    }
                }

                // Hapus item dalam grup lama yang tidak ada lagi di kartu bulan baru
                if ($item->is_recurring && $oldGroupId) {
                    PengajuanBarang::where('recurring_group_id', $oldGroupId)
                        ->whereNotIn('id', $savedIds)
                        ->delete();
                }

                $countMonths = count($monthsCards);

                return redirect()->back()->with('success', "Rencana RKAS '{$validated['nama_barang']}' berhasil diperbarui untuk {$countMonths} alokasi bulan.");
            } else {
                // Hanya 1 bulan (dijadikan non-recurring / tunggal)
                $firstCard = $monthsCards[0];
                $cardVol = isset($firstCard['volume']) ? (float) $firstCard['volume'] : 1.0;
                $cardUnit = ! empty($firstCard['unit']) ? trim($firstCard['unit']) : ($validated['satuan'] ?? 'Unit');
                $cardMonth = ! empty($firstCard['month']) ? trim($firstCard['month']) : 'Januari';
                $cardJumlah = $cardVol * $harga;

                $item->update([
                    'utility_profile_id' => $utilityProfileId,
                    'nama_barang' => trim($validated['nama_barang']),
                    'spesifikasi' => $spesifikasi,
                    'kegiatan' => trim($validated['kegiatan']),
                    'kegiatan_sekolah' => $kegiatanSekolah,
                    'kegiatan_sekolah_id' => $kegiatanSekolahId,
                    'kode_rekening' => $kodeRekening,
                    'guru_id' => $validated['guru_id'] ?? null,
                    'volume' => $cardVol,
                    'satuan' => $cardUnit,
                    'harga' => $harga,
                    'jumlah' => $cardJumlah,
                    'bulan_pakai' => $cardMonth,
                    'keterangan' => $keterangan,
                    'is_recurring' => false,
                    'recurring_group_id' => null,
                ]);

                // Jika sebelumnya berulang, hapus sibling lainnya
                if ($oldGroupId) {
                    PengajuanBarang::where('recurring_group_id', $oldGroupId)
                        ->where('id', '!=', $item->id)
                        ->delete();
                }

                return redirect()->back()->with('success', "Item rencana RKAS '{$item->nama_barang}' berhasil diperbarui.");
            }
        }

        // Fallback untuk backward compatibility (single volume / update_all_recurring)
        $volume = isset($validated['volume']) ? (float) $validated['volume'] : (float) $item->volume;
        $satuan = ! empty($validated['satuan']) ? trim($validated['satuan']) : $item->satuan;
        $bulanPakai = ! empty($validated['bulan_pakai']) ? $validated['bulan_pakai'] : $item->bulan_pakai;
        $jumlah = $volume * $harga;

        // Jika item bagian dari pengajuan berulang dan diminta update semua
        if ($item->is_recurring && $item->recurring_group_id && $request->boolean('update_all_recurring')) {
            PengajuanBarang::where('recurring_group_id', $item->recurring_group_id)->update([
                'utility_profile_id' => $utilityProfileId,
                'nama_barang' => trim($validated['nama_barang']),
                'spesifikasi' => $spesifikasi,
                'kegiatan' => trim($validated['kegiatan']),
                'kegiatan_sekolah' => $kegiatanSekolah,
                'kegiatan_sekolah_id' => $kegiatanSekolahId,
                'kode_rekening' => $kodeRekening,
                'guru_id' => $validated['guru_id'] ?? null,
                'volume' => $volume,
                'satuan' => $satuan,
                'harga' => $harga,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
            ]);

            return redirect()->back()->with('success', "Seluruh alokasi bulan untuk '{$validated['nama_barang']}' berhasil diperbarui.");
        }

        $item->update([
            'utility_profile_id' => $utilityProfileId,
            'nama_barang' => trim($validated['nama_barang']),
            'spesifikasi' => $spesifikasi,
            'kegiatan' => trim($validated['kegiatan']),
            'kegiatan_sekolah' => $kegiatanSekolah,
            'kegiatan_sekolah_id' => $kegiatanSekolahId,
            'kode_rekening' => $kodeRekening,
            'guru_id' => $validated['guru_id'] ?? null,
            'volume' => $volume,
            'satuan' => $satuan,
            'harga' => $harga,
            'jumlah' => $jumlah,
            'bulan_pakai' => $bulanPakai,
            'keterangan' => $keterangan,
        ]);

        return redirect()->back()->with('success', "Item rencana RKAS '{$item->nama_barang}' berhasil diperbarui.");
    }

    /**
     * Hapus item Rencana RKAS (tunggal atau seluruh grup berulang).
     */
    public function destroy(Request $request, int|string $id): RedirectResponse
    {
        $item = PengajuanBarang::findOrFail($id);

        if ($item->is_recurring && $item->recurring_group_id && $request->boolean('delete_all_recurring')) {
            $count = PengajuanBarang::where('recurring_group_id', $item->recurring_group_id)->count();
            PengajuanBarang::where('recurring_group_id', $item->recurring_group_id)->delete();

            return redirect()->back()->with('success', "Seluruh {$count} alokasi bulan untuk '{$item->nama_barang}' berhasil dihapus.");
        }

        $item->delete();

        return redirect()->back()->with('success', 'Item rencana RKAS berhasil dihapus.');
    }

    /**
     * Simpan / Perbarui Pagu Anggaran Tahunan BOSP.
     */
    public function savePagu(Request $request): RedirectResponse
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => str_replace('.', '', (string) $request->input('amount'))]);
        }

        $validated = $request->validate([
            'year' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        BosPaguAnggaran::updateOrCreate(
            [
                'year' => $validated['year'],
                'tipe' => 'bosp',
            ],
            [
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        $formatted = 'Rp '.number_format((float) $validated['amount'], 0, ',', '.');

        return redirect()->back()->with('success', "Pagu Anggaran BOSP Tahun {$validated['year']} sebesar {$formatted} berhasil disimpan.");
    }

    /**
     * Unduh Template Excel untuk Rencana RKAS BOSP.
     */
    public function exportTemplate(Request $request): BinaryFileResponse
    {
        $year = (int) $request->input('year', session('fiscal_year', date('Y')));
        $filename = "Template_Rencana_RKAS_{$year}.xlsx";

        return Excel::download(new RencanaRkasTemplateExport($year), $filename);
    }

    /**
     * Import data Rencana RKAS dari berkas Excel (.xlsx, .xls, .csv).
     */
    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'tahun_anggaran' => ['required', 'integer'],
        ], [
            'file.required' => 'Silakan pilih berkas Excel yang akan diimpor.',
            'file.mimes' => 'Format berkas harus berupa Excel (.xlsx, .xls) atau .csv.',
            'file.max' => 'Ukuran berkas maksimal adalah 10 MB.',
        ]);

        $tahunAnggaran = (int) $request->input('tahun_anggaran');
        $utilityProfileId = UtilityProfile::first()?->id;
        $pemohonId = Auth::id() ?? 1;

        try {
            $import = new RencanaRkasImport($tahunAnggaran, $pemohonId, $utilityProfileId);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $created = $results['created'];
            $warnings = $results['warnings'];

            if ($created === 0) {
                return redirect()->back()->with('error', 'Tidak ada baris data yang berhasil diimpor. Pastikan format kolom sesuai template resmi.');
            }

            $msg = "Berhasil mengimpor {$created} baris item rencana RKAS.";

            if (! empty($warnings)) {
                $warnCount = count($warnings);
                $preview = implode('<br>• ', array_slice($warnings, 0, 5));
                if ($warnCount > 5) {
                    $preview .= '<br>• ...dan '.($warnCount - 5).' peringatan harga lainnya.';
                }

                session()->flash('warning', "<strong>{$msg}</strong><br><small class='d-block mt-1'><strong>Catatan Peringatan Harga:</strong><br>• {$preview}</small>");

                return redirect()->back()->with('success', $msg);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal mengimpor berkas Excel: '.$e->getMessage());
        }
    }
}

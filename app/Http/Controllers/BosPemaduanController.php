<?php

namespace App\Http\Controllers;

use App\Models\BosKertasKerjaItem;
use App\Models\PengajuanBarang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BosPemaduanController extends Controller
{
    private array $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Tampilkan halaman utama pemaduan RKAS & Kertas Kerja BOS.
     */
    public function index(Request $request): View
    {
        $selectedYear = (string) $request->input('year', session('fiscal_year', date('Y')));
        $selectedMonth = (string) $request->input('month', 'Semua');
        $selectedStatus = (string) $request->input('status', 'all');
        $search = $request->input('search');

        // Parameter navigasi tampilan:
        // 1. 'grouped' => 📦 Seri Belanja Berulang (mode kelompok multi-bulan untuk padukan sekaligus)
        // 2. 'sekali'  => 1️⃣ Belanja Sekali (mode rinci khusus belanja insidental 1 bulan)
        // 3. 'single'  => 📄 Semua Data Rinci (mode rinci seluruh baris alokasi per bulan)
        $viewMode = (string) $request->input('view_mode', 'single');

        // Backward compatibility jika ada request dengan parameter 'karakter'
        if ($request->filled('karakter')) {
            $karakter = $request->input('karakter');
            if ($karakter === 'berulang') {
                $viewMode = 'grouped';
            } elseif ($karakter === 'sekali') {
                $viewMode = 'sekali';
            }
        }
        $selectedKarakter = $viewMode === 'sekali' ? 'sekali' : ($viewMode === 'grouped' ? 'berulang' : 'all');

        // Ambil daftar tahun unik dari kertas kerja dan pengajuan
        $kkYears = BosKertasKerjaItem::select('year')->distinct()->pluck('year')->map(fn ($y) => (string) $y);
        $pbYears = PengajuanBarang::select('tahun_anggaran')->distinct()->pluck('tahun_anggaran')->map(fn ($y) => (string) $y);
        $years = $kkYears->merge($pbYears)->merge([(string) date('Y')])->unique()->sortDesc()->values();

        $groupedItems = null;
        $items = null;

        // MODE TAMPILAN TERKELOMPOK (SERI BELANJA BERULANG)
        if ($viewMode === 'grouped') {
            $groupQuery = DB::table('bos_kertas_kerja_items as k')
                ->leftJoin('bos_kertas_kerja_files as f', 'k.bos_kertas_kerja_file_id', '=', 'f.id')
                ->leftJoin('bos_kertas_kerja_item_pengajuans as p', 'k.id', '=', 'p.bos_kertas_kerja_item_id')
                ->where('k.year', $selectedYear)
                ->where('f.is_active', true)
                ->whereNull('k.deleted_at')
                ->whereNull('f.deleted_at')
                ->select(
                    'k.account_code',
                    'k.kegiatan',
                    'k.description',
                    'k.price',
                    DB::raw('MIN(k.id) as first_id'),
                    DB::raw('MIN(k.unit) as sample_unit'),
                    DB::raw('COUNT(DISTINCT k.month) as total_months'),
                    DB::raw('COUNT(DISTINCT k.id) as total_items'),
                    DB::raw('COUNT(DISTINCT p.bos_kertas_kerja_item_id) as paired_items_count'),
                    DB::raw('SUM(k.volume) as total_volume'),
                    DB::raw('SUM(k.total_amount) as total_amount')
                )
                ->groupBy('k.account_code', 'k.kegiatan', 'k.description', 'k.price')
                ->havingRaw('COUNT(DISTINCT k.month) > 1');

            // Filter bulan jika spesifik
            if ($selectedMonth !== '' && $selectedMonth !== 'Semua') {
                $groupQuery->where('k.month', $selectedMonth);
            }

            // Filter status pemaduan
            if ($selectedStatus === 'paired') {
                $groupQuery->havingRaw('COUNT(DISTINCT p.bos_kertas_kerja_item_id) = COUNT(DISTINCT k.id)');
            } elseif ($selectedStatus === 'unpaired') {
                $groupQuery->havingRaw('COUNT(DISTINCT p.bos_kertas_kerja_item_id) < COUNT(DISTINCT k.id)');
            }

            // Pencarian teks
            if ($request->filled('search')) {
                $groupQuery->where(function ($q) use ($search) {
                    $q->where('k.description', 'like', "%{$search}%")
                        ->orWhere('k.kegiatan', 'like', "%{$search}%")
                        ->orWhere('k.account_code', 'like', "%{$search}%");
                });
            }

            $groupedItems = $groupQuery->orderBy('k.description', 'asc')
                ->paginate(20)
                ->withQueryString();

            // Load rincian bulanan & pengajuan yang terhubung untuk item pada halaman ini
            foreach ($groupedItems as $group) {
                $group->monthly_items = BosKertasKerjaItem::where('year', $selectedYear)
                    ->whereHas('file', fn ($f) => $f->where('is_active', true))
                    ->where('account_code', $group->account_code)
                    ->where('kegiatan', $group->kegiatan)
                    ->where('description', $group->description)
                    ->where('price', $group->price)
                    ->with(['pengajuanBarangs.guru', 'pengajuanBarangs.pemohon'])
                    ->orderByRaw("CASE month 
                        WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 
                        WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 
                        ELSE 99 END ASC")
                    ->get();

                $group->months_list = $group->monthly_items->pluck('month')->unique()->values()->all();

                // Kumpulkan seluruh pengajuan barang yang dipadukan beserta bulan pasangannya
                $pairedPengajuansWithMonth = collect();
                foreach ($group->monthly_items as $mItem) {
                    foreach ($mItem->pengajuanBarangs as $pb) {
                        $pairedPengajuansWithMonth->push([
                            'month' => $mItem->month,
                            'pengajuan' => $pb,
                        ]);
                    }
                }

                // Kelompokkan pengajuan yang dipadukan agar ditampilkan sebagai grup (bukan berulang per bulan)
                $group->paired_pengajuan_groups = $pairedPengajuansWithMonth
                    ->groupBy(function ($entry) {
                        $pb = $entry['pengajuan'];

                        return $pb->recurring_group_id ?: ($pb->nama_barang.'___'.$pb->kegiatan.'___'.(float) $pb->harga.'___'.($pb->spesifikasi ?? ''));
                    })
                    ->map(function ($entries) {
                        $firstEntry = $entries->first();
                        $firstPb = $firstEntry['pengajuan'];
                        $months = $entries->pluck('month')->unique()->values()->all();
                        $uniquePbs = $entries->pluck('pengajuan')->unique('id');

                        return (object) [
                            'group_key' => $firstPb->recurring_group_id ?: (string) $firstPb->id,
                            'recurring_group_id' => $firstPb->recurring_group_id,
                            'nama_barang' => $firstPb->nama_barang,
                            'spesifikasi' => $firstPb->spesifikasi,
                            'kegiatan' => $firstPb->kegiatan,
                            'satuan' => $firstPb->satuan,
                            'harga' => (float) $firstPb->harga,
                            'total_volume' => (float) $uniquePbs->sum('volume'),
                            'total_anggaran' => (float) $entries->sum(fn ($e) => (float) $e['pengajuan']->jumlah),
                            'total_months' => count($months),
                            'months' => $months,
                            'pemohon_nama' => $firstPb->guru ? $firstPb->guru->name : ($firstPb->pemohon ? $firstPb->pemohon->name : 'Sekolah'),
                            'is_recurring' => (bool) ($firstPb->is_recurring || count($months) > 1),
                            'items_count' => $entries->count(),
                        ];
                    })
                    ->values();

                $group->paired_pengajuans = $group->monthly_items
                    ->flatMap->pengajuanBarangs
                    ->unique('id')
                    ->values();

                $group->is_fully_paired = ($group->paired_items_count == $group->total_items && $group->total_items > 0);
                $group->is_partially_paired = ($group->paired_items_count > 0 && ! $group->is_fully_paired);
                $group->is_unpaired = ($group->paired_items_count == 0);
            }
        } else {
            // MODE TAMPILAN RINCI PER BULAN (SINGLE MODE)
            $query = BosKertasKerjaItem::where('year', $selectedYear)
                ->whereHas('file', fn ($f) => $f->where('is_active', true))
                ->with([
                    'pengajuanBarangs' => function ($q) {
                        $q->with(['guru', 'pemohon']);
                    },
                ]);

            // Filter bulan jika spesifik
            if ($selectedMonth !== '' && $selectedMonth !== 'Semua') {
                $query->where('month', $selectedMonth);
            }

            // Filter status pemaduan
            if ($selectedStatus === 'paired') {
                $query->whereHas('pengajuanBarangs');
            } elseif ($selectedStatus === 'unpaired') {
                $query->whereDoesntHave('pengajuanBarangs');
            }

            // Filter karakter (berulang vs sekali)
            if ($selectedKarakter === 'berulang' || $selectedKarakter === 'sekali') {
                $subRecurring = DB::table('bos_kertas_kerja_items as bkk_sub')
                    ->select('bkk_sub.account_code', 'bkk_sub.kegiatan', 'bkk_sub.description', 'bkk_sub.price')
                    ->where('bkk_sub.year', $selectedYear)
                    ->whereNull('bkk_sub.deleted_at')
                    ->groupBy('bkk_sub.account_code', 'bkk_sub.kegiatan', 'bkk_sub.description', 'bkk_sub.price');

                if ($selectedKarakter === 'berulang') {
                    $subRecurring->havingRaw('COUNT(DISTINCT bkk_sub.month) > 1');
                } else {
                    $subRecurring->havingRaw('COUNT(DISTINCT bkk_sub.month) = 1');
                }

                $query->joinSub($subRecurring, 'rec_filter', function ($join) {
                    $join->on('bos_kertas_kerja_items.account_code', '=', 'rec_filter.account_code')
                        ->on('bos_kertas_kerja_items.kegiatan', '=', 'rec_filter.kegiatan')
                        ->on('bos_kertas_kerja_items.description', '=', 'rec_filter.description')
                        ->on('bos_kertas_kerja_items.price', '=', 'rec_filter.price');
                })->select('bos_kertas_kerja_items.*');
            }

            // Pencarian teks
            if ($request->filled('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('bos_kertas_kerja_items.description', 'like', "%{$search}%")
                        ->orWhere('bos_kertas_kerja_items.kegiatan', 'like', "%{$search}%")
                        ->orWhere('bos_kertas_kerja_items.account_code', 'like', "%{$search}%")
                        ->orWhereHas('pengajuanBarangs', function ($qp) use ($search) {
                            $qp->where('nama_barang', 'like', "%{$search}%")
                                ->orWhere('spesifikasi', 'like', "%{$search}%")
                                ->orWhereHas('guru', fn ($qg) => $qg->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('pemohon', fn ($qu) => $qu->where('name', 'like', "%{$search}%"));
                        });
                });
            }

            // Urutkan berdasarkan bulan kalender, lalu id
            $items = $query->orderByRaw("CASE month 
                WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 
                WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 
                WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 
                WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 
                ELSE 99 END ASC")
                ->orderBy('bos_kertas_kerja_items.id', 'asc')
                ->paginate(25)
                ->withQueryString();
        }

        // Statistik ringkasan untuk header
        $statsBase = BosKertasKerjaItem::where('year', $selectedYear)
            ->whereHas('file', fn ($f) => $f->where('is_active', true));
        if ($selectedMonth !== '' && $selectedMonth !== 'Semua') {
            $statsBase->where('month', $selectedMonth);
        }

        $totalCount = (clone $statsBase)->count();
        $pairedCount = (clone $statsBase)->whereHas('pengajuanBarangs')->count();
        $unpairedCount = max(0, $totalCount - $pairedCount);
        $percentPaired = $totalCount > 0 ? (int) round(($pairedCount / $totalCount) * 100) : 0;

        // Hitung total grup berulang di tahun ini
        $recurringGroupsCount = DB::table('bos_kertas_kerja_items')
            ->select('account_code', 'kegiatan', 'description', 'price')
            ->where('year', $selectedYear)
            ->whereNull('deleted_at')
            ->groupBy('account_code', 'kegiatan', 'description', 'price')
            ->havingRaw('COUNT(DISTINCT month) > 1')
            ->get()
            ->count();

        // Hitung total belanja sekali di tahun ini
        $singleItemsCount = DB::table('bos_kertas_kerja_items')
            ->select('account_code', 'kegiatan', 'description', 'price')
            ->where('year', $selectedYear)
            ->whereNull('deleted_at')
            ->groupBy('account_code', 'kegiatan', 'description', 'price')
            ->havingRaw('COUNT(DISTINCT month) = 1')
            ->get()
            ->count();

        $stats = [
            'total' => $totalCount,
            'paired' => $pairedCount,
            'unpaired' => $unpairedCount,
            'percent' => $percentPaired,
            'recurring_groups' => $recurringGroupsCount,
            'single_items' => $singleItemsCount,
        ];

        $months = $this->months;

        return view('bos.pemaduan.index', compact(
            'items',
            'groupedItems',
            'years',
            'months',
            'selectedYear',
            'selectedMonth',
            'selectedStatus',
            'selectedKarakter',
            'viewMode',
            'search',
            'stats'
        ));
    }

    /**
     * Ambil daftar pengajuan barang riil yang tersedia pada tahun tersebut (untuk modal pop-up per item tunggal).
     */
    public function availablePengajuan(Request $request): JsonResponse
    {
        $year = (string) $request->input('year', session('fiscal_year', date('Y')));
        $search = $request->input('search');
        $kertasKerjaItemId = $request->input('kertas_kerja_item_id');

        $query = PengajuanBarang::with(['guru', 'pemohon', 'kertasKerjaItems'])
            ->where('tahun_anggaran', $year)
            ->whereDoesntHave('kertasKerjaItems');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%")
                    ->orWhereHas('guru', fn ($qg) => $qg->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('pemohon', fn ($qu) => $qu->where('name', 'like', "%{$search}%"));
            });
        }

        $pengajuans = $query->latest('id')->limit(50)->get();

        // Format data untuk respons JSON modal
        $data = $pengajuans->map(function ($p) use ($kertasKerjaItemId) {
            $isPairedWithThis = false;
            if ($kertasKerjaItemId) {
                $isPairedWithThis = $p->kertasKerjaItems->contains('id', (int) $kertasKerjaItemId);
            }

            return [
                'id' => $p->id,
                'nama_barang' => $p->nama_barang,
                'spesifikasi' => $p->spesifikasi,
                'volume' => (float) $p->volume,
                'satuan' => $p->satuan,
                'harga' => (float) $p->harga,
                'total' => (float) $p->jumlah,
                'bulan_pakai' => $p->bulan_pakai,
                'kegiatan' => $p->kegiatan,
                'is_recurring' => (bool) $p->is_recurring,
                'pemohon_nama' => $p->guru ? $p->guru->name : ($p->pemohon ? $p->pemohon->name : 'Tanpa Pemohon'),
                'is_paired_with_this' => $isPairedWithThis,
                'total_pairs_count' => $p->kertasKerjaItems->count(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Ambil daftar Rencana RKAS (Pengajuan) yang dikelompokkan (untuk modal pop-up Batch Pairing).
     */
    public function availableRecurringPengajuan(Request $request): JsonResponse
    {
        $year = (string) $request->input('year', session('fiscal_year', date('Y')));
        $search = $request->input('search');

        $query = PengajuanBarang::with(['guru', 'pemohon', 'kertasKerjaItems'])
            ->where('tahun_anggaran', $year)
            ->whereDoesntHave('kertasKerjaItems');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%")
                    ->orWhereHas('guru', fn ($qg) => $qg->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('pemohon', fn ($qu) => $qu->where('name', 'like', "%{$search}%"));
            });
        }

        $allPengajuans = $query->orderBy('nama_barang')->get();

        // Kelompokkan: jika punya recurring_group_id kelompokkan per recurring_group_id, jika tidak per nama+kegiatan+harga
        $grouped = $allPengajuans->groupBy(function ($item) {
            return $item->recurring_group_id ?: ('single_'.$item->nama_barang.'_'.$item->kegiatan.'_'.(float) $item->harga);
        });

        $data = [];
        foreach ($grouped as $groupKey => $items) {
            $first = $items->first();
            $months = $items->pluck('bulan_pakai')->filter()->unique()->values()->all();
            $totalBulan = count($months);
            $totalAnggaran = $items->sum('jumlah');

            $data[] = [
                'group_key' => $first->recurring_group_id ?: (string) $first->id,
                'recurring_group_id' => $first->recurring_group_id,
                'is_recurring' => (bool) ($first->is_recurring || $totalBulan > 1),
                'nama_barang' => $first->nama_barang,
                'spesifikasi' => $first->spesifikasi,
                'kegiatan' => $first->kegiatan,
                'satuan' => $first->satuan,
                'harga' => (float) $first->harga,
                'total_anggaran' => (float) $totalAnggaran,
                'total_months' => $totalBulan,
                'months' => $months,
                'pemohon_nama' => $first->guru ? $first->guru->name : ($first->pemohon ? $first->pemohon->name : 'Tanpa Pemohon'),
                'item_ids' => $items->pluck('id')->all(),
            ];
        }

        // Urutkan: kelompok berulang (total_months > 1) ditaruh paling atas
        usort($data, function ($a, $b) {
            if ($a['total_months'] === $b['total_months']) {
                return strcmp($a['nama_barang'], $b['nama_barang']);
            }

            return $b['total_months'] <=> $a['total_months'];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Pasangkan item Kertas Kerja BOS dengan Pengajuan Barang Riil (Tunggal).
     */
    public function pair(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'bos_kertas_kerja_item_id' => ['required', 'integer', 'exists:bos_kertas_kerja_items,id'],
            'pengajuan_barang_id' => ['required', 'integer', 'exists:pengajuan_barangs,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $item = BosKertasKerjaItem::findOrFail($validated['bos_kertas_kerja_item_id']);
        $pengajuan = PengajuanBarang::findOrFail($validated['pengajuan_barang_id']);

        $item->pengajuanBarangs()->syncWithoutDetaching([
            $validated['pengajuan_barang_id'] => ['notes' => $validated['notes'] ?? null],
        ]);

        if ($pengajuan->utility_profile_id) {
            $item->update(['utility_profile_id' => $pengajuan->utility_profile_id]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data Kertas Kerja berhasil dipadukan dengan pengajuan barang riil.',
            ]);
        }

        return redirect()->back()->with('success', 'Data Kertas Kerja berhasil dipadukan dengan pengajuan barang riil.');
    }

    /**
     * Lepaskan hubungan paduan antara Kertas Kerja BOS dan Pengajuan Barang Riil (Tunggal).
     */
    public function unpair(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'bos_kertas_kerja_item_id' => ['required', 'integer', 'exists:bos_kertas_kerja_items,id'],
            'pengajuan_barang_id' => ['required', 'integer', 'exists:pengajuan_barangs,id'],
        ]);

        $item = BosKertasKerjaItem::findOrFail($validated['bos_kertas_kerja_item_id']);
        $item->pengajuanBarangs()->detach($validated['pengajuan_barang_id']);

        $remainingUtilityId = $item->pengajuanBarangs()->whereNotNull('utility_profile_id')->value('utility_profile_id');
        $item->update(['utility_profile_id' => $remainingUtilityId]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Paduan data berhasil dilepaskan.',
            ]);
        }

        return redirect()->back()->with('success', 'Paduan data berhasil dilepaskan.');
    }

    /**
     * Pemaduan Sekaligus (Batch Pair) seluruh bulan untuk kelompok Belanja Berulang.
     */
    public function batchPair(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer'],
            'account_code' => ['required', 'string'],
            'kegiatan' => ['required', 'string'],
            'description' => ['required', 'string'],
            'price' => ['required'],
            'pengajuan_group_key' => ['required'],
            'notes' => ['nullable', 'string', 'max:500'],
            'bos_kertas_kerja_item_id' => ['nullable', 'integer'],
        ]);

        $refItem = ! empty($validated['bos_kertas_kerja_item_id'])
            ? BosKertasKerjaItem::find($validated['bos_kertas_kerja_item_id'])
            : null;

        $year = $refItem ? (int) $refItem->year : (int) $validated['year'];
        $accountCode = $refItem ? $refItem->account_code : $validated['account_code'];
        $kegiatan = $refItem ? $refItem->kegiatan : $validated['kegiatan'];
        $description = $refItem ? $refItem->description : html_entity_decode((string) $validated['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $price = $refItem ? (float) $refItem->price : (float) str_replace('.', '', (string) $validated['price']);
        $groupKey = (string) $validated['pengajuan_group_key'];
        $notes = $validated['notes'] ?? null;

        // 1. Ambil seluruh item Kertas Kerja dalam kelompok ini
        $kkItems = BosKertasKerjaItem::where('year', $year)
            ->whereHas('file', fn ($f) => $f->where('is_active', true))
            ->where('account_code', $accountCode)
            ->where('kegiatan', $kegiatan)
            ->where('description', $description)
            ->where('price', $price)
            ->get();

        // Fallback jika formatting harga atau spasi kegiatan sedikit berbeda
        if ($kkItems->isEmpty()) {
            $kkItems = BosKertasKerjaItem::where('year', $year)
                ->whereHas('file', fn ($f) => $f->where('is_active', true))
                ->where('account_code', $accountCode)
                ->where('description', $description)
                ->get();
        }

        if ($kkItems->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item Kertas Kerja tidak ditemukan.',
                ], 404);
            }

            return redirect()->back()->with('error', 'Item Kertas Kerja tidak ditemukan.');
        }

        // 2. Ambil seluruh Pengajuan Barang dalam kelompok ini
        $pengajuans = PengajuanBarang::where('tahun_anggaran', $year)
            ->where(function ($q) use ($groupKey) {
                $q->where('recurring_group_id', $groupKey)
                    ->orWhere('id', $groupKey);
            })
            ->get();

        if ($pengajuans->isEmpty() || ($pengajuans->count() === 1 && ! $pengajuans->first()->recurring_group_id)) {
            $singlePengajuan = $pengajuans->first() ?: PengajuanBarang::find($groupKey);
            if ($singlePengajuan && $singlePengajuan->recurring_group_id) {
                $pengajuans = PengajuanBarang::where('tahun_anggaran', $year)
                    ->where('recurring_group_id', $singlePengajuan->recurring_group_id)
                    ->get();
            } elseif ($singlePengajuan) {
                $matchingItems = PengajuanBarang::where('tahun_anggaran', $year)
                    ->where('nama_barang', $singlePengajuan->nama_barang)
                    ->where('kegiatan', $singlePengajuan->kegiatan)
                    ->where('harga', $singlePengajuan->harga)
                    ->get();
                $pengajuans = $matchingItems->isNotEmpty() ? $matchingItems : collect([$singlePengajuan]);
            }
        }

        $pengajuanByMonth = $pengajuans->keyBy('bulan_pakai');

        $pairedCount = 0;
        DB::transaction(function () use ($kkItems, $pengajuanByMonth, $pengajuans, $notes, &$pairedCount) {
            foreach ($kkItems as $kkItem) {
                $targetPengajuan = $pengajuanByMonth->get($kkItem->month);

                // Fallback jika hanya 1 pengajuan dan 1 item kk
                if (! $targetPengajuan && $pengajuans->count() === 1 && $kkItems->count() === 1) {
                    $targetPengajuan = $pengajuans->first();
                }

                if ($targetPengajuan) {
                    $kkItem->pengajuanBarangs()->syncWithoutDetaching([
                        $targetPengajuan->id => ['notes' => $notes],
                    ]);
                    if ($targetPengajuan->utility_profile_id) {
                        $kkItem->update(['utility_profile_id' => $targetPengajuan->utility_profile_id]);
                    }
                    $pairedCount++;
                }
            }
        });

        $samplePengajuan = $pengajuans->first();
        $namaPengajuan = $samplePengajuan ? $samplePengajuan->nama_barang : 'pengajuan barang';

        $msg = "Berhasil memadukan {$pairedCount} alokasi bulan untuk '{$description}' dengan '{$namaPengajuan}'.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'paired_count' => $pairedCount,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Pelepasan Paduan Sekaligus (Batch Unpair) seluruh alokasi bulan kelompok item.
     */
    public function batchUnpair(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer'],
            'account_code' => ['required', 'string'],
            'kegiatan' => ['required', 'string'],
            'description' => ['required', 'string'],
            'price' => ['required'],
            'bos_kertas_kerja_item_id' => ['nullable', 'integer'],
        ]);

        $refItem = ! empty($validated['bos_kertas_kerja_item_id'])
            ? BosKertasKerjaItem::find($validated['bos_kertas_kerja_item_id'])
            : null;

        $year = $refItem ? (int) $refItem->year : (int) $validated['year'];
        $accountCode = $refItem ? $refItem->account_code : $validated['account_code'];
        $kegiatan = $refItem ? $refItem->kegiatan : $validated['kegiatan'];
        $description = $refItem ? $refItem->description : html_entity_decode((string) $validated['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $price = $refItem ? (float) $refItem->price : (float) str_replace('.', '', (string) $validated['price']);

        $kkItems = BosKertasKerjaItem::where('year', $year)
            ->whereHas('file', fn ($f) => $f->where('is_active', true))
            ->where('account_code', $accountCode)
            ->where('kegiatan', $kegiatan)
            ->where('description', $description)
            ->where('price', $price)
            ->get();

        if ($kkItems->isEmpty()) {
            $kkItems = BosKertasKerjaItem::where('year', $year)
                ->whereHas('file', fn ($f) => $f->where('is_active', true))
                ->where('account_code', $accountCode)
                ->where('description', $description)
                ->get();
        }

        if ($kkItems->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item Kertas Kerja tidak ditemukan.',
                ], 404);
            }

            return redirect()->back()->with('error', 'Item Kertas Kerja tidak ditemukan.');
        }

        $unpairedCount = 0;
        DB::transaction(function () use ($kkItems, &$unpairedCount) {
            foreach ($kkItems as $kkItem) {
                $kkItem->pengajuanBarangs()->detach();
                $kkItem->update(['utility_profile_id' => null]);
                $unpairedCount++;
            }
        });

        $msg = "Paduan data untuk seluruh {$unpairedCount} alokasi bulan '{$description}' berhasil dilepaskan.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'unpaired_count' => $unpairedCount,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\PerjadinTemplateExport;
use App\Imports\PerjadinImport;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\Instansi;
use App\Models\Perjadin;
use App\Models\PerjadinItem;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PerjadinController extends Controller
{
    private function resolveFundingSource(Request $request): string
    {
        if ($request->routeIs('bpopp.*') || str_contains($request->path(), 'bpopp')) {
            return 'BPOPP';
        }

        return 'BOSP';
    }

    private function getRoutePrefix(string $fundingSource): string
    {
        return ($fundingSource === 'BOSP') ? 'bos' : 'bpopp';
    }

    private function getSmartActivityPurposes(): array
    {
        $existing = Perjadin::whereNotNull('activity_purpose')
            ->where('activity_purpose', '!=', '')
            ->select('activity_purpose', DB::raw('COUNT(*) as total_used'), DB::raw('MAX(departure_date) as last_used'))
            ->groupBy('activity_purpose')
            ->orderByDesc('total_used')
            ->orderByDesc('last_used')
            ->pluck('activity_purpose')
            ->values()
            ->toArray();

        $defaults = [
            'Konsultasi dan Koordinasi Laporan BOSP ke Cabang Dinas Pendidikan',
            'Menghadiri Rapat Koordinasi MKKS SMK se-Kabupaten Kediri',
            'Monitoring dan Evaluasi Praktik Kerja Lapangan (PKL) Siswa di DUDI',
            'Workshop dan Pelatihan Implementasi Kurikulum Merdeka',
            'Mendampingi Siswa Mengikuti Lomba Kompetensi Siswa (LKS)',
            'Menghadiri Undangan dan Rapat Dinas Pendidikan',
            'Pengambilan Ijazah, Blanko, dan Sertifikat Uji Kompetensi Keahlian',
            'Koordinasi Pelaksanaan Uji Kompetensi Keahlian (UKK) bersama Mitra Industri',
        ];

        return collect($existing)
            ->concat($defaults)
            ->unique()
            ->values()
            ->toArray();
    }

    public function index(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $selectedYear = $request->get('year', (string) session('fiscal_year', date('Y')));

        // Ambil daftar tahun anggaran unik dari departure_date + tahun berjalan
        $perjadinYears = Perjadin::where('funding_source', $fundingSource)
            ->whereNotNull('departure_date')
            ->pluck('departure_date')
            ->map(fn ($d) => (int) date('Y', strtotime($d)))
            ->unique();
        $availableYears = $perjadinYears->push((int) date('Y'))->unique()->sortDesc()->values();

        if ($request->ajax()) {
            if ($request->get('action') === 'counters') {
                $baseCounterQuery = Perjadin::where('funding_source', $fundingSource);
                if ($selectedYear !== 'all') {
                    $baseCounterQuery->whereYear('departure_date', $selectedYear);
                }

                return response()->json([
                    'total' => (clone $baseCounterQuery)->count(),
                    'unassigned' => (clone $baseCounterQuery)->where(function ($q) {
                        $q->whereNull('bku_number')->orWhere('bku_number', '');
                    })->count(),
                    'assigned' => (clone $baseCounterQuery)->whereNotNull('bku_number')->where('bku_number', '!=', '')->count(),
                ]);
            }

            $query = Perjadin::with(['items.guru', 'items.pencairanBankItem.pencairanBank', 'kepalaSekolah', 'bendahara'])
                ->where('funding_source', $fundingSource)
                ->orderBy('departure_date', 'desc');

            if ($selectedYear !== 'all') {
                $query->whereYear('departure_date', $selectedYear);
            }

            if ($request->filled('bku_status')) {
                if ($request->bku_status === 'unassigned') {
                    $query->where(function ($q) {
                        $q->whereNull('bku_number')->orWhere('bku_number', '');
                    });
                } elseif ($request->bku_status === 'assigned') {
                    $query->whereNotNull('bku_number')->where('bku_number', '!=', '');
                }
            }

            $baseCounterQuery = Perjadin::where('funding_source', $fundingSource);
            if ($selectedYear !== 'all') {
                $baseCounterQuery->whereYear('departure_date', $selectedYear);
            }
            $counters = [
                'total' => (clone $baseCounterQuery)->count(),
                'unassigned' => (clone $baseCounterQuery)->where(function ($q) {
                    $q->whereNull('bku_number')->orWhere('bku_number', '');
                })->count(),
                'assigned' => (clone $baseCounterQuery)->whereNotNull('bku_number')->where('bku_number', '!=', '')->count(),
            ];

            return DataTables::of($query)
                ->filter(function ($q) use ($request) {
                    if ($request->has('search') && ! empty($request->input('search')['value'])) {
                        $search = $request->input('search')['value'];
                        $q->where(function ($sub) use ($search) {
                            $sub->where('destination_name', 'like', "%{$search}%")
                                ->orWhere('activity_purpose', 'like', "%{$search}%")
                                ->orWhere('bku_number', 'like', "%{$search}%")
                                ->orWhere('account_code', 'like', "%{$search}%")
                                ->orWhereHas('items.guru', function ($gq) use ($search) {
                                    $gq->where('name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('items.pencairanBankItem.pencairanBank', function ($pq) use ($search) {
                                    $pq->where('letter_number', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->with('counters', $counters)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="check-perjadin form-check-input" value="'.$row->id.'" data-destination="'.e($row->destination_name).'" data-teachers="'.$row->items->count().'">';
                })
                ->addColumn('tujuan_display', function ($row) {
                    $dest = '<div class="font-weight-bold text-dark">'.e($row->destination_name).'</div>';
                    $purpose = '<small class="text-muted d-block">'.e($row->activity_purpose).'</small>';
                    $acc = $row->account_code ? '<span class="badge badge-light border font-tabular mt-1 mr-1">'.e($row->account_code).'</span>' : '';

                    $tntBadges = '';
                    if ($row->tnt_letters->isNotEmpty()) {
                        foreach ($row->tnt_letters as $letter) {
                            $tntBadges .= '<span class="badge badge-light border text-primary font-tabular font-weight-bold mt-1 mr-1" style="font-size: 0.78rem;" title="Nomor Surat Pencairan Bank (TNT)"><i class="fas fa-file-invoice-dollar mr-1 text-info"></i>Surat TNT: '.e($letter).'</span>';
                        }
                    }

                    return $dest.$purpose.'<div class="d-flex flex-wrap align-items-center">'.$acc.$tntBadges.'</div>';
                })
                ->addColumn('tanggal_display', function ($row) {
                    $dep = $row->departure_date ? $row->departure_date->format('d/m/Y') : '-';
                    $ret = $row->return_date ? ' s/d '.$row->return_date->format('d/m/Y') : '';
                    $tgl = '<div class="font-tabular font-weight-500">'.$dep.$ret.'</div>';
                    $pay = $row->payment_date ? '<small class="text-success d-block"><i class="fas fa-check-circle mr-1 me-1"></i>Bayar: '.$row->payment_date->format('d/m/Y').'</small>' : '<small class="text-warning d-block"><i class="fas fa-clock mr-1 me-1"></i>Belum Cair</small>';

                    return $tgl.$pay;
                })
                ->addColumn('bku_display', function ($row) {
                    $teachersCount = $row->items->count();
                    $depYear = $row->departure_date ? $row->departure_date->format('Y') : date('Y');

                    if ($row->bku_number) {
                        return '<div class="d-flex align-items-center justify-content-center gap-1 font-tabular">
                            <span class="badge badge-light border font-weight-bold text-dark py-1 px-2" style="font-size: 0.76rem;">'.e($row->bku_number).'</span>
                            <button type="button" class="btn btn-link btn-xs p-0 text-primary btn-edit-bku" data-id="'.$row->id.'" data-bku="'.e($row->bku_number).'" data-destination="'.e($row->destination_name).'" data-teachers="'.$teachersCount.'" data-year="'.$depYear.'" title="Ubah Nomor BKU">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>';
                    }

                    return '<button type="button" class="btn btn-xs btn-outline-warning text-dark btn-edit-bku font-weight-bold px-2 py-0" data-id="'.$row->id.'" data-bku="" data-destination="'.e($row->destination_name).'" data-teachers="'.$teachersCount.'" data-year="'.$depYear.'" title="Isi Nomor BKU" style="font-size: 0.72rem; border-radius: 4px;">
                        <i class="fas fa-plus mr-1 me-1 text-warning"></i>Isi BKU
                    </button>';
                })
                ->addColumn('peserta_display', function ($row) {
                    $count = $row->items->count();
                    $badges = '<div class="mb-1"><span class="badge badge-info font-tabular">'.$count.' Orang</span></div>';
                    $names = $row->items->take(2)->map(function ($item) {
                        return '<small class="d-block text-truncate" style="max-width: 180px;">• '.e($item->guru->name ?? '-').'</small>';
                    })->implode('');
                    if ($count > 2) {
                        $names .= '<small class="text-muted italic">+'.($count - 2).' lainnya...</small>';
                    }

                    return $badges.$names;
                })
                ->addColumn('nominal_display', function ($row) {
                    return '<span class="font-tabular font-weight-bold text-dark">Rp '.number_format($row->total_net_amount, 0, ',', '.').'</span>';
                })
                ->addColumn('status_display', function ($row) {
                    if ($row->status === 'lunas') {
                        return '<span class="badge badge-success"><i class="fas fa-check-double mr-1 me-1"></i>Lunas</span>';
                    } elseif ($row->status === 'diajukan_ke_bank') {
                        return '<span class="badge badge-info"><i class="fas fa-file-invoice-dollar mr-1 me-1"></i>Diajukan ke TNT</span>';
                    }

                    return '<span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1 me-1"></i>Draft</span>';
                })
                ->addColumn('action', function ($row) use ($routePrefix) {
                    $btnShow = '<a href="'.route($routePrefix.'.perjadin.show', $row->id).'" class="btn btn-xs btn-info" title="Detail & Kwitansi"><i class="fas fa-eye"></i></a>';
                    $btnPrint = '<a href="'.route($routePrefix.'.perjadin.print', $row->id).'" target="_blank" class="btn btn-xs btn-secondary" title="Cetak Semua Kwitansi"><i class="fas fa-print"></i></a>';
                    $btnEdit = '<a href="'.route($routePrefix.'.perjadin.edit', $row->id).'" class="btn btn-xs btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                    $btnDelete = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-url="'.route($routePrefix.'.perjadin.destroy', $row->id).'" data-name="'.e($row->destination_name).'" title="Hapus"><i class="fas fa-trash"></i></button>';

                    return '<div class="d-inline-flex align-items-center gap-1 text-nowrap text-center">'.$btnShow.$btnPrint.$btnEdit.$btnDelete.'</div>';
                })
                ->rawColumns(['checkbox', 'tujuan_display', 'tanggal_display', 'bku_display', 'peserta_display', 'nominal_display', 'status_display', 'action'])
                ->make(true);
        }

        $baseQuery = Perjadin::where('funding_source', $fundingSource);
        if ($selectedYear !== 'all') {
            $baseQuery->whereYear('departure_date', $selectedYear);
        }
        $totalCount = (clone $baseQuery)->count();
        $unassignedBkuCount = (clone $baseQuery)->where(function ($q) {
            $q->whereNull('bku_number')->orWhere('bku_number', '');
        })->count();
        $assignedBkuCount = (clone $baseQuery)->whereNotNull('bku_number')->where('bku_number', '!=', '')->count();

        return view('perjadin.index', compact('fundingSource', 'routePrefix', 'totalCount', 'unassignedBkuCount', 'assignedBkuCount', 'availableYears', 'selectedYear'));
    }

    public function updateBku(Request $request, Perjadin $perjadin)
    {
        $validated = $request->validate([
            'bku_number' => 'nullable|string|max:100',
        ]);

        $bkuNumber = ! empty($validated['bku_number']) ? trim($validated['bku_number']) : null;
        $perjadin->update(['bku_number' => $bkuNumber]);

        // Sinkronisasi otomatis ke bos_realisasies jika ada
        if ($perjadin->bos_kertas_kerja_item_id) {
            BosRealisasi::where('perjadin_id', $perjadin->id)
                ->update(['no_transaksi' => $bkuNumber]);
        }

        return response()->json([
            'success' => true,
            'message' => "Nomor BKU untuk '{$perjadin->destination_name}' berhasil diperbarui.",
            'bku_number' => $bkuNumber,
        ]);
    }

    public function batchUpdateBku(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:perjadins,id',
            'mode' => 'required|in:sequence,same',
            'bku_number' => 'nullable|string|max:100',
            'bku_start' => 'nullable|string|max:50',
            'year' => 'nullable|integer',
        ]);

        $perjadins = Perjadin::with('items')
            ->whereIn('id', $validated['ids'])
            ->orderBy('departure_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $updatedCount = 0;

        DB::transaction(function () use ($perjadins, $validated, &$updatedCount) {
            if ($validated['mode'] === 'same') {
                $bkuVal = ! empty($validated['bku_number']) ? trim($validated['bku_number']) : null;
                foreach ($perjadins as $perjadin) {
                    $perjadin->update(['bku_number' => $bkuVal]);
                    if ($perjadin->bos_kertas_kerja_item_id) {
                        BosRealisasi::where('perjadin_id', $perjadin->id)
                            ->update(['no_transaksi' => $bkuVal]);
                    }
                    $updatedCount++;
                }
            } else {
                // Mode berurutan otomatis (sequence)
                $rawStart = trim($validated['bku_start'] ?? 'BPU001');
                $baseYear = ! empty($validated['year']) ? (int) $validated['year'] : (int) date('Y');

                $cleanBase = preg_replace('/\s*\/\s*\d{4}$/', '', $rawStart);
                $prefix = 'BPU';
                $startNum = 1;
                $padLength = 3;

                if (preg_match('/^([A-Za-z\s]*?)(\d+)$/', $cleanBase, $m)) {
                    $prefix = trim($m[1]) ?: 'BPU';
                    $padLength = strlen($m[2]);
                    $startNum = (int) $m[2];
                }

                $currNum = $startNum;

                foreach ($perjadins as $perjadin) {
                    $teacherCount = $perjadin->items->count() ?: 1;
                    $startFormatted = $prefix.str_pad($currNum, $padLength, '0', STR_PAD_LEFT);

                    if ($teacherCount <= 1) {
                        $bkuVal = "{$startFormatted} / {$baseYear}";
                    } else {
                        $endNum = $currNum + $teacherCount - 1;
                        $endFormatted = $prefix.str_pad($endNum, $padLength, '0', STR_PAD_LEFT);
                        $bkuVal = "{$startFormatted} - {$endFormatted} / {$baseYear}";
                    }

                    $perjadin->update(['bku_number' => $bkuVal]);
                    if ($perjadin->bos_kertas_kerja_item_id) {
                        BosRealisasi::where('perjadin_id', $perjadin->id)
                            ->update(['no_transaksi' => $bkuVal]);
                    }

                    $currNum += $teacherCount;
                    $updatedCount++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Berhasil memperbarui nomor BKU untuk {$updatedCount} kegiatan perjalanan dinas.",
            'updated_count' => $updatedCount,
        ]);
    }

    public function create(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $school = SchoolProfile::first();

        $today = date('Y-m-d');
        $kepsekJabatan = GuruJabatan::getPejabatByDate('kepala_sekolah', $today);
        $bendaharaJabatan = GuruJabatan::getPejabatByDate('bendahara_'.strtolower($fundingSource), $today);

        $defaultKepsekId = $kepsekJabatan ? $kepsekJabatan->guru_id : null;
        $defaultBendaharaId = $bendaharaJabatan ? $bendaharaJabatan->guru_id : null;

        $gurus = Guru::with('primaryBankAccount')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $instansis = Instansi::orderBy('name')->get();

        $defaultAccountCode = $fundingSource === 'BOSP' ? '5.1.02.04.01.0003' : '5.1.02.04.01.0001';

        $kertasKerjaItems = collect([]);
        if ($fundingSource === 'BOSP') {
            $kertasKerjaItems = BosKertasKerjaItem::whereHas('file', function ($q) {
                $q->where('is_active', true);
            })
                ->where(function ($q) {
                    $q->where('account_code', 'like', '5.1.02.04%')
                        ->orWhere('description', 'like', '%perjalanan dinas%')
                        ->orWhere('description', 'like', '%transport%')
                        ->orWhere('kegiatan', 'like', '%perjalanan%');
                })
                ->with('realisasis')
                ->orderBy('year', 'desc')
                ->orderByRaw("FIELD(month, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') ASC")
                ->get();

            if ($kertasKerjaItems->isNotEmpty()) {
                $kkIds = $kertasKerjaItems->pluck('id');
                $realizedVolumes = BosRealisasi::whereIn('bos_kertas_kerja_item_id', $kkIds)
                    ->select('bos_kertas_kerja_item_id', DB::raw('SUM(volume) as total_realized'))
                    ->groupBy('bos_kertas_kerja_item_id')
                    ->pluck('total_realized', 'bos_kertas_kerja_item_id');

                $pendingQuery = PerjadinItem::whereHas('perjadin', function ($q) use ($kkIds) {
                    $q->whereIn('bos_kertas_kerja_item_id', $kkIds)
                        ->whereDoesntHave('realisasi');
                })
                    ->join('perjadins', 'perjadin_items.perjadin_id', '=', 'perjadins.id')
                    ->select('perjadins.bos_kertas_kerja_item_id', DB::raw('COUNT(perjadin_items.id) as total_pending'))
                    ->groupBy('perjadins.bos_kertas_kerja_item_id')
                    ->pluck('total_pending', 'perjadins.bos_kertas_kerja_item_id');

                foreach ($kertasKerjaItems as $kk) {
                    $realized = (float) ($realizedVolumes[$kk->id] ?? 0);
                    $pending = (float) ($pendingQuery[$kk->id] ?? 0);
                    $kk->available_volume = max(0, (float) $kk->volume - ($realized + $pending));
                }
            }
        }

        $availableYears = collect([(int) date('Y')]);
        if ($fundingSource === 'BOSP' && $kertasKerjaItems->isNotEmpty()) {
            $availableYears = $kertasKerjaItems->pluck('year')->unique()->push((int) date('Y'))->unique()->sortDesc()->values();
        }
        $selectedYear = (int) $request->input('year', date('Y'));
        $existingPurposes = $this->getSmartActivityPurposes();

        return view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'instansis',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'existingPurposes'
        ));
    }

    public function store(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);

        $validated = $request->validate([
            'bos_kertas_kerja_item_id' => 'nullable|exists:bos_kertas_kerja_items,id',
            'activity_purpose' => 'required|string',
            'destination_name' => 'required|string|max:255',
            'destination_address' => 'nullable|string',
            'departure_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'payment_date' => 'nullable|date',
            'bku_number' => 'nullable|string|max:100',
            'account_code' => 'nullable|string|max:50',
            'city' => 'required|string|max:100',
            'kepala_sekolah_id' => 'required|exists:gurus,id',
            'bendahara_id' => 'required|exists:gurus,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.guru_id' => 'required|exists:gurus,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.ppn' => 'nullable|numeric|min:0',
            'items.*.pph23' => 'nullable|numeric|min:0',
            'items.*.net_amount' => 'required|numeric|min:0',
            'items.*.guru_bank_account_id' => 'nullable|exists:guru_bank_accounts,id',
        ]);

        if (! empty($validated['bos_kertas_kerja_item_id'])) {
            $kkItem = BosKertasKerjaItem::find($validated['bos_kertas_kerja_item_id']);
            if ($kkItem) {
                $availableVolume = $kkItem->getAvailableVolumeForPerjadin();
                $participantCount = count($validated['items']);

                if ($availableVolume <= 0) {
                    return back()->withInput()->withErrors([
                        'bos_kertas_kerja_item_id' => "Alokasi volume kegiatan Kertas Kerja '{$kkItem->description}' sudah habis (sisa: 0 {$kkItem->unit}). Tidak dapat membuat perjalanan dinas dengan kegiatan ini.",
                    ]);
                }

                if ($participantCount > $availableVolume) {
                    return back()->withInput()->withErrors([
                        'bos_kertas_kerja_item_id' => "Jumlah peserta ({$participantCount} orang) melebihi sisa volume alokasi Kertas Kerja ({$availableVolume} {$kkItem->unit}).",
                    ]);
                }
            }
        }

        $status = ! empty($validated['payment_date']) ? 'lunas' : 'draft';

        // Auto-resolve or auto-create Instansi
        $destName = trim($validated['destination_name']);
        $destAddress = $validated['destination_address'] ?? null;
        $city = $validated['city'] ?? 'Kediri';

        $instansi = Instansi::firstOrCreate(
            ['name' => $destName],
            [
                'category' => Instansi::detectCategory($destName),
                'address' => $destAddress,
                'city' => $city,
            ]
        );

        if (! empty($destAddress) && empty($instansi->address)) {
            $instansi->update(['address' => $destAddress]);
        }

        $perjadin = Perjadin::create([
            'funding_source' => $fundingSource,
            'bos_kertas_kerja_item_id' => $validated['bos_kertas_kerja_item_id'] ?? null,
            'bku_number' => $validated['bku_number'] ?? null,
            'account_code' => $validated['account_code'] ?? null,
            'activity_purpose' => $validated['activity_purpose'],
            'destination_name' => $validated['destination_name'],
            'destination_address' => $validated['destination_address'] ?? null,
            'instansi_id' => $instansi->id,
            'departure_date' => $validated['departure_date'],
            'return_date' => $validated['return_date'] ?? null,
            'payment_date' => $validated['payment_date'] ?? null,
            'city' => $validated['city'],
            'kepala_sekolah_id' => $validated['kepala_sekolah_id'],
            'bendahara_id' => $validated['bendahara_id'],
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $itemData) {
            $bankAccId = $itemData['guru_bank_account_id'] ?? null;
            if (! $bankAccId) {
                $guru = Guru::with('primaryBankAccount')->find($itemData['guru_id']);
                $bankAccId = $guru && $guru->primaryBankAccount ? $guru->primaryBankAccount->id : null;
            }

            PerjadinItem::create([
                'perjadin_id' => $perjadin->id,
                'guru_id' => $itemData['guru_id'],
                'guru_bank_account_id' => $bankAccId,
                'amount' => $itemData['amount'],
                'ppn' => $itemData['ppn'] ?? 0,
                'pph23' => $itemData['pph23'] ?? 0,
                'net_amount' => $itemData['net_amount'],
                'is_paid' => $status === 'lunas',
            ]);
        }

        $routePrefix = $this->getRoutePrefix($fundingSource);

        return redirect()->route($routePrefix.'.perjadin.show', $perjadin->id)
            ->with('success', "Kegiatan perjalanan dinas ke '{$perjadin->destination_name}' berhasil disimpan.");
    }

    public function show(Request $request, Perjadin $perjadin)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $perjadin->load(['kertasKerjaItem', 'items.guru', 'items.bankAccount', 'items.pencairanBankItem.pencairanBank', 'kepalaSekolah', 'bendahara']);

        return view('perjadin.show', compact('perjadin', 'fundingSource', 'routePrefix'));
    }

    public function edit(Request $request, Perjadin $perjadin)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $perjadin->load(['items.guru.primaryBankAccount', 'items.bankAccount']);

        $gurus = Guru::with(['primaryBankAccount', 'bankAccounts'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $instansis = Instansi::orderBy('name')->get();

        $kertasKerjaItems = collect([]);
        if ($fundingSource === 'BOSP') {
            $kertasKerjaItems = BosKertasKerjaItem::whereHas('file', function ($q) {
                $q->where('is_active', true);
            })
                ->where(function ($q) {
                    $q->where('account_code', 'like', '5.1.02.04%')
                        ->orWhere('description', 'like', '%perjalanan dinas%')
                        ->orWhere('description', 'like', '%transport%')
                        ->orWhere('kegiatan', 'like', '%perjalanan%');
                })
                ->with('realisasis')
                ->orderBy('year', 'desc')
                ->orderByRaw("FIELD(month, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') ASC")
                ->get();

            if ($kertasKerjaItems->isNotEmpty()) {
                $kkIds = $kertasKerjaItems->pluck('id');
                $realizedVolumes = BosRealisasi::whereIn('bos_kertas_kerja_item_id', $kkIds)
                    ->select('bos_kertas_kerja_item_id', DB::raw('SUM(volume) as total_realized'))
                    ->groupBy('bos_kertas_kerja_item_id')
                    ->pluck('total_realized', 'bos_kertas_kerja_item_id');

                $pendingQuery = PerjadinItem::whereHas('perjadin', function ($q) use ($kkIds, $perjadin) {
                    $q->whereIn('bos_kertas_kerja_item_id', $kkIds)
                        ->where('id', '!=', $perjadin->id)
                        ->whereDoesntHave('realisasi');
                })
                    ->join('perjadins', 'perjadin_items.perjadin_id', '=', 'perjadins.id')
                    ->select('perjadins.bos_kertas_kerja_item_id', DB::raw('COUNT(perjadin_items.id) as total_pending'))
                    ->groupBy('perjadins.bos_kertas_kerja_item_id')
                    ->pluck('total_pending', 'perjadins.bos_kertas_kerja_item_id');

                foreach ($kertasKerjaItems as $kk) {
                    $realized = (float) ($realizedVolumes[$kk->id] ?? 0);
                    $pending = (float) ($pendingQuery[$kk->id] ?? 0);
                    $kk->available_volume = max(0, (float) $kk->volume - ($realized + $pending));
                }
            }
        }

        $availableYears = collect([(int) date('Y')]);
        if ($fundingSource === 'BOSP' && $kertasKerjaItems->isNotEmpty()) {
            $availableYears = $kertasKerjaItems->pluck('year')->unique()->push((int) date('Y'))->unique()->sortDesc()->values();
        }
        $currentYear = (int) date('Y');
        $selectedYear = $perjadin->kertasKerjaItem
            ? (int) $perjadin->kertasKerjaItem->year
            : ($perjadin->departure_date ? (int) $perjadin->departure_date->format('Y') : $currentYear);
        $existingPurposes = $this->getSmartActivityPurposes();

        return view('perjadin.edit', compact(
            'perjadin',
            'fundingSource',
            'routePrefix',
            'gurus',
            'instansis',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'existingPurposes'
        ));
    }

    public function update(Request $request, Perjadin $perjadin)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);

        $validated = $request->validate([
            'bos_kertas_kerja_item_id' => 'nullable|exists:bos_kertas_kerja_items,id',
            'activity_purpose' => 'required|string',
            'destination_name' => 'required|string|max:255',
            'destination_address' => 'nullable|string',
            'departure_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'payment_date' => 'nullable|date',
            'bku_number' => 'nullable|string|max:100',
            'account_code' => 'nullable|string|max:50',
            'city' => 'required|string|max:100',
            'kepala_sekolah_id' => 'required|exists:gurus,id',
            'bendahara_id' => 'required|exists:gurus,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.guru_id' => 'required|exists:gurus,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.ppn' => 'nullable|numeric|min:0',
            'items.*.pph23' => 'nullable|numeric|min:0',
            'items.*.net_amount' => 'required|numeric|min:0',
            'items.*.guru_bank_account_id' => 'nullable|exists:guru_bank_accounts,id',
        ]);

        if (! empty($validated['bos_kertas_kerja_item_id'])) {
            $kkItem = BosKertasKerjaItem::find($validated['bos_kertas_kerja_item_id']);
            if ($kkItem) {
                $availableVolume = $kkItem->getAvailableVolumeForPerjadin($perjadin->id);
                $participantCount = count($validated['items']);

                if ($availableVolume <= 0) {
                    return back()->withInput()->withErrors([
                        'bos_kertas_kerja_item_id' => "Alokasi volume kegiatan Kertas Kerja '{$kkItem->description}' sudah habis (sisa: 0 {$kkItem->unit}). Tidak dapat memilih kegiatan ini.",
                    ]);
                }

                if ($participantCount > $availableVolume) {
                    return back()->withInput()->withErrors([
                        'bos_kertas_kerja_item_id' => "Jumlah peserta ({$participantCount} orang) melebihi sisa volume alokasi Kertas Kerja ({$availableVolume} {$kkItem->unit}).",
                    ]);
                }
            }
        }

        $status = ! empty($validated['payment_date']) ? 'lunas' : $perjadin->status;

        // Auto-resolve or auto-create Instansi
        $destName = trim($validated['destination_name']);
        $destAddress = $validated['destination_address'] ?? null;
        $city = $validated['city'] ?? 'Kediri';

        $instansi = Instansi::firstOrCreate(
            ['name' => $destName],
            [
                'category' => Instansi::detectCategory($destName),
                'address' => $destAddress,
                'city' => $city,
            ]
        );

        if (! empty($destAddress) && empty($instansi->address)) {
            $instansi->update(['address' => $destAddress]);
        }

        $perjadin->update([
            'bos_kertas_kerja_item_id' => $validated['bos_kertas_kerja_item_id'] ?? null,
            'bku_number' => $validated['bku_number'] ?? null,
            'account_code' => $validated['account_code'] ?? null,
            'activity_purpose' => $validated['activity_purpose'],
            'destination_name' => $validated['destination_name'],
            'destination_address' => $validated['destination_address'] ?? null,
            'instansi_id' => $instansi->id,
            'departure_date' => $validated['departure_date'],
            'return_date' => $validated['return_date'] ?? null,
            'payment_date' => $validated['payment_date'] ?? null,
            'city' => $validated['city'],
            'kepala_sekolah_id' => $validated['kepala_sekolah_id'],
            'bendahara_id' => $validated['bendahara_id'],
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Recreate items or update
        $existingItemIds = $perjadin->items()->pluck('id')->toArray();
        $submittedItemIds = [];

        foreach ($validated['items'] as $itemData) {
            $itemId = $itemData['id'] ?? null;
            $bankAccId = $itemData['guru_bank_account_id'] ?? null;
            if (! $bankAccId) {
                $guru = Guru::with('primaryBankAccount')->find($itemData['guru_id']);
                $bankAccId = $guru && $guru->primaryBankAccount ? $guru->primaryBankAccount->id : null;
            }

            if ($itemId && in_array($itemId, $existingItemIds)) {
                $item = PerjadinItem::find($itemId);
                $item->update([
                    'guru_id' => $itemData['guru_id'],
                    'guru_bank_account_id' => $bankAccId,
                    'amount' => $itemData['amount'],
                    'ppn' => $itemData['ppn'] ?? 0,
                    'pph23' => $itemData['pph23'] ?? 0,
                    'net_amount' => $itemData['net_amount'],
                    'is_paid' => $status === 'lunas',
                ]);
                $submittedItemIds[] = $itemId;
            } else {
                $newItem = PerjadinItem::create([
                    'perjadin_id' => $perjadin->id,
                    'guru_id' => $itemData['guru_id'],
                    'guru_bank_account_id' => $bankAccId,
                    'amount' => $itemData['amount'],
                    'ppn' => $itemData['ppn'] ?? 0,
                    'pph23' => $itemData['pph23'] ?? 0,
                    'net_amount' => $itemData['net_amount'],
                    'is_paid' => $status === 'lunas',
                ]);
                $submittedItemIds[] = $newItem->id;
            }
        }

        // Delete removed items
        $toDelete = array_diff($existingItemIds, $submittedItemIds);
        if (! empty($toDelete)) {
            PerjadinItem::whereIn('id', $toDelete)->delete();
        }

        return redirect()->route($routePrefix.'.perjadin.show', $perjadin->id)
            ->with('success', "Data perjalanan dinas '{$perjadin->destination_name}' berhasil diperbarui.");
    }

    public function destroy(Request $request, Perjadin $perjadin)
    {
        $dest = $perjadin->destination_name;
        $perjadin->items()->delete();
        $perjadin->delete();

        return response()->json([
            'message' => "Data perjalanan dinas ke '{$dest}' berhasil dihapus.",
        ]);
    }

    public function printKwitansi(Request $request, Perjadin $perjadin, ?PerjadinItem $item = null)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $school = SchoolProfile::first();

        $perjadin->load(['kepalaSekolah', 'bendahara']);

        if ($item) {
            $items = collect([$item->load(['guru', 'bankAccount'])]);
        } else {
            $items = $perjadin->items()->with(['guru', 'bankAccount'])->get();
        }

        return view('perjadin.print_kwitansi', compact('perjadin', 'items', 'school', 'fundingSource', 'routePrefix'));
    }

    /**
     * Download template Excel untuk import batch Perjadin
     */
    public function downloadTemplate(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $filename = 'template_import_perjadin_'.strtolower($fundingSource).'.xlsx';

        return Excel::download(new PerjadinTemplateExport, $filename);
    }

    /**
     * Upload & import batch Perjadin dari file Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);

        try {
            $import = new PerjadinImport($fundingSource);
            Excel::import($import, $request->file('excel_file'));

            $results = $import->getResults();

            if ($results['perjadins'] === 0) {
                $errorMsg = 'Tidak ada kegiatan dinas yang berhasil diimpor.';
                if (! empty($results['warnings'])) {
                    $errorMsg .= ' Catatan: '.implode(' ', array_slice($results['warnings'], 0, 3));
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'results' => $results,
                    ], 422);
                }

                return redirect()->route($routePrefix.'.perjadin.index')
                    ->with('error', $errorMsg);
            }

            $msg = "Berhasil mengimpor {$results['perjadins']} kegiatan Perjadin dengan total {$results['items']} orang guru.";
            if ($results['skipped'] > 0) {
                $msg .= " ({$results['skipped']} baris dilewati).";
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'results' => $results,
                    'redirect' => route($routePrefix.'.perjadin.index'),
                ]);
            }

            return redirect()->route($routePrefix.'.perjadin.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor file Excel: '.$e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', 'Gagal mengimpor file Excel: '.$e->getMessage());
        }
    }
}

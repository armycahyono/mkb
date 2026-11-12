<?php

namespace App\Http\Controllers;

use App\Exports\BosBarangBelumBelanjaExport;
use App\Exports\BosDaftarRencanaBelanjaExport;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use App\Models\BosRencanaBelanjaItem;
use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\Supplier;
use App\Models\UtilityReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class BosPanduanBelanjaController extends Controller
{
    private $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request)
    {
        $selectedYear = (string) session('fiscal_year', date('Y'));
        $years = BosKertasKerjaItem::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([(int) $selectedYear]);
        }
        $suppliers = Supplier::with('bankAccounts')->where('is_active', true)->orderBy('name')->get();

        return view('bos.panduan.index', compact('years', 'suppliers', 'selectedYear'));
    }

    public function unrealizedData(Request $request)
    {
        $year = $request->input('year', session('fiscal_year', date('Y')));
        $currentMonthIndex = (int) date('n') - 1;
        $defaultMonth = $this->months[$currentMonthIndex] ?? 'Desember';
        $targetMonth = $request->input('month', $defaultMonth);
        $hidePlanned = $request->has('hide_planned') ? $request->boolean('hide_planned') : true;

        $targetIndex = array_search($targetMonth, $this->months);
        $allowedMonths = ($targetIndex === false) ? $this->months : array_slice($this->months, 0, $targetIndex + 1);

        $items = BosKertasKerjaItem::where('year', $year)
            ->whereIn('month', $allowedMonths)
            ->whereHas('file', function ($f) {
                $f->where('is_active', true);
            })
            ->with([
                'realisasis',
                'rencanaBelanjaItems.rencanaBelanja',
                'pengajuanBarangs' => function ($q) {
                    $q->with(['guru', 'pemohon']);
                },
            ])
            ->orderByRaw("CASE month WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 ELSE 99 END ASC")
            ->get();

        // Group by Account Code + Kegiatan + Description (Nama Barang) + Unit + Price (Harga)
        $grouped = [];
        foreach ($items as $item) {
            $groupKey = md5(
                trim($item->account_code).'_'.
                strtolower(trim($item->kegiatan)).'_'.
                strtolower(trim($item->description)).'_'.
                strtolower(trim($item->unit)).'_'.
                number_format((float) $item->price, 2, '.', '')
            );

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'id' => $item->id,
                    'account_code' => $item->account_code,
                    'program_code' => $item->program_code,
                    'kegiatan' => $item->kegiatan,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'price' => (float) $item->price,
                    'months_included' => [],
                    'total_budget_vol' => 0,
                    'total_realized_vol' => 0,
                    'total_planned_vol' => 0,
                    'active_plans' => [],
                    'paired_pengajuans' => [],
                ];
            }

            $grouped[$groupKey]['months_included'][] = $item->month;
            $grouped[$groupKey]['total_budget_vol'] += (float) $item->volume;
            $grouped[$groupKey]['total_realized_vol'] += (float) $item->realisasis->sum('volume');

            if ($item->relationLoaded('pengajuanBarangs')) {
                foreach ($item->pengajuanBarangs as $pb) {
                    $pemohonName = $pb->guru ? $pb->guru->name : ($pb->pemohon ? $pb->pemohon->name : 'Tanpa Pemohon');
                    $pairKey = md5(
                        strtolower(trim((string) $pb->nama_barang)).'_'.
                        strtolower(trim((string) ($pb->spesifikasi ?? ''))).'_'.
                        strtolower(trim((string) $pemohonName))
                    );

                    if (! isset($grouped[$groupKey]['paired_pengajuans'][$pairKey])) {
                        $grouped[$groupKey]['paired_pengajuans'][$pairKey] = [
                            'id' => $pb->id,
                            'nama_barang' => $pb->nama_barang,
                            'spesifikasi' => $pb->spesifikasi,
                            'pemohon' => $pemohonName,
                            'volume' => 0,
                            'satuan' => $pb->satuan,
                            'count' => 0,
                            'months' => [],
                            'is_recurring' => (bool) $pb->is_recurring,
                        ];
                    }

                    $grouped[$groupKey]['paired_pengajuans'][$pairKey]['count']++;
                    $grouped[$groupKey]['paired_pengajuans'][$pairKey]['volume'] += (float) $pb->volume;
                    $m = $pb->bulan_pakai ?: $item->month;
                    if ($m && ! in_array($m, $grouped[$groupKey]['paired_pengajuans'][$pairKey]['months'])) {
                        $grouped[$groupKey]['paired_pengajuans'][$pairKey]['months'][] = $m;
                    }
                }
            }

            if ($item->relationLoaded('rencanaBelanjaItems')) {
                foreach ($item->rencanaBelanjaItems as $rItem) {
                    $rb = $rItem->rencanaBelanja;
                    if ($rb && ! $rb->trashed() && $rb->status !== 'realized') {
                        $grouped[$groupKey]['total_planned_vol'] += (float) $rItem->volume_plan;
                        $notaLabel = $rb->no_nota ?: ('RB #'.$rb->id);
                        if (! in_array($notaLabel, $grouped[$groupKey]['active_plans'])) {
                            $grouped[$groupKey]['active_plans'][] = $notaLabel;
                        }
                    }
                }
            }
        }

        $result = [];
        foreach ($grouped as $g) {
            $unrealizedVol = max(0, $g['total_budget_vol'] - $g['total_realized_vol']);

            // Jika barang sudah 100% direalisasikan di BKU, jangan tampilkan di daftar belum terealisasi
            if ($unrealizedVol <= 0.001) {
                continue;
            }

            $availableVol = max(0, $unrealizedVol - $g['total_planned_vol']);
            $isFullyPlanned = ($availableVol <= 0.001);

            // Filter toggle: sembunyikan yang sudah 100% masuk rencana jika dicentang
            if ($hidePlanned && $isFullyPlanned) {
                continue;
            }

            $monthsStr = implode(', ', array_unique($g['months_included']));
            $availableAmount = $availableVol * $g['price'];

            $result[] = [
                'id' => $g['id'],
                'months_included' => $monthsStr,
                'program_code' => $g['program_code'] ?? '-',
                'account_code' => $g['account_code'],
                'kegiatan' => $g['kegiatan'],
                'description' => $g['description'],
                'unit' => $g['unit'],
                'price' => $g['price'],
                'price_display' => number_format($g['price'], 0, ',', '.'),
                'total_budget_vol' => $g['total_budget_vol'],
                'total_realized_vol' => $g['total_realized_vol'],
                'total_planned_vol' => $g['total_planned_vol'],
                'unrealized_volume' => $unrealizedVol,
                'available_volume' => $availableVol,
                'remaining_volume' => $availableVol,
                'remaining_amount' => $availableAmount,
                'remaining_amount_display' => number_format($availableAmount, 0, ',', '.'),
                'active_plans' => $g['active_plans'],
                'is_allocatable' => ! $isFullyPlanned,
                'paired_pengajuans' => array_values($g['paired_pengajuans']),
            ];
        }

        return DataTables::of(collect($result))
            ->addIndexColumn()
            ->blacklist(['kegiatan', 'checkbox'])
            ->addColumn('checkbox', function ($row) {
                if (! $row['is_allocatable']) {
                    $plansText = ! empty($row['active_plans']) ? implode(', ', $row['active_plans']) : 'Rencana Aktif';

                    return '<span class="text-secondary d-inline-block" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="right" title="Sudah dialokasikan penuh di: '.e($plansText).'"><i class="fas fa-lock text-muted"></i></span>';
                }

                return '<input type="checkbox" class="item-checkbox" value="'.$row['id'].'" data-json="'.e(json_encode($row)).'">';
            })
            ->rawColumns(['checkbox'])
            ->make(true);
    }

    /**
     * Export Excel Barang Belum Dibelanjakan
     */
    public function exportUnrealizedExcel(Request $request)
    {
        $year = $request->input('year', session('fiscal_year', date('Y')));
        $currentMonthIndex = (int) date('n') - 1;
        $defaultMonth = $this->months[$currentMonthIndex] ?? 'Desember';
        $targetMonth = $request->input('month', $defaultMonth);
        $hidePlanned = $request->has('hide_planned') ? $request->boolean('hide_planned') : true;

        $safeMonth = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $targetMonth);
        $filename = 'Barang_Belum_Belanja_'.$year.'_'.$safeMonth.'.xlsx';

        return Excel::download(new BosBarangBelumBelanjaExport($year, $targetMonth, $hidePlanned), $filename);
    }

    /**
     * Export Excel Daftar Rencana Belanja (Draft)
     */
    public function exportGroupsExcel(Request $request)
    {
        $year = $request->input('year', (string) session('fiscal_year', date('Y')));
        $statusFilter = $request->input('status', 'draft');

        $statusSuffix = $statusFilter ? strtoupper(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $statusFilter)) : 'ALL';
        $filename = 'Daftar_Rencana_Belanja_'.$year.'_'.$statusSuffix.'.xlsx';

        return Excel::download(new BosDaftarRencanaBelanjaExport($year, $statusFilter), $filename);
    }

    public function nextNotaNumber(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $noNota = BosRencanaBelanja::generateNoNota($year, $month);

        return response()->json(['no_nota' => $noNota]);
    }

    /**
     * Store a new group / Rencana Belanja (Nota Rencana Supplier)
     */
    public function storeGroup(Request $request)
    {
        if ($request->has('items') && is_array($request->items)) {
            $cleanedItems = [];
            foreach ($request->items as $it) {
                if (isset($it['price'])) {
                    $it['price'] = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', (string) $it['price'])));
                }
                if (isset($it['volume_plan'])) {
                    $it['volume_plan'] = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $it['volume_plan']));
                }
                $cleanedItems[] = $it;
            }
            $request->merge(['items' => $cleanedItems]);
        }

        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|string',
            'no_nota' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:255',
            'planned_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.bos_kertas_kerja_item_id' => 'required|exists:bos_kertas_kerja_items,id',
            'items.*.volume_plan' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.nama_pasar' => 'nullable|string|max:255',
            'items.*.spesifikasi' => 'nullable|string',
        ]);

        $noNota = $request->no_nota ?: BosRencanaBelanja::generateNoNota($request->year, $request->month);

        $supplierId = $request->input('supplier_id');
        $supplierName = $request->input('supplier_name');
        $bankName = $request->input('bank_name');
        $accountNumber = $request->input('account_number');

        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier) {
                $supplierName = $supplierName ?: $supplier->name;
                $bankName = $bankName ?: $supplier->bank_name;
                $accountNumber = $accountNumber ?: $supplier->account_number;
            }
        }

        // Validasi ketersediaan sisa anggaran sebelum membuat rencana
        foreach ($request->items as $itemData) {
            $kkItem = BosKertasKerjaItem::findOrFail($itemData['bos_kertas_kerja_item_id']);

            $allKkIds = BosKertasKerjaItem::where('year', $kkItem->year)
                ->where('account_code', $kkItem->account_code)
                ->where('kegiatan', $kkItem->kegiatan)
                ->where('description', $kkItem->description)
                ->where('unit', $kkItem->unit)
                ->where('price', $kkItem->price)
                ->whereHas('file', function ($f) {
                    $f->where('is_active', true);
                })
                ->pluck('id');

            $totalBudget = (float) BosKertasKerjaItem::whereIn('id', $allKkIds)->sum('volume');
            $totalRealized = (float) BosRealisasi::whereIn('bos_kertas_kerja_item_id', $allKkIds)->sum('volume');
            $activePlanned = (float) BosRencanaBelanjaItem::whereIn('bos_kertas_kerja_item_id', $allKkIds)
                ->whereHas('rencanaBelanja', function ($q) {
                    $q->whereNull('deleted_at')
                        ->where(function ($sq) {
                            $sq->whereNull('status')->orWhere('status', '!=', 'realized');
                        });
                })
                ->sum('volume_plan');

            $available = max(0, $totalBudget - ($totalRealized + $activePlanned));
            $requested = (float) $itemData['volume_plan'];

            if ($requested > ($available + 0.001)) {
                return response()->json([
                    'message' => "Volume untuk barang [{$kkItem->description}] ({$requested} {$kkItem->unit}) melebihi sisa anggaran yang tersedia! Sisa yang dapat dialokasikan: {$available} {$kkItem->unit} (Anggaran: {$totalBudget}, Realisasi BKU: {$totalRealized}, Sudah Masuk Rencana: {$activePlanned}).",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $group = BosRencanaBelanja::create([
                'supplier_id' => $supplierId,
                'no_nota' => $noNota,
                'year' => $request->year,
                'month' => $request->month,
                'title' => $request->title,
                'supplier_name' => $supplierName,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'planned_date' => $request->planned_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $itemData) {
                $kkItem = BosKertasKerjaItem::findOrFail($itemData['bos_kertas_kerja_item_id']);
                $totalAmount = $itemData['volume_plan'] * $itemData['price'];

                $itemPayload = [
                    'bos_rencana_belanja_id' => $group->id,
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => $itemData['volume_plan'],
                    'unit' => $kkItem->unit,
                    'price' => $itemData['price'],
                    'total_amount' => $totalAmount,
                ];
                if (Schema::hasColumn('bos_rencana_belanja_items', 'nama_pasar')) {
                    $itemPayload['nama_pasar'] = ! empty($itemData['nama_pasar']) ? trim($itemData['nama_pasar']) : null;
                }
                if (Schema::hasColumn('bos_rencana_belanja_items', 'spesifikasi')) {
                    $itemPayload['spesifikasi'] = ! empty($itemData['spesifikasi']) ? trim($itemData['spesifikasi']) : null;
                }
                BosRencanaBelanjaItem::create($itemPayload);
            }

            DB::commit();

            return response()->json(['message' => 'Rencana belanja berhasil dibuat dengan No. Rencana: '.$noNota]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal membuat rencana: '.$e->getMessage()], 422);
        }
    }

    /**
     * DataTables for saved Rencana Belanja / Groups
     */
    public function groupsData(Request $request)
    {
        $selectedYear = $request->input('year', (string) session('fiscal_year', date('Y')));
        $statusFilter = $request->input('status');
        $query = BosRencanaBelanja::with(['items.kertasKerjaItem', 'creator', 'pencairanBankItem.pencairanBank'])
            ->when($selectedYear && $selectedYear !== 'all', function ($q) use ($selectedYear) {
                $q->where('year', $selectedYear);
            })
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            })
            ->orderByRaw("CASE WHEN status = 'realized' THEN 2 ELSE 1 END ASC")
            ->orderBy('id', 'desc');

        return DataTables::of($query)
            ->order(function ($q) {
                $q->orderByRaw("CASE WHEN status = 'realized' THEN 2 ELSE 1 END ASC");

                $orders = request()->input('order', []);
                $columns = request()->input('columns', []);

                if (! empty($orders)) {
                    foreach ($orders as $order) {
                        $colIdx = (int) ($order['column'] ?? -1);
                        $dir = strtolower($order['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
                        $colName = $columns[$colIdx]['name'] ?? ($columns[$colIdx]['data'] ?? null);

                        if ($colName && in_array($colName, ['id', 'no_nota', 'title', 'supplier_name', 'planned_date', 'status', 'year', 'month', 'created_at'])) {
                            $q->orderBy($colName, $dir);
                        }
                    }
                }

                $q->orderBy('id', 'desc');
            })
            ->filter(function ($q) use ($request) {
                $search = $request->input('search.value') ?? ($request->input('search')['value'] ?? null);
                if (! empty($search)) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('no_nota', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%")
                            ->orWhere('supplier_name', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('planned_date', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%")
                            ->orWhereHas('items.kertasKerjaItem', function ($itemQ) use ($search) {
                                $itemQ->where('description', 'like', "%{$search}%")
                                    ->orWhere('account_code', 'like', "%{$search}%")
                                    ->orWhere('kegiatan', 'like', "%{$search}%");
                            })
                            ->when(Schema::hasTable('pencairan_bank_items'), function ($sub) use ($search) {
                                $sub->orWhereHas('pencairanBankItem.pencairanBank', function ($bankQ) use ($search) {
                                    $bankQ->where('letter_number', 'like', "%{$search}%");
                                });
                            });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('btn_expand', function ($row) {
                return '<button type="button" class="btn btn-xs btn-outline-info btn-toggle-detail px-2 py-0" style="font-size: 0.72rem; border-radius: 4px;" title="Buka / Tutup Rincian"><i class="fas fa-plus"></i></button>';
            })
            ->addColumn('no_nota_display', function ($row) {
                return $row->no_nota ?: '-';
            })
            ->addColumn('planned_date_display', function ($row) {
                return $row->planned_date ? $row->planned_date->format('d/m/Y') : '-';
            })
            ->addColumn('items_count', function ($row) {
                return $row->items->count().' barang';
            })
            ->addColumn('total_amount_display', function ($row) {
                return number_format($row->total_amount, 0, ',', '.');
            })
            ->addColumn('status_badge', function ($row) {
                if ($row->status === 'realized') {
                    return '<span class="badge badge-success font-weight-bold" style="font-size: 0.78rem; padding: 3px 7px; border-radius: 4px;">Realisasi</span>';
                }
                if ($row->status === 'ordered') {
                    return '<span class="badge badge-primary font-weight-bold" style="font-size: 0.78rem; padding: 3px 7px; border-radius: 4px; background-color: #0284c7 !important; border: 1px solid #0369a1;">Order</span>';
                }
                if ($row->status === 'diajukan_ke_bank') {
                    return '<span class="badge badge-info font-weight-bold" style="font-size: 0.78rem; padding: 3px 7px; border-radius: 4px;">TNT</span>';
                }

                return '<span class="badge badge-warning text-dark font-weight-bold" style="font-size: 0.78rem; padding: 3px 7px; border-radius: 4px;">Draft</span>';
            })
            ->addColumn('tnt_letter_number', function ($row) {
                return $row->pencairanBankItem?->pencairanBank?->letter_number;
            })
            ->addColumn('items_data', function ($row) {
                return $row->items->map(function ($it) {
                    $kk = $it->kertasKerjaItem;

                    return [
                        'id' => $it->id,
                        'account_code' => $kk->account_code ?? '-',
                        'program_code' => $kk->program_code ?? '-',
                        'description' => $kk->description ?? '-',
                        'nama_pasar' => $it->nama_pasar,
                        'spesifikasi' => $it->spesifikasi,
                        'effective_description' => $it->effective_description,
                        'kegiatan' => $kk->kegiatan ?? '-',
                        'volume_plan' => (float) $it->volume_plan,
                        'unit' => $it->unit,
                        'price' => (float) $it->price,
                        'price_display' => number_format($it->price, 0, ',', '.'),
                        'total_amount' => (float) $it->total_amount,
                        'total_amount_display' => number_format($it->total_amount, 0, ',', '.'),
                    ];
                });
            })
            ->addColumn('action', function ($row) {
                $btn = '<button type="button" class="btn btn-outline-info btn-xs btn-show-detail mr-1" data-id="'.$row->id.'" title="Lihat Detail Modal"><i class="fas fa-eye"></i></button>';
                $btn .= '<a href="'.route('bos.panduan.print', $row->id).'" target="_blank" class="btn btn-info btn-xs mr-1" title="Cetak Panduan Belanja / Checklist"><i class="fas fa-print"></i></a>';

                if ($row->status === 'draft') {
                    $btn .= '<button type="button" class="btn btn-outline-primary btn-xs btn-toggle-order mr-1" data-id="'.$row->id.'" data-status="ordered" title="Tandai pesanan ini SUDAH DIORDER"><i class="fas fa-cart-arrow-down"></i></button>';
                } elseif ($row->status === 'ordered') {
                    $btn .= '<button type="button" class="btn btn-primary btn-xs btn-toggle-order mr-1" data-id="'.$row->id.'" data-status="draft" title="Klik untuk membatalkan status Diorder (kembali ke Draft)"><i class="fas fa-check"></i></button>';
                }

                $btn .= '<button class="btn btn-warning btn-xs btn-edit-group mr-1" data-id="'.$row->id.'" title="Edit Rencana Belanja"><i class="fas fa-edit"></i></button>';

                if (in_array($row->status, ['draft', 'ordered'])) {
                    $btn .= '<button class="btn btn-success btn-xs btn-convert mr-1" data-id="'.$row->id.'" title="Proses Jadi Realisasi BKU"><i class="fas fa-receipt"></i></button>';
                }

                $btn .= '<button class="btn btn-danger btn-xs btn-delete-group" data-url="'.route('bos.panduan.destroy-group', $row->id).'" title="Hapus Rencana"><i class="fas fa-trash"></i></button>';

                return $btn;
            })
            ->rawColumns(['btn_expand', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Get detail of a group for view / kroscek
     */
    public function showGroup($id)
    {
        $group = BosRencanaBelanja::with([
            'items.kertasKerjaItem.pengajuanBarangs.guru',
            'items.kertasKerjaItem.pengajuanBarangs.pemohon',
            'creator',
            'supplier',
            'pencairanBankItem.pencairanBank',
        ])->findOrFail($id);
        $group->tnt_letter_number = $group->pencairanBankItem?->pencairanBank?->letter_number;

        return response()->json($group);
    }

    /**
     * Get detail of a group for editing
     */
    public function editGroup($id)
    {
        $group = BosRencanaBelanja::with([
            'items.kertasKerjaItem.pengajuanBarangs.guru',
            'items.kertasKerjaItem.pengajuanBarangs.pemohon',
            'supplier',
        ])->findOrFail($id);

        return response()->json($group);
    }

    /**
     * Update existing group / Rencana Belanja
     */
    public function updateGroup(Request $request, $id)
    {
        $group = BosRencanaBelanja::findOrFail($id);

        if ($request->has('items') && is_array($request->items)) {
            $cleanedItems = [];
            foreach ($request->items as $it) {
                if (isset($it['price'])) {
                    $it['price'] = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', (string) $it['price'])));
                }
                if (isset($it['volume_plan'])) {
                    $it['volume_plan'] = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $it['volume_plan']));
                }
                $cleanedItems[] = $it;
            }
            $request->merge(['items' => $cleanedItems]);
        }

        $request->validate([
            'no_nota' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:255',
            'planned_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:bos_rencana_belanja_items,id',
            'items.*.volume_plan' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.nama_pasar' => 'nullable|string|max:255',
            'items.*.spesifikasi' => 'nullable|string',
        ]);

        $noNota = $request->no_nota ?: ($group->no_nota ?: BosRencanaBelanja::generateNoNota($group->year, $group->month));

        $supplierId = $request->input('supplier_id');
        $supplierName = $request->input('supplier_name');
        $bankName = $request->filled('bank_name') ? $request->input('bank_name') : $group->bank_name;
        $accountNumber = $request->filled('account_number') ? $request->input('account_number') : $group->account_number;

        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier) {
                $supplierName = $supplierName ?: $supplier->name;
                if (! $bankName) {
                    $bankName = $supplier->bank_name;
                }
                if (! $accountNumber) {
                    $accountNumber = $supplier->account_number;
                }
            }
        }

        // Validasi ketersediaan sisa anggaran sebelum memperbarui rencana
        foreach ($request->items as $itemData) {
            $rItem = BosRencanaBelanjaItem::findOrFail($itemData['id']);
            $kkItem = $rItem->kertasKerjaItem;

            if ($kkItem) {
                $allKkIds = BosKertasKerjaItem::where('year', $kkItem->year)
                    ->where('account_code', $kkItem->account_code)
                    ->where('kegiatan', $kkItem->kegiatan)
                    ->where('description', $kkItem->description)
                    ->where('unit', $kkItem->unit)
                    ->where('price', $kkItem->price)
                    ->whereHas('file', function ($f) {
                        $f->where('is_active', true);
                    })
                    ->pluck('id');

                $totalBudget = (float) BosKertasKerjaItem::whereIn('id', $allKkIds)->sum('volume');
                $totalRealized = (float) BosRealisasi::whereIn('bos_kertas_kerja_item_id', $allKkIds)->sum('volume');
                $activePlannedOther = (float) BosRencanaBelanjaItem::whereIn('bos_kertas_kerja_item_id', $allKkIds)
                    ->where('bos_rencana_belanja_id', '!=', $group->id)
                    ->whereHas('rencanaBelanja', function ($q) {
                        $q->whereNull('deleted_at')
                            ->where(function ($sq) {
                                $sq->whereNull('status')->orWhere('status', '!=', 'realized');
                            });
                    })
                    ->sum('volume_plan');

                $available = max(0, $totalBudget - ($totalRealized + $activePlannedOther));
                $requested = (float) $itemData['volume_plan'];

                if ($requested > ($available + 0.001)) {
                    return response()->json([
                        'message' => "Volume untuk barang [{$kkItem->description}] ({$requested} {$kkItem->unit}) melebihi sisa anggaran! Sisa yang dapat dialokasikan: {$available} {$kkItem->unit}.",
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $group->update([
                'supplier_id' => $supplierId,
                'no_nota' => $noNota,
                'title' => $request->title,
                'supplier_name' => $supplierName,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'planned_date' => $request->planned_date,
                'notes' => $request->notes,
            ]);

            // If already realized, sync no_nota to existing realisasis
            BosRealisasi::where('bos_rencana_belanja_id', $group->id)->update(['no_nota' => $noNota]);

            $submittedItemIds = collect($request->items)->pluck('id')->filter()->toArray();
            $group->items()->whereNotIn('id', $submittedItemIds)->delete();

            foreach ($request->items as $itemData) {
                $item = BosRencanaBelanjaItem::findOrFail($itemData['id']);
                $totalAmount = $itemData['volume_plan'] * $itemData['price'];

                $itemUpdatePayload = [
                    'volume_plan' => $itemData['volume_plan'],
                    'price' => $itemData['price'],
                    'total_amount' => $totalAmount,
                ];
                if (Schema::hasColumn('bos_rencana_belanja_items', 'nama_pasar')) {
                    $itemUpdatePayload['nama_pasar'] = ! empty($itemData['nama_pasar']) ? trim($itemData['nama_pasar']) : null;
                }
                if (Schema::hasColumn('bos_rencana_belanja_items', 'spesifikasi')) {
                    $itemUpdatePayload['spesifikasi'] = ! empty($itemData['spesifikasi']) ? trim($itemData['spesifikasi']) : null;
                }
                $item->update($itemUpdatePayload);
            }

            DB::commit();

            return response()->json(['message' => 'Rencana belanja berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal memperbarui rencana: '.$e->getMessage()], 422);
        }
    }

    /**
     * Print Shopping Guide / Checklist for Store
     */
    public function printGroup($id)
    {
        $group = BosRencanaBelanja::with([
            'items.kertasKerjaItem.pengajuanBarangs.guru',
            'items.kertasKerjaItem.pengajuanBarangs.pemohon',
            'supplier',
        ])->findOrFail($id);
        $targetDate = $group->planned_date ?: now();
        $kepsekName = 'ENDANG PATMOWATI, S.Pd';
        try {
            if (Schema::hasTable('guru_jabatans')) {
                $kepalaSekolah = GuruJabatan::getPejabatByDate('kepala_sekolah', $targetDate)?->guru;
                if ($kepalaSekolah) {
                    $kepsekName = $kepalaSekolah->full_name_with_title;
                }
            }
        } catch (\Throwable) {
            // fallback default
        }

        // Kelompokkan items per Program/Kegiatan, lalu per Kode Rekening
        $groupedItems = $group->items->groupBy(function ($item) {
            $kk = $item->kertasKerjaItem;
            $code = $kk?->program_code ?: '-';
            $keg = $kk?->kegiatan ?: 'Tanpa Kegiatan';

            return $code !== '-' ? "{$code} - {$keg}" : $keg;
        })->map(function ($itemsInKegiatan) {
            return $itemsInKegiatan->groupBy(function ($item) {
                return $item->kertasKerjaItem?->account_code ?: 'Tanpa Kode Rekening';
            });
        });

        return view('bos.panduan.print', compact('group', 'kepsekName', 'groupedItems'));
    }

    /**
     * One-Click Convert Rencana Belanja Group -> BosRealisasi records
     */
    public function convertToRealisasi(Request $request, $id)
    {
        $group = BosRencanaBelanja::with('items.kertasKerjaItem')->findOrFail($id);

        if ($group->status === 'realized') {
            return response()->json(['message' => 'Rencana belanja ini sudah pernah direalisasikan.'], 422);
        }

        $request->validate([
            'no_transaksi' => 'nullable|string|max:50',
            'transaction_date' => 'required|date',
            'receipt_date' => 'nullable|date',
        ]);

        $noNota = $group->no_nota ?: BosRencanaBelanja::generateNoNota($group->year, $group->month);
        if (! $group->no_nota) {
            $group->update(['no_nota' => $noNota]);
        }

        DB::beginTransaction();
        try {
            foreach ($group->items as $item) {
                BosRealisasi::create([
                    'bos_kertas_kerja_item_id' => $item->bos_kertas_kerja_item_id,
                    'no_transaksi' => $request->no_transaksi,
                    'no_nota' => $noNota,
                    'bos_rencana_belanja_id' => $group->id,
                    'year' => $group->year,
                    'month' => $item->kertasKerjaItem?->month ?: $group->month,
                    'transaction_date' => $request->transaction_date,
                    'receipt_date' => $request->receipt_date ?? $request->transaction_date,
                    'volume' => $item->volume_plan,
                    'unit' => $item->unit,
                    'price' => $item->price,
                    'total_amount' => $item->total_amount,
                    'notes' => 'Direalisasikan dari Panduan Belanja: '.$group->title.($group->supplier_name ? ' ('.$group->supplier_name.')' : ''),
                    'created_by' => Auth::id(),
                ]);
            }

            $group->update(['status' => 'realized']);

            DB::commit();

            return response()->json(['message' => 'Berhasil memproses rencana belanja menjadi transaksi Realisasi Belanja BOS.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal memproses realisasi: '.$e->getMessage()], 422);
        }
    }

    /**
     * Toggle Order status (Draft <-> Ordered in SIPLah)
     */
    public function toggleOrderStatus(Request $request, $id)
    {
        $group = BosRencanaBelanja::findOrFail($id);

        if ($group->status === 'realized') {
            return response()->json(['message' => 'Rencana belanja yang sudah direalisasikan tidak dapat diubah status ordernya.'], 422);
        }

        $targetStatus = $request->input('status');
        if (! in_array($targetStatus, ['draft', 'ordered'])) {
            $targetStatus = ($group->status === 'ordered') ? 'draft' : 'ordered';
        }

        $group->update(['status' => $targetStatus]);

        $statusLabel = ($targetStatus === 'ordered') ? 'SUDAH DIORDER' : 'DRAFT RENCANA';
        $message = "Status rencana belanja {$group->no_nota} berhasil diubah menjadi: {$statusLabel}.";

        return response()->json([
            'message' => $message,
            'status' => $targetStatus,
            'group' => $group,
        ]);
    }

    /**
     * Get data for Modal Bayar Utilitas (receipts, KK utility items, gurus)
     */
    public function getUtilityPaymentModalData(Request $request)
    {
        $year = (int) $request->input('year', session('fiscal_year', date('Y')));
        $currentMonthIndex = (int) date('n') - 1;
        $defaultMonth = $this->months[$currentMonthIndex] ?? 'Desember';
        $month = $request->input('month', $defaultMonth);

        // 1. Ambil struk utilitas yang belum masuk rencana belanja dan belum dibayar (khusus BOS / bukan SMP PGRI)
        $receipts = UtilityReceipt::with(['profile.defaultReimburseGuru.primaryBankAccount', 'kertasKerjaItem'])
            ->whereNull('bos_rencana_belanja_id')
            ->where('is_paid', false)
            ->where(function ($q) {
                $q->whereHas('profile', function ($pq) {
                    $pq->where('is_budgeted', true)
                        ->where(function ($codeQ) {
                            $codeQ->whereNull('code')->orWhere('code', '!=', 'listrik_smp');
                        })
                        ->where('name', 'not like', '%smp%');
                })->where(function ($cq) {
                    $cq->whereNull('customer_name')
                        ->orWhere('customer_name', 'not like', '%smp%');
                });
            })
            ->where(function ($q) use ($year) {
                $q->where('fiscal_year', $year)
                    ->orWhere(function ($sub) use ($year) {
                        $sub->whereNull('fiscal_year')
                            ->where(function ($dateQ) use ($year) {
                                $dateQ->whereYear('transaction_date', $year)
                                    ->orWhereYear('created_at', $year);
                            });
                    });
            })
            ->when($month && $month !== 'all', function ($q) use ($month) {
                $q->where(function ($sub) use ($month) {
                    $sub->where('billing_month', $month)
                        ->orWhere(function ($emptyQ) use ($month) {
                            $targetMonthNum = array_search($month, $this->months);
                            if ($targetMonthNum !== false) {
                                $emptyQ->where(function ($eq) {
                                    $eq->whereNull('billing_month')->orWhere('billing_month', '');
                                })->whereMonth('transaction_date', $targetMonthNum + 1);
                            }
                        });
                });
            })
            ->orderBy('transaction_date', 'asc')
            ->get();

        // 2. Ambil Kertas Kerja Langganan Daya & Jasa serta BBM tahun terpilih
        $targetKegiatans = [
            'pembayaran daya listrik',
            'pembayaran biaya telepon',
            'pembayaran jasa internet',
            'pembelian bahan bakar minyak/gas untuk keperluan pembelajaran/rt sekolah',
        ];

        $kkUtilityItems = BosKertasKerjaItem::where('year', $year)
            ->whereHas('file', fn ($f) => $f->where('is_active', true))
            ->where(function ($q) use ($targetKegiatans) {
                $q->whereIn(DB::raw('LOWER(TRIM(kegiatan))'), $targetKegiatans)
                    ->orWhereNotNull('utility_profile_id');
            })
            ->orderByRaw("CASE month WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 ELSE 99 END ASC")
            ->get();

        $kkFormatted = $kkUtilityItems->map(function ($item) {
            $totalBudget = (float) $item->volume * (float) $item->price;
            if ($totalBudget <= 0 && (float) $item->total_amount > 0) {
                $totalBudget = (float) $item->total_amount;
            }

            $totalRealized = (float) BosRealisasi::where('bos_kertas_kerja_item_id', $item->id)->sum('total_amount');
            $activePlanned = (float) BosRencanaBelanjaItem::where('bos_kertas_kerja_item_id', $item->id)
                ->whereHas('rencanaBelanja', function ($q) {
                    $q->whereNull('deleted_at')
                        ->where(function ($sq) {
                            $sq->whereNull('status')->orWhere('status', '!=', 'realized');
                        });
                })
                ->sum('total_amount');

            $remainingAmount = max(0, $totalBudget - ($totalRealized + $activePlanned));

            return [
                'id' => $item->id,
                'utility_profile_id' => $item->utility_profile_id,
                'account_code' => $item->account_code,
                'program_code' => $item->program_code,
                'kegiatan' => $item->kegiatan,
                'description' => $item->description,
                'month' => $item->month,
                'price' => (float) $item->price,
                'volume' => (float) $item->volume,
                'unit' => $item->unit,
                'total_budget' => $totalBudget,
                'remaining_amount' => $remainingAmount,
                'label' => "[{$item->month}] {$item->description} (Sisa: Rp ".number_format($remainingAmount, 0, ',', '.').')',
            ];
        });

        // 3. Daftar Guru untuk Reimburse
        $gurus = Guru::where('is_active', true)
            ->with('primaryBankAccount', 'bankAccounts')
            ->orderBy('name')
            ->get()
            ->map(function ($g) {
                $acc = $g->primaryBankAccount ?? $g->bankAccounts->first();

                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'bank_name' => $acc?->bank_name ?: 'Bank Jatim',
                    'account_number' => $acc?->account_number ?: '',
                    'account_holder' => $acc?->account_holder ?: $g->name,
                ];
            });

        // Cari default guru reimburse dari profil struk yang ada
        $defaultGuruId = null;
        $defaultBankName = 'Bank Jatim';
        $defaultAccountNumber = '';
        foreach ($receipts as $r) {
            if ($r->profile && $r->profile->default_reimburse_guru_id) {
                $defaultGuruId = $r->profile->default_reimburse_guru_id;
                $dg = $gurus->firstWhere('id', $defaultGuruId);
                if ($dg) {
                    $defaultBankName = $dg['bank_name'];
                    $defaultAccountNumber = $dg['account_number'];
                }
                break;
            }
        }

        // Format Struk
        $formattedReceipts = $receipts->map(function ($r) use ($kkFormatted) {
            $bestKkId = $r->bos_kertas_kerja_item_id;
            if (! $bestKkId && $r->utility_profile_id) {
                $matched = $kkFormatted->firstWhere('utility_profile_id', $r->utility_profile_id);
                $bestKkId = $matched['id'] ?? null;
            }

            return [
                'id' => $r->id,
                'utility_profile_id' => $r->utility_profile_id,
                'profile_name' => $r->profile?->name ?: ($r->customer_name ?: 'Tagihan Utilitas'),
                'category' => $r->profile?->category ?: 'listrik',
                'customer_name' => $r->customer_name ?: '-',
                'customer_number' => $r->customer_number ?: ($r->meter_number ?: '-'),
                'billing_month' => $r->billing_month ?: ($r->transaction_date ? $r->transaction_date->format('F') : '-'),
                'total_amount' => (float) $r->total_amount,
                'total_amount_display' => 'Rp '.number_format($r->total_amount, 0, ',', '.'),
                'token_number' => $r->token_number ?: null,
                'receipt_number' => $r->receipt_number ?: null,
                'transaction_date' => $r->transaction_date ? $r->transaction_date->format('d/m/Y') : '-',
                'receipt_url' => $r->receipt_url,
                'suggested_kk_id' => $bestKkId,
            ];
        });

        $noNota = BosRencanaBelanja::generateNoNota($year, $month);
        $defaultTitle = "Bayar Utilitas dan BBM {$month}";

        return response()->json([
            'year' => $year,
            'month' => $month,
            'no_nota' => $noNota,
            'title' => $defaultTitle,
            'receipts' => $formattedReceipts,
            'kertas_kerja_items' => $kkFormatted,
            'gurus' => $gurus,
            'default_guru_id' => $defaultGuruId,
            'default_bank_name' => $defaultBankName,
            'default_account_number' => $defaultAccountNumber,
        ]);
    }

    /**
     * Store Bundled Utility Payment into a single BosRencanaBelanja
     */
    public function storeUtilityPaymentGroup(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|string',
            'no_nota' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'guru_id' => 'required|exists:gurus,id',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'planned_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'receipts' => 'required|array|min:1',
            'receipts.*.id' => 'required|exists:utility_receipts,id',
            'receipts.*.bos_kertas_kerja_item_id' => 'required|exists:bos_kertas_kerja_items,id',
            'receipts.*.nominal' => 'required|numeric|min:1',
        ]);

        $guru = Guru::with('primaryBankAccount', 'bankAccounts')->findOrFail($request->guru_id);
        $acc = $guru->primaryBankAccount ?? $guru->bankAccounts->first();
        $bankName = $request->bank_name ?: ($acc?->bank_name ?? 'Bank Jatim');
        $accountNumber = $request->account_number ?: ($acc?->account_number ?? '');

        $noNota = $request->no_nota ?: BosRencanaBelanja::generateNoNota($request->year, $request->month);
        $title = $request->title ?: ("Bayar Utilitas dan BBM {$request->month}");

        DB::beginTransaction();
        try {
            $group = BosRencanaBelanja::create([
                'supplier_id' => null,
                'guru_id' => $guru->id,
                'no_nota' => $noNota,
                'year' => $request->year,
                'month' => $request->month,
                'title' => $title,
                'type' => 'utilitas',
                'supplier_name' => $guru->name,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'planned_date' => $request->planned_date ?: date('Y-m-d'),
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->receipts as $rData) {
                $receipt = UtilityReceipt::with('profile')->findOrFail($rData['id']);
                $kkItem = BosKertasKerjaItem::findOrFail($rData['bos_kertas_kerja_item_id']);
                $nominal = (float) $rData['nominal'];

                // Update receipt linkage
                $receipt->update([
                    'bos_rencana_belanja_id' => $group->id,
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                ]);

                $profileName = $receipt->profile?->name ?: ($receipt->customer_name ?: 'Tagihan Utilitas');
                $custNum = $receipt->customer_number ? " ({$receipt->customer_number})" : '';

                BosRencanaBelanjaItem::create([
                    'bos_rencana_belanja_id' => $group->id,
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 1,
                    'unit' => $kkItem->unit ?: 'Bulan',
                    'price' => $nominal,
                    'total_amount' => $nominal,
                    'spesifikasi' => "Reimburse {$profileName}{$custNum} - {$receipt->billing_month}",
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Paket Rencana Belanja Utilitas {$noNota} berhasil dibuat.",
                'group_id' => $group->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuat rencana utilitas: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete Group (Draft or Realized)
     */
    public function destroyGroup($id)
    {
        $group = BosRencanaBelanja::findOrFail($id);

        if ($group->status === 'realized' && $group->realisasis()->exists()) {
            return response()->json(['message' => 'Rencana belanja yang sudah berstatus Realisasi tidak dapat dihapus. Hapus terlebih dahulu transaksi realisasi terkait sebelum menghapus rencana belanja.'], 422);
        }

        if ($group->status === 'diajukan_ke_bank' || $group->pencairan_bank_item_id) {
            return response()->json(['message' => 'Rencana belanja ini sedang diajukan dalam Surat Pencairan Bank (TNT). Hapus terlebih dahulu dari Surat TNT sebelum menghapus rencana belanja.'], 422);
        }

        // Release associated utility receipts if any
        if ($group->utilityReceipts()->exists()) {
            $group->utilityReceipts()->update(['bos_rencana_belanja_id' => null]);
        }

        // Clean up soft-deleted realisasi records if any
        $group->realisasis()->withTrashed()->forceDelete();

        $group->items()->delete();
        $group->delete();

        return response()->json(['message' => 'Rencana belanja berhasil dihapus.']);
    }
}

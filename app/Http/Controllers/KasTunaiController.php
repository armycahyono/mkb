<?php

namespace App\Http\Controllers;

use App\Helpers\FiscalYearHelper;
use App\Models\BosRencanaBelanja;
use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\KasTunai;
use App\Models\KasTunaiItem;
use App\Models\PencairanBank;
use App\Models\PerjadinItem;
use App\Models\SchoolProfile;
use App\Services\ReceiptOcrService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class KasTunaiController extends Controller
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

    /**
     * @return array{saldo_kas: float, saldo_kas_formatted: string, saldo_awal: float, saldo_awal_formatted: string, total_masuk: float, total_masuk_formatted: string, total_keluar: float, total_keluar_formatted: string, total_panjar_pending: float, total_panjar_pending_formatted: string, count_panjar_pending: int, kas_siap_pakai: float, kas_siap_pakai_formatted: string}
     */
    private function calculateSummaryStats(string $fundingSource, string $selectedYear): array
    {
        if ($selectedYear !== 'all') {
            $saldoAwalMasuk = (float) KasTunai::funding($fundingSource)
                ->whereDate('transaction_date', '<', $selectedYear.'-01-01')
                ->where('transaction_type', 'masuk')
                ->sum('amount');
            $saldoAwalKeluar = (float) KasTunai::funding($fundingSource)
                ->whereDate('transaction_date', '<', $selectedYear.'-01-01')
                ->actualCashOutflow()
                ->sum('amount');
            $saldoAwal = $saldoAwalMasuk - $saldoAwalKeluar;

            $totalMasuk = (float) KasTunai::funding($fundingSource)
                ->whereYear('transaction_date', $selectedYear)
                ->where('transaction_type', 'masuk')
                ->sum('amount');
            $totalKeluar = (float) KasTunai::funding($fundingSource)
                ->whereYear('transaction_date', $selectedYear)
                ->actualCashOutflow()
                ->sum('amount');

            $saldoKas = $saldoAwal + $totalMasuk - $totalKeluar;

            $pendingQuery = KasTunai::funding($fundingSource)
                ->pendingPanjar()
                ->whereDate('transaction_date', '<=', $selectedYear.'-12-31')
                ->with('childKass');
        } else {
            $saldoAwal = 0;
            $totalMasuk = (float) KasTunai::funding($fundingSource)->where('transaction_type', 'masuk')->sum('amount');
            $totalKeluar = (float) KasTunai::funding($fundingSource)->actualCashOutflow()->sum('amount');
            $saldoKas = $totalMasuk - $totalKeluar;

            $pendingQuery = KasTunai::funding($fundingSource)->pendingPanjar()->with('childKass');
        }

        $pendingPanjarsList = (clone $pendingQuery)->get();
        $totalPanjarPending = (float) $pendingPanjarsList->sum(fn ($p) => $p->remaining_panjar_amount);
        $countPanjarPending = $pendingPanjarsList->count();
        $kasSiapPakai = $saldoKas - $totalPanjarPending;

        return [
            'saldo_kas' => $saldoKas,
            'saldo_kas_formatted' => 'Rp '.number_format($saldoKas, 0, ',', '.'),
            'saldo_awal' => $saldoAwal,
            'saldo_awal_formatted' => 'Rp '.number_format($saldoAwal, 0, ',', '.'),
            'total_masuk' => $totalMasuk,
            'total_masuk_formatted' => '+ '.number_format($totalMasuk, 0, ',', '.'),
            'total_keluar' => $totalKeluar,
            'total_keluar_formatted' => '- '.number_format($totalKeluar, 0, ',', '.'),
            'total_panjar_pending' => $totalPanjarPending,
            'total_panjar_pending_formatted' => 'Rp '.number_format($totalPanjarPending, 0, ',', '.'),
            'count_panjar_pending' => $countPanjarPending,
            'kas_siap_pakai' => $kasSiapPakai,
            'kas_siap_pakai_formatted' => 'Rp '.number_format($kasSiapPakai, 0, ',', '.'),
        ];
    }

    public function index(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);

        if ($request->ajax()) {
            $query = KasTunai::query()
                ->select('kas_tunais.*')
                ->with(['kasCategory', 'guru', 'parentKas', 'rencanaBelanja', 'pencairanBank', 'perjadinItem.guru', 'perjadinItem.perjadin'])
                ->withCount('items')
                ->funding($fundingSource);

            if ($request->filled('category')) {
                $query->where('kas_tunais.category', $request->category);
            }

            if ($request->filled('month')) {
                $query->whereMonth('kas_tunais.transaction_date', $request->month);
            }

            if ($request->filled('panjar_status') && $request->panjar_status === 'pending') {
                $query->where('kas_tunais.panjar_status', 'pending');
                $filterYear = $request->get('year', session('fiscal_year', date('Y')));
                if ($filterYear && $filterYear !== 'all') {
                    $query->whereDate('kas_tunais.transaction_date', '<=', $filterYear.'-12-31');
                }
            } else {
                if ($request->filled('year')) {
                    if ($request->year !== 'all') {
                        $query->whereYear('kas_tunais.transaction_date', $request->year);
                    }
                } else {
                    $activeFiscalYear = session('fiscal_year', date('Y'));
                    if ($activeFiscalYear && $activeFiscalYear !== 'all') {
                        $query->whereYear('kas_tunais.transaction_date', $activeFiscalYear);
                    }
                }

                if ($request->filled('panjar_status')) {
                    $query->where('kas_tunais.panjar_status', $request->panjar_status);
                }
            }

            if (! $request->has('order') || empty($request->input('order'))) {
                $query->orderBy('kas_tunais.transaction_date', 'desc')->orderBy('kas_tunais.id', 'desc');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('id_display', function ($row) {
                    return '<span class="badge badge-light border text-monospace font-weight-bold">'.$row->id_display.'</span>';
                })
                ->orderColumn('id_display', function ($query, $order) {
                    $query->orderBy('kas_tunais.year', $order)->orderBy('kas_tunais.sequence', $order);
                })
                ->orderColumn('id', function ($query, $order) {
                    $query->orderBy('kas_tunais.id', $order);
                })
                ->editColumn('transaction_date', function ($row) {
                    return $row->transaction_date ? $row->transaction_date->format('d/m/Y') : '-';
                })
                ->orderColumn('transaction_date', function ($query, $order) {
                    $query->orderBy('kas_tunais.transaction_date', $order)->orderBy('kas_tunais.id', $order);
                })
                ->editColumn('category', function ($row) {
                    return $row->category_label;
                })
                ->orderColumn('category', function ($query, $order) {
                    $query->orderBy('kas_tunais.category', $order);
                })
                ->editColumn('description', function ($row) {
                    $html = '<div>'.htmlspecialchars((string) $row->description).'</div>';
                    $meta = [];
                    if ($row->parent_kas_id) {
                        $parentDisplay = $row->parentKas ? $row->parentKas->id_display : ('KT'.str_pad((string) $row->parent_kas_id, 4, '0', STR_PAD_LEFT));
                        $meta[] = '<span class="badge badge-light border text-info" title="Turunan dari Kasbon '.$parentDisplay.'"><i class="fas fa-link mr-1"></i> Ref: '.$parentDisplay.'</span>';
                    }
                    if ($row->pencairanBank) {
                        $meta[] = '<span class="badge badge-light border text-success"><i class="fas fa-file-invoice-dollar mr-1"></i> TNT: '.htmlspecialchars((string) $row->pencairanBank->letter_number).'</span>';
                    }
                    if ($row->rencanaBelanja) {
                        $meta[] = '<span class="badge badge-light border text-warning"><i class="fas fa-shopping-basket mr-1"></i> RB: '.htmlspecialchars((string) $row->rencanaBelanja->no_nota).'</span>';
                    }
                    if ($row->perjadinItem) {
                        $receiptNo = $row->perjadinItem->receipt_number ?: ('Kwt #'.$row->perjadinItem->id);
                        $meta[] = '<span class="badge badge-light border text-primary"><i class="fas fa-car-side mr-1"></i> Perjadin: '.htmlspecialchars((string) $receiptNo).'</span>';
                    }
                    if ($row->evidence_number) {
                        $meta[] = '<span class="badge badge-light border text-secondary"><i class="fas fa-receipt mr-1"></i> Bukti: '.htmlspecialchars((string) $row->evidence_number).'</span>';
                    }
                    if ($row->items_count > 0) {
                        $meta[] = '<a href="javascript:void(0)" class="badge badge-info border text-white btn-view-receipt-items" data-id="'.$row->id.'" title="Lihat Rincian Barang"><i class="fas fa-receipt mr-1"></i> '.$row->items_count.' Barang</a>';
                    }
                    if ($row->category === 'panjar_kerja' && $row->panjar_status === 'partial') {
                        $meta[] = '<span class="badge badge-warning border text-dark font-weight-bold" title="Kasbon Belum Lunas"><i class="fas fa-hourglass-half mr-1"></i> Sisa: Rp '.number_format($row->remaining_panjar_amount, 0, ',', '.').'</span>';
                    }
                    if (! empty($meta)) {
                        $html .= '<div class="mt-1" style="display:flex; flex-wrap:wrap; gap:4px;">'.implode(' ', $meta).'</div>';
                    }

                    return $html;
                })
                ->orderColumn('description', function ($query, $order) {
                    $query->orderBy('kas_tunais.description', $order);
                })
                ->addColumn('debit', function ($row) {
                    return $row->transaction_type === 'masuk' ? (float) $row->amount : 0;
                })
                ->filterColumn('debit', function ($query, $keyword) {
                    $clean = preg_replace('/[^0-9]/', '', (string) $keyword);
                    if ($clean !== '') {
                        $query->where(function ($q) use ($clean) {
                            $q->where('kas_tunais.transaction_type', 'masuk')
                                ->where('kas_tunais.amount', 'like', "%{$clean}%");
                        });
                    }
                })
                ->orderColumn('debit', function ($query, $order) {
                    $query->orderByRaw("CASE WHEN kas_tunais.transaction_type = 'masuk' THEN kas_tunais.amount ELSE 0 END {$order}");
                })
                ->addColumn('kredit', function ($row) {
                    return $row->transaction_type === 'keluar' ? (float) $row->amount : 0;
                })
                ->filterColumn('kredit', function ($query, $keyword) {
                    $clean = preg_replace('/[^0-9]/', '', (string) $keyword);
                    if ($clean !== '') {
                        $query->where(function ($q) use ($clean) {
                            $q->where('kas_tunais.transaction_type', 'keluar')
                                ->where('kas_tunais.amount', 'like', "%{$clean}%");
                        });
                    }
                })
                ->orderColumn('kredit', function ($query, $order) {
                    $query->orderByRaw("CASE WHEN kas_tunais.transaction_type = 'keluar' THEN kas_tunais.amount ELSE 0 END {$order}");
                })
                ->addColumn('person_display', function ($row) {
                    $guruName = $row->guru ? htmlspecialchars($row->guru->name) : null;
                    $storeName = $row->person_name ? htmlspecialchars($row->person_name) : null;

                    if ($guruName && $storeName) {
                        return '<div class="font-weight-bold text-dark"><i class="fas fa-user-check text-primary mr-1"></i>'.$guruName.'</div>'
                            .'<div class="small text-muted mt-1"><i class="fas fa-store text-secondary mr-1"></i>'.$storeName.'</div>';
                    } elseif ($guruName) {
                        return '<div class="font-weight-bold text-dark"><i class="fas fa-user-check text-primary mr-1"></i>'.$guruName.'</div>';
                    } elseif ($storeName) {
                        return '<div class="text-dark"><i class="fas fa-store text-secondary mr-1"></i>'.$storeName.'</div>';
                    }

                    return '-';
                })
                ->filterColumn('person_display', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('kas_tunais.person_name', 'like', "%{$keyword}%")
                            ->orWhereHas('guru', function ($gq) use ($keyword) {
                                $gq->where('name', 'like', "%{$keyword}%");
                            });
                    });
                })
                ->orderColumn('person_display', function ($query, $order) {
                    $query->leftJoin('gurus as g_sort', 'kas_tunais.guru_id', '=', 'g_sort.id')
                        ->orderByRaw("COALESCE(g_sort.name, kas_tunais.person_name) {$order}");
                })
                ->addColumn('action', function ($row) use ($routePrefix) {
                    $btn = '<div class="d-flex justify-content-end align-items-center"><div class="btn-group btn-group-sm" role="group">';
                    if ($row->category === 'panjar_kerja' && in_array($row->panjar_status, ['pending', 'partial'], true)) {
                        $btnText = ($row->panjar_status === 'partial') ? 'SPJ Lanjutan' : 'Selesai SPJ';
                        $btnClass = ($row->panjar_status === 'partial') ? 'btn-info' : 'btn-warning';
                        $btn .= '<button type="button" class="btn '.$btnClass.' btn-sm btn-settle-panjar" data-id="'.$row->id.'" data-amount="'.$row->amount.'" data-remaining="'.$row->remaining_panjar_amount.'" data-settled="'.$row->settled_panjar_amount.'" data-status="'.$row->panjar_status.'" data-desc="'.htmlspecialchars((string) $row->description).'" data-person="'.htmlspecialchars((string) ($row->guru ? $row->guru->name : ($row->person_name ?: ''))).'" data-rb-id="'.$row->bos_rencana_belanja_id.'" data-pi-id="'.$row->perjadin_item_id.'" title="Selesaikan Kasbon / SPJ"><i class="fas fa-check-circle"></i> '.$btnText.'</button>';
                    }
                    if ($row->receipt_file) {
                        $receiptUrl = asset('storage/'.$row->receipt_file);
                        $ext = strtolower(pathinfo($row->receipt_file, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                        $title = 'Bukti Transaksi '.$row->id_display;
                        $desc = htmlspecialchars((string) ($row->description ?: ''));
                        $person = htmlspecialchars((string) ($row->guru ? $row->guru->name : ($row->person_name ?: '')));
                        $evidence = htmlspecialchars((string) ($row->evidence_number ?: ''));
                        $date = $row->transaction_date ? $row->transaction_date->format('d/m/Y') : '-';

                        $btn .= '<button type="button" class="btn btn-secondary btn-sm btn-preview-receipt" '
                            .'data-url="'.$receiptUrl.'" '
                            .'data-is-image="'.($isImage ? '1' : '0').'" '
                            .'data-title="'.$title.'" '
                            .'data-desc="'.$desc.'" '
                            .'data-person="'.$person.'" '
                            .'data-evidence="'.$evidence.'" '
                            .'data-date="'.$date.'" '
                            .'title="Lihat Bukti Foto / Nota"><i class="fas fa-file-invoice"></i></button>';
                    }
                    $btn .= '<button type="button" class="btn btn-primary btn-sm btn-edit-kas" data-id="'.$row->id.'" title="Edit Transaksi"><i class="fas fa-edit"></i></button>';
                    $btn .= '<button type="button" class="btn btn-danger btn-sm btn-delete-kas" data-id="'.$row->id.'" data-url="'.route($routePrefix.'.kas-tunai.destroy', $row->id, false).'" title="Hapus"><i class="fas fa-trash"></i></button>';
                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['id_display', 'description', 'person_display', 'action'])
                ->with('summary', $this->calculateSummaryStats($fundingSource, (string) $request->get('year', session('fiscal_year', date('Y')))))
                ->make(true);
        }

        $selectedYear = (string) $request->get('year', session('fiscal_year', date('Y')));
        $summary = $this->calculateSummaryStats($fundingSource, $selectedYear);

        $saldoAwal = $summary['saldo_awal'];
        $totalMasuk = $summary['total_masuk'];
        $totalKeluar = $summary['total_keluar'];
        $saldoKas = $summary['saldo_kas'];
        $totalPanjarPending = $summary['total_panjar_pending'];
        $countPanjarPending = $summary['count_panjar_pending'];
        $kasSiapPakai = $summary['kas_siap_pakai'];

        $gurus = Guru::where('is_active', true)->orderBy('name')->get();
        $categories = KasTunai::categories();

        $rbQuery = BosRencanaBelanja::where('status', '!=', 'cancelled');
        if ($selectedYear !== 'all') {
            $rbQuery->where(function ($q) use ($selectedYear) {
                $q->where('year', $selectedYear)
                    ->orWhereYear('planned_date', $selectedYear)
                    ->orWhereYear('created_at', $selectedYear);
            });
        }
        $rencanaBelanjas = $rbQuery->latest('id')->limit(60)->get();

        $pbQuery = PencairanBank::funding($fundingSource);
        if ($selectedYear !== 'all') {
            $pbQuery->whereYear('letter_date', $selectedYear);
        }
        $pencairanBanks = $pbQuery->latest('letter_date')->limit(30)->get();

        $piQuery = PerjadinItem::whereHas('perjadin', function ($q) use ($fundingSource, $selectedYear) {
            $q->where('funding_source', $fundingSource);
            if ($selectedYear !== 'all') {
                $q->where(function ($sub) use ($selectedYear) {
                    $sub->whereYear('departure_date', $selectedYear)
                        ->orWhereYear('payment_date', $selectedYear)
                        ->orWhereYear('created_at', $selectedYear);
                });
            }
        })->with(['perjadin', 'guru']);
        $perjadinItems = $piQuery->latest('id')->limit(60)->get();
        $availableFiscalYears = FiscalYearHelper::getAvailableYears();

        $pendingPanjars = KasTunai::where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->funding($fundingSource)
            ->with(['guru', 'childKass'])
            ->latest('transaction_date')
            ->get();

        $danaTalangans = KasTunai::where('category', 'dana_talangan_masuk')
            ->where('transaction_type', 'masuk')
            ->funding($fundingSource)
            ->with('guru')
            ->latest('transaction_date')
            ->get();

        return view('kas_tunai.index', compact(
            'fundingSource',
            'routePrefix',
            'selectedYear',
            'saldoAwal',
            'totalMasuk',
            'totalKeluar',
            'saldoKas',
            'totalPanjarPending',
            'countPanjarPending',
            'kasSiapPakai',
            'gurus',
            'categories',
            'rencanaBelanjas',
            'pencairanBanks',
            'perjadinItems',
            'availableFiscalYears',
            'pendingPanjars',
            'danaTalangans'
        ));
    }

    public function getAvailableDocuments(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        if ($request->has('year')) {
            $year = $request->year !== 'all' ? (int) $request->year : null;
        } else {
            $activeFiscalYear = session('fiscal_year', date('Y'));
            $year = $activeFiscalYear !== 'all' ? (int) $activeFiscalYear : null;
        }
        $includeSettled = $request->boolean('include_settled', false);

        // 1. TNT Bank
        $pencairanQuery = PencairanBank::funding($fundingSource);
        if ($year) {
            $pencairanQuery->whereYear('letter_date', $year);
        }
        $pencairanBanks = $pencairanQuery->latest('letter_date')->get()->map(function ($pb) {
            return [
                'id' => $pb->id,
                'letter_number' => $pb->letter_number,
                'total_nominal' => (float) $pb->total_nominal,
                'label' => $pb->letter_number.' (Rp '.number_format($pb->total_nominal, 0, ',', '.').($pb->letter_date ? ' - '.$pb->letter_date->format('d/m/Y') : '').')',
            ];
        });

        // 2. Rencana Belanja (khusus BOSP)
        $rencanaBelanjas = collect();
        if ($fundingSource === 'BOSP') {
            $rbQuery = BosRencanaBelanja::with(['items', 'supplier'])
                ->where('status', '!=', 'cancelled');

            if ($year) {
                $rbQuery->where('year', $year);
            }

            if (! $includeSettled) {
                $settledRbIds = KasTunai::where('funding_source', 'BOSP')
                    ->whereNotNull('bos_rencana_belanja_id')
                    ->where('transaction_type', 'keluar')
                    ->where('panjar_status', '!=', 'pending')
                    ->pluck('bos_rencana_belanja_id')
                    ->toArray();

                $rbQuery->whereNotIn('id', $settledRbIds);
            }

            $rencanaBelanjas = $rbQuery->latest('id')->get()->map(function ($rb) {
                $total = (float) $rb->total_amount;
                $supplier = $rb->supplier_name ?: ($rb->supplier?->name ?? '');

                return [
                    'id' => $rb->id,
                    'no_nota' => $rb->no_nota,
                    'title' => $rb->title,
                    'supplier_name' => $supplier,
                    'total_amount' => $total,
                    'label' => $rb->no_nota.' - '.($supplier ?: $rb->title).' (Rp '.number_format($total, 0, ',', '.').')',
                ];
            });
        }

        // 3. Kwitansi Perjadin
        $piQuery = PerjadinItem::whereHas('perjadin', function ($q) use ($fundingSource, $year) {
            $q->where('funding_source', $fundingSource);
            if ($year) {
                $q->where(function ($sub) use ($year) {
                    $sub->whereYear('departure_date', $year)
                        ->orWhereYear('payment_date', $year)
                        ->orWhereYear('created_at', $year);
                });
            }
        })->with(['perjadin', 'guru']);

        if (! $includeSettled) {
            $settledPiIds = KasTunai::where('funding_source', $fundingSource)
                ->whereNotNull('perjadin_item_id')
                ->where('transaction_type', 'keluar')
                ->where('panjar_status', '!=', 'pending')
                ->pluck('perjadin_item_id')
                ->toArray();

            $piQuery->where('is_paid', false)
                ->whereNotIn('id', $settledPiIds);
        }

        $perjadinItems = $piQuery->latest('id')->get()->map(function ($pi) {
            $guruName = $pi->guru ? $pi->guru->name : 'Pegawai';
            $receipt = $pi->receipt_number ?: ('KWT-'.$pi->id);
            $purpose = $pi->perjadin?->activity_purpose ?? 'Perjadin';

            return [
                'id' => $pi->id,
                'receipt_number' => $receipt,
                'guru_id' => $pi->guru_id,
                'guru_name' => $guruName,
                'net_amount' => (float) $pi->net_amount,
                'activity_purpose' => $purpose,
                'label' => $receipt.' - '.$guruName.' (Rp '.number_format($pi->net_amount, 0, ',', '.').')',
            ];
        });

        // 4. Kasbon Pending (untuk pilihan kembalian sisa_panjar)
        $pendingPanjars = KasTunai::funding($fundingSource)
            ->pendingPanjar()
            ->with('guru')
            ->latest('transaction_date')
            ->get()
            ->map(function ($pp) {
                $guruName = $pp->guru?->name ?? $pp->person_name;

                return [
                    'id' => $pp->id,
                    'guru_id' => $pp->guru_id,
                    'guru_name' => $guruName,
                    'amount' => (float) $pp->amount,
                    'remaining_amount' => (float) $pp->remaining_panjar_amount,
                    'description' => $pp->description,
                    'label' => $pp->id_display.' - '.$guruName.' (Kasbon: Rp '.number_format($pp->amount, 0, ',', '.').' - '.$pp->description.')',
                ];
            });

        return response()->json([
            'year' => $year,
            'include_settled' => $includeSettled,
            'pencairan_banks' => $pencairanBanks,
            'rencana_belanjas' => $rencanaBelanjas,
            'perjadin_items' => $perjadinItems,
            'pending_panjars' => $pendingPanjars,
        ]);
    }

    public function store(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($request->has('amount')) {
            $request->merge([
                'amount' => $this->parseCurrencyAmount($request->input('amount')),
            ]);
        }

        if ($request->has('items')) {
            $rawItems = $request->input('items');
            if (is_string($rawItems)) {
                $rawItems = json_decode($rawItems, true) ?: [];
            }
            if (is_array($rawItems)) {
                $cleanedItems = [];
                foreach ($rawItems as $item) {
                    if (empty($item['item_name'])) {
                        continue;
                    }
                    $qty = isset($item['quantity']) ? (float) str_replace(',', '.', (string) $item['quantity']) : 1;
                    $price = isset($item['price']) ? $this->parseCurrencyAmount($item['price']) : 0;
                    $total = isset($item['total_price']) ? $this->parseCurrencyAmount($item['total_price']) : ($qty * $price);
                    if ($total <= 0 && $price > 0) {
                        $total = $qty * $price;
                    } elseif ($price <= 0 && $total > 0 && $qty > 0) {
                        $price = $total / $qty;
                    }
                    $cleanedItems[] = [
                        'item_name' => trim((string) ($item['item_name'] ?? '')),
                        'quantity' => $qty > 0 ? $qty : 1,
                        'unit' => trim((string) ($item['unit'] ?? '')),
                        'price' => $price,
                        'total_price' => $total,
                        'notes' => trim((string) ($item['notes'] ?? '')),
                    ];
                }
                $request->merge(['items' => $cleanedItems]);
            }
        }

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:masuk,keluar',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'person_name' => 'nullable|string|max:255',
            'guru_id' => 'required|exists:gurus,id',
            'parent_kas_id' => 'nullable|exists:kas_tunais,id',
            'pencairan_bank_id' => 'nullable|exists:pencairan_banks,id',
            'bos_rencana_belanja_id' => 'nullable|exists:bos_rencana_belanjas,id',
            'perjadin_item_id' => 'nullable|exists:perjadin_items,id',
            'evidence_number' => 'nullable|string|max:100',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.total_price' => 'required_with:items|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'guru_id.required' => 'Penanggung jawab / guru yang bertransaksi wajib dipilih.',
            'guru_id.exists' => 'Guru / penanggung jawab yang dipilih tidak valid.',
            'receipt_file.max' => 'Ukuran file nota maksimal 20 MB.',
            'receipt_file.mimes' => 'Format file nota harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        // 1. Validasi Keunikan Nomor Bukti Transaksi (Cegah Double Claim / Pencatatan Ganda)
        $evidenceNo = trim((string) ($validated['evidence_number'] ?? ''));
        if ($evidenceNo !== '') {
            $transactionYear = date('Y', strtotime($validated['transaction_date']));
            $existingEvidence = KasTunai::funding($fundingSource)
                ->whereYear('transaction_date', $transactionYear)
                ->where('evidence_number', $evidenceNo)
                ->first();

            if ($existingEvidence) {
                return response()->json([
                    'success' => false,
                    'message' => "Nomor bukti transaksi '{$evidenceNo}' sudah pernah digunakan pada transaksi {$existingEvidence->id_display} ({$existingEvidence->description}). Nomor bukti tidak boleh duplikat untuk mencegah pencatatan ganda.",
                ], 422);
            }
        }

        // 2. Validasi Pencegahan Saldo Kas Negatif (Negative Cash Prevention)
        if ($validated['transaction_type'] === 'keluar' && (empty($validated['parent_kas_id']) || in_array($validated['category'], ['reimburse', 'bayar_talangan'], true))) {
            $currentBalance = KasTunai::getAvailableCashBalance($fundingSource);
            $requestedAmount = (float) $validated['amount'];

            if ($requestedAmount > $currentBalance && ! $request->boolean('allow_negative_balance', false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengeluaran kas sebesar Rp '.number_format($requestedAmount, 0, ',', '.').' melampaui sisa saldo kas tunai yang tersedia (Rp '.number_format($currentBalance, 0, ',', '.').'). Saldo kas tidak boleh bernilai negatif.',
                ], 422);
            }
        }

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $request->file('receipt_file')->store('kas_tunai', 'public');
        }

        $panjarStatus = ($validated['category'] === 'panjar_kerja') ? 'pending' : 'none';

        DB::transaction(function () use ($fundingSource, $validated, $receiptPath, $panjarStatus) {
            $kas = KasTunai::create([
                'funding_source' => $fundingSource,
                'transaction_date' => $validated['transaction_date'],
                'transaction_type' => $validated['transaction_type'],
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'person_name' => $validated['person_name'] ?? null,
                'guru_id' => $validated['guru_id'] ?? null,
                'parent_kas_id' => $validated['parent_kas_id'] ?? null,
                'pencairan_bank_id' => $validated['pencairan_bank_id'] ?? null,
                'bos_rencana_belanja_id' => $validated['bos_rencana_belanja_id'] ?? null,
                'perjadin_item_id' => $validated['perjadin_item_id'] ?? null,
                'panjar_status' => $panjarStatus,
                'evidence_number' => $validated['evidence_number'] ?? null,
                'receipt_file' => $receiptPath,
                'created_by' => Auth::id(),
            ]);

            if ($validated['category'] === 'sisa_panjar' && ! empty($validated['parent_kas_id'])) {
                KasTunai::where('id', $validated['parent_kas_id'])->update(['panjar_status' => 'settled']);
            }

            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $kas->items()->create($item);
                }
            }

            // Jika kas keluar langsung untuk kwitansi perjadin, tandai perjadin item sebagai lunas
            if ($validated['transaction_type'] === 'keluar' && ! empty($validated['perjadin_item_id']) && $panjarStatus !== 'pending') {
                PerjadinItem::where('id', $validated['perjadin_item_id'])->update(['is_paid' => true]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kas tunai berhasil dicatat.',
        ]);
    }

    public function edit(Request $request, KasTunai $kasTunai)
    {
        $kasTunai->load(['guru', 'items', 'pencairanBank', 'rencanaBelanja', 'perjadinItem', 'parentKas']);

        return response()->json([
            'id' => $kasTunai->id,
            'funding_source' => $kasTunai->funding_source,
            'transaction_date' => $kasTunai->transaction_date ? $kasTunai->transaction_date->format('Y-m-d') : '',
            'transaction_type' => $kasTunai->transaction_type,
            'category' => $kasTunai->category,
            'amount' => (float) $kasTunai->amount,
            'description' => $kasTunai->description,
            'person_name' => $kasTunai->person_name,
            'guru_id' => $kasTunai->guru_id,
            'parent_kas_id' => $kasTunai->parent_kas_id,
            'parent_kas' => $kasTunai->parentKas ? [
                'id' => $kasTunai->parentKas->id,
                'description' => $kasTunai->parentKas->description,
            ] : null,
            'pencairan_bank_id' => $kasTunai->pencairan_bank_id,
            'bos_rencana_belanja_id' => $kasTunai->bos_rencana_belanja_id,
            'perjadin_item_id' => $kasTunai->perjadin_item_id,
            'evidence_number' => $kasTunai->evidence_number,
            'panjar_status' => $kasTunai->panjar_status,
            'receipt_file' => $kasTunai->receipt_file,
            'receipt_url' => $kasTunai->receipt_file ? asset('storage/'.$kasTunai->receipt_file) : null,
            'items' => $kasTunai->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit ?: '',
                    'price' => (float) $item->price,
                    'total_price' => (float) $item->total_price,
                    'notes' => $item->notes ?: '',
                ];
            }),
        ]);
    }

    public function update(Request $request, KasTunai $kasTunai)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($request->has('amount')) {
            $request->merge([
                'amount' => $this->parseCurrencyAmount($request->input('amount')),
            ]);
        }

        if ($request->has('items')) {
            $rawItems = $request->input('items');
            if (is_string($rawItems)) {
                $rawItems = json_decode($rawItems, true) ?: [];
            }
            if (is_array($rawItems)) {
                $cleanedItems = [];
                foreach ($rawItems as $item) {
                    if (empty($item['item_name'])) {
                        continue;
                    }
                    $qty = isset($item['quantity']) ? (float) str_replace(',', '.', (string) $item['quantity']) : 1;
                    $price = isset($item['price']) ? $this->parseCurrencyAmount($item['price']) : 0;
                    $total = isset($item['total_price']) ? $this->parseCurrencyAmount($item['total_price']) : ($qty * $price);
                    if ($total <= 0 && $price > 0) {
                        $total = $qty * $price;
                    } elseif ($price <= 0 && $total > 0 && $qty > 0) {
                        $price = $total / $qty;
                    }
                    $cleanedItems[] = [
                        'item_name' => trim((string) ($item['item_name'] ?? '')),
                        'quantity' => $qty > 0 ? $qty : 1,
                        'unit' => trim((string) ($item['unit'] ?? '')),
                        'price' => $price,
                        'total_price' => $total,
                        'notes' => trim((string) ($item['notes'] ?? '')),
                    ];
                }
                $request->merge(['items' => $cleanedItems]);
            }
        }

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:masuk,keluar',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'person_name' => 'nullable|string|max:255',
            'guru_id' => 'required|exists:gurus,id',
            'parent_kas_id' => 'nullable|exists:kas_tunais,id',
            'pencairan_bank_id' => 'nullable|exists:pencairan_banks,id',
            'bos_rencana_belanja_id' => 'nullable|exists:bos_rencana_belanjas,id',
            'perjadin_item_id' => 'nullable|exists:perjadin_items,id',
            'evidence_number' => 'nullable|string|max:100',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.total_price' => 'required_with:items|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'guru_id.required' => 'Penanggung jawab / guru yang bertransaksi wajib dipilih.',
            'guru_id.exists' => 'Guru / penanggung jawab yang dipilih tidak valid.',
            'receipt_file.max' => 'Ukuran file nota maksimal 20 MB.',
            'receipt_file.mimes' => 'Format file nota harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        // 1. Validasi Keunikan Nomor Bukti Transaksi (Cegah Double Claim)
        $evidenceNo = trim((string) ($validated['evidence_number'] ?? ''));
        if ($evidenceNo !== '' && ! in_array($evidenceNo, ['-', '0', 'none', 'null'], true)) {
            $transactionYear = date('Y', strtotime($validated['transaction_date']));
            $existingEvidence = KasTunai::funding($fundingSource)
                ->where('id', '!=', $kasTunai->id)
                ->whereYear('transaction_date', $transactionYear)
                ->where('evidence_number', $evidenceNo)
                ->first();

            if ($existingEvidence) {
                return response()->json([
                    'success' => false,
                    'message' => "Nomor bukti transaksi '{$evidenceNo}' sudah pernah digunakan pada transaksi {$existingEvidence->id_display} ({$existingEvidence->description}). Nomor bukti tidak boleh duplikat untuk mencegah pencatatan ganda.",
                ], 422);
            }
        }

        // 2. Validasi Pencegahan Saldo Kas Negatif
        if ($validated['transaction_type'] === 'keluar' && (empty($validated['parent_kas_id']) || $validated['category'] === 'reimburse')) {
            $currentBalance = KasTunai::getAvailableCashBalance($fundingSource, null, $kasTunai->id);
            $requestedAmount = (float) $validated['amount'];

            if ($requestedAmount > $currentBalance && ! $request->boolean('allow_negative_balance', false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengeluaran kas sebesar Rp '.number_format($requestedAmount, 0, ',', '.').' melampaui sisa saldo kas tunai yang tersedia (Rp '.number_format($currentBalance, 0, ',', '.').'). Saldo kas tidak boleh bernilai negatif.',
                ], 422);
            }
        }

        $receiptPath = $kasTunai->receipt_file;
        if ($request->hasFile('receipt_file')) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt_file')->store('kas_tunai', 'public');
        }

        $panjarStatus = $kasTunai->panjar_status;
        if ($validated['category'] === 'panjar_kerja' && $panjarStatus === 'none') {
            $panjarStatus = 'pending';
        } elseif ($validated['category'] !== 'panjar_kerja') {
            $panjarStatus = 'none';
        }

        DB::transaction(function () use ($kasTunai, $validated, $receiptPath, $panjarStatus) {
            $kasTunai->update([
                'transaction_date' => $validated['transaction_date'],
                'transaction_type' => $validated['transaction_type'],
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'person_name' => $validated['person_name'] ?? null,
                'guru_id' => $validated['guru_id'] ?? null,
                'parent_kas_id' => $validated['parent_kas_id'] ?? null,
                'pencairan_bank_id' => $validated['pencairan_bank_id'] ?? null,
                'bos_rencana_belanja_id' => $validated['bos_rencana_belanja_id'] ?? null,
                'perjadin_item_id' => $validated['perjadin_item_id'] ?? null,
                'panjar_status' => $panjarStatus,
                'evidence_number' => $validated['evidence_number'] ?? null,
                'receipt_file' => $receiptPath,
                'updated_by' => Auth::id(),
            ]);

            // Sinkronisasi item barang: Hapus item lama dan buat baru jika ada
            $kasTunai->items()->delete();
            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $kasTunai->items()->create($item);
                }
            }

            // Jika kas keluar langsung untuk kwitansi perjadin, tandai perjadin item sebagai lunas
            if ($validated['transaction_type'] === 'keluar' && ! empty($validated['perjadin_item_id']) && $panjarStatus !== 'pending') {
                PerjadinItem::where('id', $validated['perjadin_item_id'])->update(['is_paid' => true]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kas tunai berhasil diperbarui.',
        ]);
    }

    public function settlePanjar(Request $request, KasTunai $kasTunai)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($kasTunai->category !== 'panjar_kerja' || ! in_array($kasTunai->panjar_status, ['pending', 'partial'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini bukan kasbon/panjar yang berstatus aktif/pending.',
            ], 422);
        }

        foreach (['real_amount', 'nota_amount', 'other_amount', 'cash_return_amount'] as $field) {
            if ($request->has($field)) {
                $request->merge([
                    $field => $this->parseCurrencyAmount($request->input($field)),
                ]);
            }
        }

        if ($request->has('items')) {
            $rawItems = $request->input('items');
            if (is_string($rawItems)) {
                $rawItems = json_decode($rawItems, true) ?: [];
            }
            if (is_array($rawItems)) {
                $cleanedItems = [];
                foreach ($rawItems as $item) {
                    if (empty($item['item_name'])) {
                        continue;
                    }
                    $qty = isset($item['quantity']) ? (float) str_replace(',', '.', (string) $item['quantity']) : 1;
                    $price = isset($item['price']) ? $this->parseCurrencyAmount($item['price']) : 0;
                    $total = isset($item['total_price']) ? $this->parseCurrencyAmount($item['total_price']) : ($qty * $price);
                    if ($total <= 0 && $price > 0) {
                        $total = $qty * $price;
                    } elseif ($price <= 0 && $total > 0 && $qty > 0) {
                        $price = $total / $qty;
                    }
                    $cleanedItems[] = [
                        'item_name' => trim((string) ($item['item_name'] ?? '')),
                        'quantity' => $qty > 0 ? $qty : 1,
                        'unit' => trim((string) ($item['unit'] ?? '')),
                        'price' => $price,
                        'total_price' => $total,
                        'notes' => trim((string) ($item['notes'] ?? '')),
                    ];
                }
                $request->merge(['items' => $cleanedItems]);
            }
        }

        $validated = $request->validate([
            'settle_date' => 'required|date',
            'real_amount' => 'nullable|numeric|min:0',
            'nota_amount' => 'nullable|numeric|min:0',
            'other_amount' => 'nullable|numeric|min:0',
            'other_notes' => 'nullable|string|max:255',
            'cash_return_amount' => 'nullable|numeric|min:0',
            'is_partial' => 'nullable|boolean',
            'person_name' => 'nullable|string|max:255',
            'evidence_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'bos_rencana_belanja_id' => 'nullable|exists:bos_rencana_belanjas,id',
            'perjadin_item_id' => 'nullable|exists:perjadin_items,id',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.total_price' => 'required_with:items|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'receipt_file.max' => 'Ukuran file nota maksimal 20 MB.',
            'receipt_file.mimes' => 'Format file nota harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        $notaAmount = 0.0;
        $rawReceipts = $request->input('receipts');
        if (is_string($rawReceipts)) {
            $rawReceipts = json_decode($rawReceipts, true) ?: [];
        }
        $cleanedReceipts = [];
        if (is_array($rawReceipts) && ! empty($rawReceipts)) {
            foreach ($rawReceipts as $idx => $r) {
                if (empty($r['amount']) && empty($r['store_name']) && empty($r['items'])) {
                    continue;
                }
                $rAmount = isset($r['amount']) ? $this->parseCurrencyAmount($r['amount']) : 0;
                $rItems = [];
                if (! empty($r['items']) && is_array($r['items'])) {
                    foreach ($r['items'] as $item) {
                        if (empty($item['item_name'])) {
                            continue;
                        }
                        $qty = isset($item['quantity']) ? (float) str_replace(',', '.', (string) $item['quantity']) : 1;
                        $price = isset($item['price']) ? $this->parseCurrencyAmount($item['price']) : 0;
                        $total = isset($item['total_price']) ? $this->parseCurrencyAmount($item['total_price']) : ($qty * $price);
                        if ($total <= 0 && $price > 0) {
                            $total = $qty * $price;
                        } elseif ($price <= 0 && $total > 0 && $qty > 0) {
                            $price = $total / $qty;
                        }
                        $rItems[] = [
                            'item_name' => trim((string) ($item['item_name'] ?? '')),
                            'quantity' => $qty > 0 ? $qty : 1,
                            'unit' => trim((string) ($item['unit'] ?? '')),
                            'price' => $price,
                            'total_price' => $total,
                            'notes' => trim((string) ($item['notes'] ?? '')),
                        ];
                    }
                    if ($rAmount <= 0 && ! empty($rItems)) {
                        $rAmount = collect($rItems)->sum('total_price');
                    }
                }
                $cleanedReceipts[] = [
                    'index' => $idx,
                    'store_name' => trim((string) ($r['store_name'] ?? ($r['person_name'] ?? ''))),
                    'evidence_number' => trim((string) ($r['evidence_number'] ?? '')),
                    'amount' => $rAmount,
                    'items' => $rItems,
                ];
            }
        }

        $rawExpenses = $request->input('expenses');
        if (is_string($rawExpenses)) {
            $rawExpenses = json_decode($rawExpenses, true) ?: [];
        }
        $cleanedExpenses = [];
        if (is_array($rawExpenses) && ! empty($rawExpenses)) {
            foreach ($rawExpenses as $e) {
                $eAmount = isset($e['amount']) ? (float) preg_replace('/[^0-9.]/', '', (string) $e['amount']) : 0;
                if ($eAmount <= 0 && empty($e['title'])) {
                    continue;
                }
                $cleanedExpenses[] = [
                    'title' => trim((string) ($e['title'] ?? ($e['expense_name'] ?? 'Biaya Operasional'))),
                    'amount' => $eAmount,
                    'evidence_number' => trim((string) ($e['evidence_number'] ?? '')),
                    'notes' => trim((string) ($e['notes'] ?? '')),
                ];
            }
        }

        if (! empty($cleanedReceipts)) {
            $notaAmount = (float) collect($cleanedReceipts)->sum('amount');
        } elseif ($request->filled('nota_amount')) {
            $notaAmount = (float) $validated['nota_amount'];
        } elseif ($request->filled('real_amount')) {
            $notaAmount = (float) $validated['real_amount'];
        }

        if (! empty($cleanedExpenses)) {
            $otherAmount = (float) collect($cleanedExpenses)->sum('amount');
        } else {
            $otherAmount = (float) ($validated['other_amount'] ?? 0);
        }

        $cashReturnAmount = (float) ($validated['cash_return_amount'] ?? 0);
        $otherNotes = trim((string) ($validated['other_notes'] ?? ''));
        $isPartial = $request->boolean('is_partial');

        if ($notaAmount <= 0 && $otherAmount <= 0 && $cashReturnAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal salah satu nominal (belanja nota, biaya lain-lain, atau sisa uang) harus diisi lebih dari 0.',
            ], 422);
        }

        // 1. Validasi Keunikan Nomor Bukti Transaksi
        $evidenceNumbersToCheck = [];
        if (! empty($cleanedReceipts)) {
            foreach ($cleanedReceipts as $rData) {
                if (! empty($rData['evidence_number'])) {
                    $evidenceNumbersToCheck[] = $rData['evidence_number'];
                }
            }
        } elseif (! empty($validated['evidence_number'])) {
            $evidenceNumbersToCheck[] = trim((string) $validated['evidence_number']);
        }

        if (! empty($cleanedExpenses)) {
            foreach ($cleanedExpenses as $eData) {
                if (! empty($eData['evidence_number'])) {
                    $evidenceNumbersToCheck[] = $eData['evidence_number'];
                }
            }
        }

        $transactionYear = date('Y', strtotime($validated['settle_date']));
        foreach (array_unique($evidenceNumbersToCheck) as $evidenceNo) {
            $evidenceNo = trim((string) $evidenceNo);
            if ($evidenceNo === '' || in_array($evidenceNo, ['-', '0', 'none', 'null'], true)) {
                continue;
            }
            $existingEvidence = KasTunai::funding($fundingSource)
                ->whereYear('transaction_date', $transactionYear)
                ->where('evidence_number', $evidenceNo)
                ->first();

            if ($existingEvidence) {
                return response()->json([
                    'success' => false,
                    'message' => "Nomor bukti nota '{$evidenceNo}' sudah pernah digunakan pada transaksi {$existingEvidence->id_display} ({$existingEvidence->description}). Nomor bukti tidak boleh duplikat untuk mencegah pencatatan ganda.",
                ], 422);
            }
        }

        // 2. Validasi Ketersediaan Kas Fisik jika terjadi Nombok (Reimburse) pada Full Settlement
        $thisTotal = $notaAmount + $otherAmount + $cashReturnAmount;
        $potentialRemaining = $kasTunai->remaining_panjar_amount - $thisTotal;
        if (! $isPartial && $potentialRemaining < 0) {
            $shortage = abs($potentialRemaining);
            $currentBalance = KasTunai::getAvailableCashBalance($fundingSource);
            if ($shortage > $currentBalance && ! $request->boolean('allow_negative_balance', false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengeluaran kas reimburse (nombok) sebesar Rp '.number_format($shortage, 0, ',', '.').' melampaui sisa saldo kas tunai yang tersedia (Rp '.number_format($currentBalance, 0, ',', '.').'). Saldo kas tidak boleh bernilai negatif.',
                ], 422);
            }
        }

        $resultMessage = '';

        DB::transaction(function () use ($kasTunai, $validated, $fundingSource, $request, $notaAmount, $otherAmount, $otherNotes, $cashReturnAmount, $isPartial, $cleanedReceipts, $cleanedExpenses, &$resultMessage) {
            $currentRemaining = $kasTunai->remaining_panjar_amount;
            $receiptPath = null;

            if ($request->hasFile('receipt_file')) {
                $receiptPath = $request->file('receipt_file')->store('kas_tunai', 'public');
            }

            // 1. Catat belanja tunai aktual sesuai nota (Multi-Nota atau Nota Tunggal)
            if (! empty($cleanedReceipts)) {
                foreach ($cleanedReceipts as $cIdx => $rData) {
                    if ($rData['amount'] <= 0) {
                        continue;
                    }

                    $rFileKey = "receipt_file_{$cIdx}";
                    $rPath = null;
                    if ($request->hasFile($rFileKey)) {
                        $rPath = $request->file($rFileKey)->store('kas_tunai', 'public');
                    } elseif ($request->hasFile("receipts.{$cIdx}.receipt_file")) {
                        $rPath = $request->file("receipts.{$cIdx}.receipt_file")->store('kas_tunai', 'public');
                    } elseif ($cIdx === 0 && $receiptPath) {
                        $rPath = $receiptPath;
                    }

                    $belanja = KasTunai::create([
                        'funding_source' => $fundingSource,
                        'transaction_date' => $validated['settle_date'],
                        'transaction_type' => 'keluar',
                        'category' => 'belanja_tunai',
                        'amount' => $rData['amount'],
                        'description' => 'Realisasi Belanja Panjar '.$kasTunai->id_display.': '.($rData['store_name'] ?: 'Belanja Nota'),
                        'person_name' => $rData['store_name'] ?: $kasTunai->person_name,
                        'guru_id' => $kasTunai->guru_id,
                        'parent_kas_id' => $kasTunai->id,
                        'panjar_status' => 'settled',
                        'evidence_number' => $rData['evidence_number'] ?: null,
                        'receipt_file' => $rPath,
                        'created_by' => Auth::id(),
                    ]);

                    if (! empty($rData['items'])) {
                        foreach ($rData['items'] as $item) {
                            $belanja->items()->create($item);
                        }
                    }
                }
            } elseif ($notaAmount > 0) {
                $belanja = KasTunai::create([
                    'funding_source' => $fundingSource,
                    'transaction_date' => $validated['settle_date'],
                    'transaction_type' => 'keluar',
                    'category' => 'belanja_tunai',
                    'amount' => $notaAmount,
                    'description' => 'Realisasi Belanja Panjar '.$kasTunai->id_display.': '.($validated['notes'] ?: $kasTunai->description),
                    'person_name' => $validated['person_name'] ?? $kasTunai->person_name,
                    'guru_id' => $kasTunai->guru_id,
                    'bos_rencana_belanja_id' => $validated['bos_rencana_belanja_id'] ?? $kasTunai->bos_rencana_belanja_id,
                    'perjadin_item_id' => $validated['perjadin_item_id'] ?? $kasTunai->perjadin_item_id,
                    'parent_kas_id' => $kasTunai->id,
                    'panjar_status' => 'settled',
                    'evidence_number' => $validated['evidence_number'] ?? null,
                    'receipt_file' => $receiptPath,
                    'created_by' => Auth::id(),
                ]);

                if (! empty($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        $belanja->items()->create($item);
                    }
                }
            }

            // 2. Catat biaya lain-lain / operasional penyerta (Multi-Expense atau Tunggal)
            if (! empty($cleanedExpenses)) {
                foreach ($cleanedExpenses as $exp) {
                    if ($exp['amount'] <= 0) {
                        continue;
                    }
                    KasTunai::create([
                        'funding_source' => $fundingSource,
                        'transaction_date' => $validated['settle_date'],
                        'transaction_type' => 'keluar',
                        'category' => 'operasional_laporan',
                        'amount' => $exp['amount'],
                        'description' => $exp['title'].' (SPJ Kasbon '.$kasTunai->id_display.')'.($exp['notes'] ? ' - '.$exp['notes'] : ''),
                        'person_name' => $kasTunai->person_name,
                        'guru_id' => $kasTunai->guru_id,
                        'parent_kas_id' => $kasTunai->id,
                        'panjar_status' => 'settled',
                        'evidence_number' => $exp['evidence_number'] ?: null,
                        'created_by' => Auth::id(),
                    ]);
                }
            } elseif ($otherAmount > 0) {
                KasTunai::create([
                    'funding_source' => $fundingSource,
                    'transaction_date' => $validated['settle_date'],
                    'transaction_type' => 'keluar',
                    'category' => 'operasional_laporan',
                    'amount' => $otherAmount,
                    'description' => 'Biaya Lain-lain Belanja Kasbon '.$kasTunai->id_display.($otherNotes ? ' ('.$otherNotes.')' : '').': '.($validated['notes'] ?: $kasTunai->description),
                    'person_name' => $kasTunai->person_name,
                    'guru_id' => $kasTunai->guru_id,
                    'parent_kas_id' => $kasTunai->id,
                    'panjar_status' => 'settled',
                    'evidence_number' => $validated['evidence_number'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            // 3. Catat sisa uang kembalian masuk ke kas tunai jika diisi
            if ($cashReturnAmount > 0) {
                KasTunai::create([
                    'funding_source' => $fundingSource,
                    'transaction_date' => $validated['settle_date'],
                    'transaction_type' => 'masuk',
                    'category' => 'sisa_panjar',
                    'amount' => $cashReturnAmount,
                    'description' => 'Pengembalian Sisa Kasbon '.$kasTunai->id_display.': '.($validated['notes'] ?: $kasTunai->description),
                    'person_name' => $kasTunai->person_name,
                    'guru_id' => $kasTunai->guru_id,
                    'parent_kas_id' => $kasTunai->id,
                    'panjar_status' => 'settled',
                    'evidence_number' => $validated['evidence_number'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $thisTotal = $notaAmount + $otherAmount + $cashReturnAmount;
            $newRemaining = $currentRemaining - $thisTotal;

            if ($isPartial) {
                if ($newRemaining > 0) {
                    $kasTunai->update([
                        'panjar_status' => 'partial',
                        'updated_by' => Auth::id(),
                    ]);
                    $resultMessage = 'Pengembalian kasbon tahap ini berhasil dicatat. Sisa tanggungan kasbon: Rp '.number_format($newRemaining, 0, ',', '.').'.';
                } else {
                    $kasTunai->update([
                        'panjar_status' => 'settled',
                        'updated_by' => Auth::id(),
                    ]);
                    $resultMessage = 'Kasbon berhasil diselesaikan secara lunas.';
                }
            } else {
                // Pelunasan penuh / full settlement
                // Jika tidak memasukkan cash_return_amount eksplisit (misal form legacy) dan masih ada sisa,
                // sisa otomatis dibuatkan record sisa_panjar
                if ($newRemaining > 0 && ! $request->has('cash_return_amount')) {
                    KasTunai::create([
                        'funding_source' => $fundingSource,
                        'transaction_date' => $validated['settle_date'],
                        'transaction_type' => 'masuk',
                        'category' => 'sisa_panjar',
                        'amount' => $newRemaining,
                        'description' => 'Pengembalian Sisa Belanja/Panjar '.$kasTunai->id_display.': '.($validated['notes'] ?: $kasTunai->description),
                        'person_name' => $kasTunai->person_name,
                        'guru_id' => $kasTunai->guru_id,
                        'parent_kas_id' => $kasTunai->id,
                        'panjar_status' => 'settled',
                        'evidence_number' => $validated['evidence_number'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                } elseif ($newRemaining < 0) {
                    // Nombok (pengeluaran melebihi kasbon)
                    $kurang = abs($newRemaining);
                    KasTunai::create([
                        'funding_source' => $fundingSource,
                        'transaction_date' => $validated['settle_date'],
                        'transaction_type' => 'keluar',
                        'category' => 'reimburse',
                        'amount' => $kurang,
                        'description' => 'Tambahan Biaya Belanja Panjar (Nombok) '.$kasTunai->id_display.': '.($validated['notes'] ?: $kasTunai->description),
                        'person_name' => $kasTunai->person_name,
                        'guru_id' => $kasTunai->guru_id,
                        'parent_kas_id' => $kasTunai->id,
                        'panjar_status' => 'settled',
                        'evidence_number' => $validated['evidence_number'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }

                $kasTunai->update([
                    'panjar_status' => 'settled',
                    'updated_by' => Auth::id(),
                ]);
                $resultMessage = 'Kasbon/panjar berhasil diselesaikan secara lunas dan dicatat ke pembukuan.';
            }

            // Tandai PerjadinItem jika kasbon ditutup lunas
            if ($kasTunai->panjar_status === 'settled') {
                $linkedPerjadinItemId = $validated['perjadin_item_id'] ?? $kasTunai->perjadin_item_id;
                if ($linkedPerjadinItemId) {
                    PerjadinItem::where('id', $linkedPerjadinItemId)->update(['is_paid' => true]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $resultMessage,
        ]);
    }

    public function getItems(KasTunai $kasTunai)
    {
        $items = $kasTunai->items;
        $receiptFile = $kasTunai->receipt_file;

        if ($items->isEmpty() && $kasTunai->category === 'panjar_kerja') {
            $childBelanja = KasTunai::where('parent_kas_id', $kasTunai->id)
                ->where('category', 'belanja_tunai')
                ->first();
            if ($childBelanja) {
                $items = $childBelanja->items;
                if (! $receiptFile && $childBelanja->receipt_file) {
                    $receiptFile = $childBelanja->receipt_file;
                }
            }
        }

        return response()->json([
            'kas_tunai' => [
                'id' => $kasTunai->id,
                'transaction_date' => $kasTunai->transaction_date ? $kasTunai->transaction_date->format('d/m/Y') : '-',
                'raw_date' => $kasTunai->transaction_date ? $kasTunai->transaction_date->format('Y-m-d') : '',
                'category_label' => $kasTunai->category_label,
                'amount' => (float) $kasTunai->amount,
                'formatted_amount' => 'Rp '.number_format($kasTunai->amount, 0, ',', '.'),
                'description' => $kasTunai->description,
                'person_name' => $kasTunai->guru ? $kasTunai->guru->name : ($kasTunai->person_name ?: '-'),
                'guru_id' => $kasTunai->guru_id,
                'evidence_number' => $kasTunai->evidence_number ?: '-',
                'receipt_url' => $receiptFile ? asset('storage/'.$receiptFile) : null,
                'receipt_is_image' => $receiptFile ? (bool) preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $receiptFile) : false,
            ],
            'items' => $items->map(function ($item, $index) {
                return [
                    'index' => $index + 1,
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit ?: '-',
                    'price' => (float) $item->price,
                    'formatted_price' => 'Rp '.number_format($item->price, 0, ',', '.'),
                    'total_price' => (float) $item->total_price,
                    'formatted_total_price' => 'Rp '.number_format($item->total_price, 0, ',', '.'),
                    'notes' => $item->notes ?: '',
                ];
            }),
            'total_items_amount' => (float) $items->sum('total_price'),
            'formatted_total_items_amount' => 'Rp '.number_format($items->sum('total_price'), 0, ',', '.'),
        ]);
    }

    public function getDetail(KasTunai $kasTunai)
    {
        $kasTunai->load([
            'guru',
            'items',
            'parentKas.guru',
            'childKass' => function ($query) {
                $query->with(['items', 'guru'])->orderBy('transaction_date')->orderBy('id');
            },
            'rencanaBelanja',
            'perjadinItem.perjadin',
            'pencairanBank',
        ]);

        $isPanjar = $kasTunai->category === 'panjar_kerja';

        $childReceipts = [];
        $childExpenses = [];
        $childReturns = [];
        $childReimburses = [];

        if ($kasTunai->childKass->isNotEmpty()) {
            foreach ($kasTunai->childKass as $child) {
                $childData = [
                    'id' => $child->id,
                    'transaction_date' => $child->transaction_date ? $child->transaction_date->format('d/m/Y') : '-',
                    'category' => $child->category,
                    'category_label' => $child->category_label,
                    'amount' => (float) $child->amount,
                    'formatted_amount' => 'Rp '.number_format($child->amount, 0, ',', '.'),
                    'description' => $child->description,
                    'evidence_number' => $child->evidence_number ?: '-',
                    'receipt_url' => $child->receipt_file ? asset('storage/'.$child->receipt_file) : null,
                    'receipt_is_image' => $child->receipt_file ? (bool) preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $child->receipt_file) : false,
                    'items' => $child->items->map(function ($it, $idx) {
                        return [
                            'index' => $idx + 1,
                            'id' => $it->id,
                            'item_name' => $it->item_name,
                            'quantity' => (float) $it->quantity,
                            'unit' => $it->unit ?: '-',
                            'price' => (float) $it->price,
                            'formatted_price' => 'Rp '.number_format($it->price, 0, ',', '.'),
                            'total_price' => (float) $it->total_price,
                            'formatted_total_price' => 'Rp '.number_format($it->total_price, 0, ',', '.'),
                            'notes' => $it->notes ?: '',
                        ];
                    }),
                ];

                if ($child->category === 'belanja_tunai') {
                    $childReceipts[] = $childData;
                } elseif ($child->category === 'operasional_laporan') {
                    $childExpenses[] = $childData;
                } elseif ($child->category === 'sisa_panjar') {
                    $childReturns[] = $childData;
                } elseif ($child->category === 'reimburse') {
                    $childReimburses[] = $childData;
                }
            }
        }

        $directItems = $kasTunai->items->map(function ($it, $idx) {
            return [
                'index' => $idx + 1,
                'id' => $it->id,
                'item_name' => $it->item_name,
                'quantity' => (float) $it->quantity,
                'unit' => $it->unit ?: '-',
                'price' => (float) $it->price,
                'formatted_price' => 'Rp '.number_format($it->price, 0, ',', '.'),
                'total_price' => (float) $it->total_price,
                'formatted_total_price' => 'Rp '.number_format($it->total_price, 0, ',', '.'),
                'notes' => $it->notes ?: '',
            ];
        });

        // Panjar calculations
        $totalBelanja = collect($childReceipts)->sum('amount');
        $totalExpenses = collect($childExpenses)->sum('amount');
        $totalReturns = collect($childReturns)->sum('amount');
        $totalReimburses = collect($childReimburses)->sum('amount');
        $totalSettled = $totalBelanja + $totalExpenses + $totalReturns;
        $panjarRemaining = max(0, (float) $kasTunai->amount - $totalSettled);

        return response()->json([
            'kas_tunai' => [
                'id' => $kasTunai->id,
                'code' => $kasTunai->id_display,
                'funding_source' => $kasTunai->funding_source,
                'transaction_date' => $kasTunai->transaction_date ? $kasTunai->transaction_date->format('d/m/Y') : '-',
                'raw_date' => $kasTunai->transaction_date ? $kasTunai->transaction_date->format('Y-m-d') : '',
                'transaction_type' => $kasTunai->transaction_type,
                'category' => $kasTunai->category,
                'category_label' => $kasTunai->category_label,
                'amount' => (float) $kasTunai->amount,
                'formatted_amount' => 'Rp '.number_format($kasTunai->amount, 0, ',', '.'),
                'description' => $kasTunai->description,
                'person_name' => $kasTunai->guru ? $kasTunai->guru->name : ($kasTunai->person_name ?: '-'),
                'guru_id' => $kasTunai->guru_id,
                'evidence_number' => $kasTunai->evidence_number ?: '-',
                'receipt_url' => $kasTunai->receipt_file ? asset('storage/'.$kasTunai->receipt_file) : null,
                'receipt_is_image' => $kasTunai->receipt_file ? (bool) preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $kasTunai->receipt_file) : false,
                'panjar_status' => $kasTunai->panjar_status,
                'parent_kas' => $kasTunai->parentKas ? [
                    'id' => $kasTunai->parentKas->id,
                    'code' => $kasTunai->parentKas->id_display,
                    'description' => $kasTunai->parentKas->description,
                    'amount' => (float) $kasTunai->parentKas->amount,
                    'formatted_amount' => 'Rp '.number_format($kasTunai->parentKas->amount, 0, ',', '.'),
                    'person_name' => $kasTunai->parentKas->guru ? $kasTunai->parentKas->guru->name : ($kasTunai->parentKas->person_name ?: '-'),
                ] : null,
                'is_panjar' => $isPanjar,
            ],
            'direct_items' => $directItems,
            'child_receipts' => $childReceipts,
            'child_expenses' => $childExpenses,
            'child_returns' => $childReturns,
            'child_reimburses' => $childReimburses,
            'panjar_summary' => $isPanjar ? [
                'panjar_amount' => (float) $kasTunai->amount,
                'formatted_panjar_amount' => 'Rp '.number_format($kasTunai->amount, 0, ',', '.'),
                'total_belanja' => $totalBelanja,
                'formatted_total_belanja' => 'Rp '.number_format($totalBelanja, 0, ',', '.'),
                'total_expenses' => $totalExpenses,
                'formatted_total_expenses' => 'Rp '.number_format($totalExpenses, 0, ',', '.'),
                'total_returns' => $totalReturns,
                'formatted_total_returns' => 'Rp '.number_format($totalReturns, 0, ',', '.'),
                'total_reimburses' => $totalReimburses,
                'formatted_total_reimburses' => 'Rp '.number_format($totalReimburses, 0, ',', '.'),
                'remaining' => $panjarRemaining,
                'formatted_remaining' => 'Rp '.number_format($panjarRemaining, 0, ',', '.'),
            ] : null,
        ]);
    }

    public function destroy(KasTunai $kasTunai)
    {
        if ($kasTunai->receipt_file) {
            Storage::disk('public')->delete($kasTunai->receipt_file);
        }

        $kasTunai->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus.',
        ]);
    }

    public function printKas(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $school = SchoolProfile::first();

        $query = KasTunai::funding($fundingSource)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        $month = $request->get('month');
        $year = $request->get('year', date('Y'));

        // Saldo Awal (Pindahan) sebelum periode yang dicetak
        $saldoAwalQuery = KasTunai::funding($fundingSource);
        if ($month && $year && $year !== 'all') {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $saldoAwalQuery->whereDate('transaction_date', '<', $startDate);
        } elseif ($year && $year !== 'all') {
            $startDate = sprintf('%04d-01-01', $year);
            $saldoAwalQuery->whereDate('transaction_date', '<', $startDate);
        }

        if ($year && $year !== 'all') {
            $saldoAwalMasuk = (float) (clone $saldoAwalQuery)->where('transaction_type', 'masuk')->sum('amount');
            $saldoAwalKeluar = (float) (clone $saldoAwalQuery)->actualCashOutflow()->sum('amount');
            $saldoAwal = $saldoAwalMasuk - $saldoAwalKeluar;
        } else {
            $saldoAwal = 0;
        }

        if ($month) {
            $query->whereMonth('transaction_date', $month);
        }
        if ($year && $year !== 'all') {
            $query->whereYear('transaction_date', $year);
        }

        $transactions = $query->get();

        // Pejabat penandatangan
        $targetDate = now();
        $kepsek = GuruJabatan::getPejabatByDate('kepala_sekolah', $targetDate)?->guru;
        $kepsekName = $kepsek ? $kepsek->full_name_with_title : 'ENDANG PATMOWATI, S.Pd';

        $bendaharaRole = ($fundingSource === 'BPOPP') ? 'bendahara_bpopp' : 'bendahara_bosp';
        $bendahara = GuruJabatan::getPejabatByDate($bendaharaRole, $targetDate)?->guru;
        $bendaharaName = $bendahara ? $bendahara->full_name_with_title : ($fundingSource === 'BPOPP' ? 'ARMY WIDHI CAHYONO, S.Pt.' : 'ALIYA NUSAIBA, S.Pd.');
        $bendaharaTitle = ($fundingSource === 'BPOPP') ? 'Bendahara BPOPP' : 'Bendahara BOSP';

        return view('kas_tunai.print', compact(
            'fundingSource',
            'school',
            'transactions',
            'saldoAwal',
            'month',
            'year',
            'kepsekName',
            'bendaharaName',
            'bendaharaTitle'
        ));
    }

    public function printOpname(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $school = SchoolProfile::first();

        $opnameDate = $request->get('opname_date', date('Y-m-d'));
        $notes = $request->get('notes', '');

        $denominations = [
            '100000' => (int) $request->get('pecahan_100000', 0),
            '50000' => (int) $request->get('pecahan_50000', 0),
            '20000' => (int) $request->get('pecahan_20000', 0),
            '10000' => (int) $request->get('pecahan_10000', 0),
            '5000' => (int) $request->get('pecahan_5000', 0),
            '2000' => (int) $request->get('pecahan_2000', 0),
            '1000' => (int) $request->get('pecahan_1000', 0),
            'koin' => (float) $request->get('pecahan_koin', 0),
        ];

        $totalFisik = 0;
        foreach ($denominations as $denom => $count) {
            if ($denom === 'koin') {
                $totalFisik += $count;
            } else {
                $totalFisik += ((int) $denom * $count);
            }
        }

        // Saldo buku kas per tanggal opname (Kas Masuk - Pengeluaran Fisik Riil Brankas)
        $totalMasuk = (float) KasTunai::funding($fundingSource)
            ->whereDate('transaction_date', '<=', $opnameDate)
            ->where('transaction_type', 'masuk')
            ->sum('amount');

        $totalKeluar = (float) KasTunai::funding($fundingSource)
            ->whereDate('transaction_date', '<=', $opnameDate)
            ->actualCashOutflow()
            ->sum('amount');

        $saldoBuku = $totalMasuk - $totalKeluar;
        $selisih = $totalFisik - $saldoBuku;

        $targetDate = $opnameDate ?: now();
        $kepsek = GuruJabatan::getPejabatByDate('kepala_sekolah', $targetDate)?->guru;
        $kepsekName = $kepsek ? $kepsek->full_name_with_title : 'ENDANG PATMOWATI, S.Pd';

        $bendaharaRole = ($fundingSource === 'BPOPP') ? 'bendahara_bpopp' : 'bendahara_bosp';
        $bendahara = GuruJabatan::getPejabatByDate($bendaharaRole, $targetDate)?->guru;
        $bendaharaName = $bendahara ? $bendahara->full_name_with_title : ($fundingSource === 'BPOPP' ? 'ARMY WIDHI CAHYONO, S.Pt.' : 'ALIYA NUSAIBA, S.Pd.');
        $bendaharaTitle = ($fundingSource === 'BPOPP') ? 'Bendahara BPOPP' : 'Bendahara BOSP';

        return view('kas_tunai.print_opname', compact(
            'fundingSource',
            'school',
            'opnameDate',
            'denominations',
            'totalFisik',
            'saldoBuku',
            'selisih',
            'notes',
            'kepsekName',
            'bendaharaName',
            'bendaharaTitle'
        ));
    }

    public function kasbonIndex(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $selectedYear = $request->get('year', (string) session('fiscal_year', date('Y')));

        // Query pending & partial panjar
        $query = KasTunai::funding($fundingSource)
            ->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->with([
                'guru',
                'childKass' => function ($q) {
                    $q->with(['items', 'createdBy', 'kasCategory'])
                        ->orderBy('transaction_date', 'asc')
                        ->orderBy('id', 'asc');
                },
                'rencanaBelanja',
                'perjadinItem',
                'createdBy',
            ])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($selectedYear !== 'all') {
            $query->whereYear('transaction_date', '<=', $selectedYear);
        }

        $pendingKasbons = $query->get()->map(function ($kas) {
            $diffDays = $kas->transaction_date ? (int) $kas->transaction_date->diffInDays(now()) : 0;
            $kas->aging_days = $diffDays;
            $kas->completion_status = 'unsettled';

            if ($diffDays <= 3) {
                $kas->aging_badge = 'badge-success';
                $kas->aging_badge_class = 'badge-status-new';
                $kas->aging_status = 'new';
                $kas->aging_label = 'Baru ('.$diffDays.' hr)';
            } elseif ($diffDays <= 7) {
                $kas->aging_badge = 'badge-warning text-dark';
                $kas->aging_badge_class = 'badge-status-warning';
                $kas->aging_status = 'warning';
                $kas->aging_label = 'Perhatian ('.$diffDays.' hr)';
            } else {
                $kas->aging_badge = 'badge-danger';
                $kas->aging_badge_class = 'badge-status-danger';
                $kas->aging_status = 'danger';
                $kas->aging_label = 'Menunggak ('.$diffDays.' hr)';
            }

            $kas->remaining_amount = $kas->remaining_panjar_amount;
            $kas->settled_amount = $kas->settled_panjar_amount;
            $kas->formatted_remaining = 'Rp '.number_format($kas->remaining_amount, 0, ',', '.');
            $kas->formatted_settled = 'Rp '.number_format($kas->settled_amount, 0, ',', '.');
            $kas->formatted_date = $kas->transaction_date ? $kas->transaction_date->translatedFormat('d M Y') : '-';
            $kas->formatted_amount = 'Rp '.number_format($kas->amount, 0, ',', '.');
            $kas->person_name = $kas->guru ? $kas->guru->name : ($kas->person_name ?: 'Staf / Guru');
            $kas->receipt_file_url = $kas->receipt_file ? Storage::url($kas->receipt_file) : null;
            $kas->is_receipt_image = $kas->receipt_file && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', (string) $kas->receipt_file);

            if ($kas->relationLoaded('childKass')) {
                $kas->childKass->transform(function ($child) {
                    $child->formatted_date = $child->transaction_date ? $child->transaction_date->translatedFormat('d M Y') : '-';
                    $child->formatted_amount = 'Rp '.number_format($child->amount, 0, ',', '.');
                    $child->receipt_file_url = $child->receipt_file ? Storage::url($child->receipt_file) : null;
                    $child->is_receipt_image = $child->receipt_file && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', (string) $child->receipt_file);

                    return $child;
                });
            }

            return $kas;
        });

        // Grouping by person
        $groupedByPerson = $pendingKasbons->groupBy(function ($item) {
            return $item->person_name;
        });

        $personSummary = $groupedByPerson->map(function ($items, $person) {
            $worstStatus = 'new';
            if ($items->contains(fn ($i) => $i->aging_days > 7)) {
                $worstStatus = 'danger';
            } elseif ($items->contains(fn ($i) => $i->aging_days > 3)) {
                $worstStatus = 'warning';
            }

            $remainingSum = $items->sum(fn ($i) => $i->remaining_amount);

            return [
                'name' => $person,
                'count' => $items->count(),
                'total_amount' => $remainingSum,
                'formatted_total_amount' => 'Rp '.number_format($remainingSum, 0, ',', '.'),
                'oldest_days' => $items->max('aging_days') ?? 0,
                'worst_status' => $worstStatus,
            ];
        })->values();

        $totalKasbonPending = (float) $pendingKasbons->sum(fn ($i) => $i->remaining_amount);
        $countKasbonPending = $pendingKasbons->count();
        $countPersonPending = $personSummary->count();
        $oldestKasbonDays = $pendingKasbons->max('aging_days') ?? 0;

        $totalPendingAmount = $totalKasbonPending;
        $countPanjarPending = $countKasbonPending;
        $countPersons = $countPersonPending;
        $oldestDays = $oldestKasbonDays;

        // References for Settle Panjar Modal
        $gurus = Guru::where('is_active', true)->orderBy('name')->get();
        $categories = KasTunai::categories();

        $pencairanBanks = PencairanBank::funding($fundingSource)->latest('id')->limit(30)->get();
        $rencanaBelanjas = ($fundingSource === 'BOSP')
            ? BosRencanaBelanja::with('supplier')->latest('id')->limit(40)->get()
            : collect();
        $perjadinItems = PerjadinItem::whereHas('perjadin', fn ($q) => $q->where('funding_source', $fundingSource))
            ->where('is_paid', false)
            ->with(['perjadin', 'guru'])
            ->latest('id')
            ->limit(40)
            ->get();

        // Query settled / finished panjar (Arsip Kasbon yang sudah selesai)
        $settledQuery = KasTunai::funding($fundingSource)
            ->where('category', 'panjar_kerja')
            ->where('panjar_status', 'settled')
            ->with([
                'guru',
                'childKass' => function ($q) {
                    $q->with(['items', 'createdBy', 'kasCategory'])
                        ->orderBy('transaction_date', 'asc')
                        ->orderBy('id', 'asc');
                },
                'rencanaBelanja',
                'perjadinItem',
                'createdBy',
            ])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($selectedYear !== 'all') {
            $settledQuery->whereYear('transaction_date', $selectedYear);
        }

        $settledKasbons = $settledQuery->get()->map(function ($kas) {
            $kas->completion_status = 'settled';
            $kas->aging_days = 0;
            $kas->aging_badge = 'badge-success';
            $kas->aging_badge_class = 'badge-status-new';
            $kas->aging_status = 'settled';
            $kas->aging_label = 'Selesai SPJ';
            $kas->remaining_amount = 0.0;
            $kas->settled_amount = (float) $kas->amount;
            $kas->formatted_remaining = 'Rp 0';
            $kas->formatted_settled = 'Rp '.number_format($kas->amount, 0, ',', '.');
            $kas->formatted_date = $kas->transaction_date ? $kas->transaction_date->translatedFormat('d M Y') : '-';
            $kas->formatted_amount = 'Rp '.number_format($kas->amount, 0, ',', '.');
            $kas->person_name = $kas->guru ? $kas->guru->name : ($kas->person_name ?: 'Staf / Guru');
            $kas->receipt_file_url = $kas->receipt_file ? Storage::url($kas->receipt_file) : null;
            $kas->is_receipt_image = $kas->receipt_file && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', (string) $kas->receipt_file);

            // Perhitungan realisasi belanja nota, operasional/transport, dan sisa uang
            $notaBelanja = (float) $kas->childKass->where('category', 'belanja_tunai')->sum('amount');
            $operasional = (float) $kas->childKass->where('category', 'operasional_laporan')->sum('amount');
            $kembali = (float) $kas->childKass->where('category', 'sisa_panjar')->sum('amount');
            $totalRealisasi = $notaBelanja + $operasional;

            $lastChild = $kas->childKass->sortByDesc('transaction_date')->first();
            $kas->settled_date = $lastChild && $lastChild->transaction_date ? $lastChild->transaction_date->translatedFormat('d M Y') : ($kas->updated_at ? $kas->updated_at->translatedFormat('d M Y') : '-');

            $kas->nota_belanja = $notaBelanja;
            $kas->operasional = $operasional;
            $kas->kembali = $kembali;
            $kas->total_realisasi = $totalRealisasi;

            $kas->formatted_nota = 'Rp '.number_format($notaBelanja, 0, ',', '.');
            $kas->formatted_operasional = 'Rp '.number_format($operasional, 0, ',', '.');
            $kas->formatted_kembali = 'Rp '.number_format($kembali, 0, ',', '.');
            $kas->formatted_realisasi = 'Rp '.number_format($totalRealisasi, 0, ',', '.');

            if ($kas->relationLoaded('childKass')) {
                $kas->childKass->transform(function ($child) {
                    $child->formatted_date = $child->transaction_date ? $child->transaction_date->translatedFormat('d M Y') : '-';
                    $child->formatted_amount = 'Rp '.number_format($child->amount, 0, ',', '.');
                    $child->receipt_file_url = $child->receipt_file ? Storage::url($child->receipt_file) : null;
                    $child->is_receipt_image = $child->receipt_file && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', (string) $child->receipt_file);

                    return $child;
                });
            }

            return $kas;
        });

        // Gabungkan seluruh daftar kasbon (belum selesai di atas, selesai di bawah)
        $panjarList = $pendingKasbons->concat($settledKasbons);
        $countKasbonSettled = $settledKasbons->count();
        $totalNominalSettled = (float) $settledKasbons->sum('amount');

        return view('kas_tunai.kasbon', compact(
            'fundingSource',
            'routePrefix',
            'selectedYear',
            'pendingKasbons',
            'groupedByPerson',
            'totalKasbonPending',
            'countKasbonPending',
            'countPersonPending',
            'oldestKasbonDays',
            'panjarList',
            'personSummary',
            'totalPendingAmount',
            'countPanjarPending',
            'countPersons',
            'oldestDays',
            'settledKasbons',
            'countKasbonSettled',
            'totalNominalSettled',
            'gurus',
            'categories',
            'pencairanBanks',
            'rencanaBelanjas',
            'perjadinItems'
        ));
    }

    public function belanjaBarangIndex(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $selectedYear = $request->get('year', (string) session('fiscal_year', date('Y')));
        $selectedMonth = $request->get('month', '');

        // Summary stats of all commodity items
        $itemsQuery = KasTunaiItem::whereHas('kasTunai', function ($q) use ($fundingSource, $selectedYear, $selectedMonth) {
            $q->where('funding_source', $fundingSource);
            if ($selectedYear !== 'all') {
                $q->whereYear('transaction_date', $selectedYear);
            }
            if ($selectedMonth) {
                $q->whereMonth('transaction_date', $selectedMonth);
            }
        });

        $totalBelanjaBarang = (float) (clone $itemsQuery)->sum('total_price');
        $countTotalItems = (clone $itemsQuery)->count();

        // Query nota belanja (transaksi kas keluar) untuk Tab 1 (Rekap Per Nota)
        $notaQuery = KasTunai::funding($fundingSource)
            ->where('transaction_type', 'keluar')
            ->with(['guru', 'items', 'createdBy', 'kasCategory'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($selectedYear !== 'all') {
            $notaQuery->whereYear('transaction_date', $selectedYear);
        }
        if ($selectedMonth) {
            $notaQuery->whereMonth('transaction_date', $selectedMonth);
        }

        $notaBelanjaList = $notaQuery->get()->map(function ($kas) {
            $items = $kas->items;
            $itemsCount = $items->count();
            $itemsTotal = (float) $items->sum('total_price');
            $kasAmount = (float) $kas->amount;

            $diff = $itemsTotal - $kasAmount;
            if ($itemsCount === 0) {
                $syncStatus = 'empty';
                $syncBadge = 'badge-secondary';
                $syncLabel = 'Belum Ada Item';
            } elseif (abs($diff) < 1) {
                $syncStatus = 'match';
                $syncBadge = 'badge-success';
                $syncLabel = 'Sesuai Nota';
            } elseif ($diff > 0) {
                $syncStatus = 'over';
                $syncBadge = 'badge-warning text-dark';
                $syncLabel = 'Item Lebih Rp '.number_format($diff, 0, ',', '.');
            } else {
                $syncStatus = 'under';
                $syncBadge = 'badge-danger';
                $syncLabel = 'Item Kurang Rp '.number_format(abs($diff), 0, ',', '.');
            }

            $kas->items_count = $itemsCount;
            $kas->items_total = $itemsTotal;
            $kas->sync_status = $syncStatus;
            $kas->sync_badge = $syncBadge;
            $kas->sync_label = $syncLabel;
            $kas->formatted_items_total = 'Rp '.number_format($itemsTotal, 0, ',', '.');
            $kas->formatted_amount = 'Rp '.number_format($kasAmount, 0, ',', '.');
            $kas->formatted_date = $kas->transaction_date ? $kas->transaction_date->translatedFormat('d M Y') : '-';
            $kas->person_name = $kas->guru ? $kas->guru->name : ($kas->person_name ?: 'Staf / Guru');
            $kas->receipt_file_url = $kas->receipt_file ? Storage::url($kas->receipt_file) : null;
            $kas->is_receipt_image = $kas->receipt_file && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', (string) $kas->receipt_file);

            return $kas;
        });

        // Count pending kasbon for badge in nav tab
        $countPanjarPending = KasTunai::funding($fundingSource)
            ->where('category', 'panjar_kerja')
            ->where('panjar_status', 'pending')
            ->count();

        // List available years from KasTunai (database agnostic)
        $availableFiscalYears = KasTunai::funding($fundingSource)
            ->whereNotNull('transaction_date')
            ->pluck('transaction_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($availableFiscalYears)) {
            $availableFiscalYears = [date('Y')];
        }

        $availableYears = $availableFiscalYears;
        $totalBelanjaNominal = $totalBelanjaBarang;
        $totalItemsCount = $countTotalItems;
        $countNotaBelanja = $notaBelanjaList->count();
        $countNotaWithItems = $notaBelanjaList->where('items_count', '>', 0)->count();

        // Master Guru aktif untuk form input
        $gurus = Guru::where('is_active', true)->orderBy('name')->get();

        return view('kas_tunai.belanja_barang', compact(
            'fundingSource',
            'routePrefix',
            'selectedYear',
            'selectedMonth',
            'totalBelanjaBarang',
            'countTotalItems',
            'countPanjarPending',
            'availableFiscalYears',
            'availableYears',
            'totalBelanjaNominal',
            'totalItemsCount',
            'notaBelanjaList',
            'countNotaBelanja',
            'countNotaWithItems',
            'gurus'
        ));
    }

    public function storeBelanjaBarang(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($request->has('amount')) {
            $request->merge([
                'amount' => $this->parseCurrencyAmount($request->input('amount')),
            ]);
        }

        if ($request->has('items')) {
            $rawItems = $request->input('items');
            if (is_string($rawItems)) {
                $rawItems = json_decode($rawItems, true) ?: [];
            }
            if (is_array($rawItems)) {
                $cleanedItems = [];
                foreach ($rawItems as $item) {
                    if (empty($item['item_name'])) {
                        continue;
                    }
                    $qty = isset($item['quantity']) ? (float) str_replace(',', '.', (string) $item['quantity']) : 1;
                    $price = isset($item['price']) ? $this->parseCurrencyAmount($item['price']) : 0;
                    $total = isset($item['total_price']) ? $this->parseCurrencyAmount($item['total_price']) : ($qty * $price);
                    if ($total <= 0 && $price > 0) {
                        $total = $qty * $price;
                    } elseif ($price <= 0 && $total > 0 && $qty > 0) {
                        $price = $total / $qty;
                    }
                    $cleanedItems[] = [
                        'item_name' => trim((string) ($item['item_name'] ?? '')),
                        'quantity' => $qty > 0 ? $qty : 1,
                        'unit' => trim((string) ($item['unit'] ?? '')),
                        'price' => $price,
                        'total_price' => $total,
                        'notes' => trim((string) ($item['notes'] ?? '')),
                    ];
                }
                $request->merge(['items' => $cleanedItems]);
            }
        }

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'guru_id' => 'required|exists:gurus,id',
            'description' => 'required|string|max:1000',
            'evidence_number' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'sync_amount' => 'nullable|boolean',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'guru_id.required' => 'Penanggung jawab / guru pembeli wajib dipilih.',
            'description.required' => 'Toko / Uraian belanja wajib diisi.',
            'items.required' => 'Minimal satu baris rincian barang harus diisi.',
            'items.min' => 'Minimal satu baris rincian barang harus diisi.',
            'items.*.item_name.required' => 'Nama barang tidak boleh kosong.',
            'receipt_file.max' => 'Ukuran file nota maksimal 20 MB.',
            'receipt_file.mimes' => 'Format file nota harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $request->file('receipt_file')->store('kas_tunai', 'public');
        }

        $itemsTotal = collect($validated['items'])->sum('total_price');
        $amount = (! empty($validated['amount']) && ! $request->boolean('sync_amount', true))
            ? (float) $validated['amount']
            : $itemsTotal;

        if ($amount <= 0) {
            $amount = $itemsTotal > 0 ? $itemsTotal : 1;
        }

        // 1. Validasi Keunikan Nomor Bukti Transaksi
        $evidenceNo = trim((string) ($validated['evidence_number'] ?? ''));
        if ($evidenceNo !== '') {
            $transactionYear = date('Y', strtotime($validated['transaction_date']));
            $existingEvidence = KasTunai::funding($fundingSource)
                ->whereYear('transaction_date', $transactionYear)
                ->where('evidence_number', $evidenceNo)
                ->first();

            if ($existingEvidence) {
                return response()->json([
                    'success' => false,
                    'message' => "Nomor bukti transaksi '{$evidenceNo}' sudah pernah digunakan pada transaksi {$existingEvidence->id_display} ({$existingEvidence->description}). Nomor bukti tidak boleh duplikat untuk mencegah pencatatan ganda.",
                ], 422);
            }
        }

        // 2. Validasi Pencegahan Saldo Kas Negatif (Negative Cash Prevention)
        $currentBalance = KasTunai::getAvailableCashBalance($fundingSource);
        if ($amount > $currentBalance && ! $request->boolean('allow_negative_balance', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Pengeluaran belanja barang sebesar Rp '.number_format($amount, 0, ',', '.').' melampaui sisa saldo kas tunai yang tersedia (Rp '.number_format($currentBalance, 0, ',', '.').'). Saldo kas tidak boleh bernilai negatif.',
            ], 422);
        }

        $guru = Guru::find($validated['guru_id']);

        $kas = null;
        DB::transaction(function () use ($fundingSource, $validated, $receiptPath, $amount, $guru, &$kas) {
            $kas = KasTunai::create([
                'funding_source' => $fundingSource,
                'transaction_date' => $validated['transaction_date'],
                'transaction_type' => 'keluar',
                'category' => 'belanja_tunai',
                'amount' => $amount,
                'description' => $validated['description'],
                'person_name' => $guru ? $guru->name : null,
                'guru_id' => $validated['guru_id'],
                'panjar_status' => 'none',
                'evidence_number' => $validated['evidence_number'] ?? null,
                'receipt_file' => $receiptPath,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $kas->items()->create($item);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi belanja nota tunai beserta rincian barang berhasil dicatat.',
            'kas_tunai_id' => $kas ? $kas->id : null,
        ]);
    }

    public function saveNotaItems(Request $request, KasTunai $kasTunai)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($request->has('items')) {
            $rawItems = $request->input('items');
            if (is_string($rawItems)) {
                $rawItems = json_decode($rawItems, true) ?: [];
            }
            if (is_array($rawItems)) {
                $cleanedItems = [];
                foreach ($rawItems as $item) {
                    if (empty($item['item_name'])) {
                        continue;
                    }
                    $qty = isset($item['quantity']) ? (float) str_replace(',', '.', (string) $item['quantity']) : 1;
                    $price = isset($item['price']) ? $this->parseCurrencyAmount($item['price']) : 0;
                    $total = isset($item['total_price']) ? $this->parseCurrencyAmount($item['total_price']) : ($qty * $price);
                    if ($total <= 0 && $price > 0) {
                        $total = $qty * $price;
                    } elseif ($price <= 0 && $total > 0 && $qty > 0) {
                        $price = $total / $qty;
                    }
                    $cleanedItems[] = [
                        'item_name' => trim((string) ($item['item_name'] ?? '')),
                        'quantity' => $qty > 0 ? $qty : 1,
                        'unit' => trim((string) ($item['unit'] ?? '')),
                        'price' => $price,
                        'total_price' => $total,
                        'notes' => trim((string) ($item['notes'] ?? '')),
                    ];
                }
                $request->merge(['items' => $cleanedItems]);
            }
        }

        $validated = $request->validate([
            'evidence_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sync_amount' => 'nullable|boolean',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'items.required' => 'Minimal satu baris rincian barang harus diisi.',
            'items.min' => 'Minimal satu baris rincian barang harus diisi.',
            'items.*.item_name.required' => 'Nama barang tidak boleh kosong.',
            'receipt_file.max' => 'Ukuran file nota maksimal 20 MB.',
            'receipt_file.mimes' => 'Format file nota harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        $receiptPath = $kasTunai->receipt_file;
        if ($request->hasFile('receipt_file')) {
            if ($receiptPath && Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt_file')->store('kas_tunai', 'public');
        }

        DB::transaction(function () use ($kasTunai, $validated, $receiptPath, $request) {
            $kasTunai->items()->delete();
            $sumTotal = 0;
            foreach ($validated['items'] as $item) {
                $kasTunai->items()->create($item);
                $sumTotal += $item['total_price'];
            }

            $updateData = [
                'receipt_file' => $receiptPath,
                'updated_by' => Auth::id(),
            ];

            if (! empty($validated['evidence_number'])) {
                $updateData['evidence_number'] = $validated['evidence_number'];
            }
            if (! empty($validated['description'])) {
                $updateData['description'] = $validated['description'];
            }
            if ($request->boolean('sync_amount') && $sumTotal > 0) {
                $updateData['amount'] = $sumTotal;
            }

            $kasTunai->update($updateData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Rincian item barang belanja berhasil disimpan.',
            'kas_tunai_id' => $kasTunai->id,
        ]);
    }

    public function belanjaBarangData(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);

        $query = KasTunaiItem::whereHas('kasTunai', function ($q) use ($fundingSource, $request) {
            $q->where('funding_source', $fundingSource);
            if ($request->filled('year') && $request->year !== 'all') {
                $q->whereYear('transaction_date', $request->year);
            }
            if ($request->filled('month')) {
                $q->whereMonth('transaction_date', $request->month);
            }
        })->with(['kasTunai.guru']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date', function ($item) {
                return $item->kasTunai?->transaction_date ? $item->kasTunai->transaction_date->translatedFormat('d M Y') : '-';
            })
            ->addColumn('transaction_date', function ($item) {
                return $item->kasTunai?->transaction_date ? $item->kasTunai->transaction_date->format('d/m/Y') : '-';
            })
            ->editColumn('item_name', function ($item) {
                $html = '<strong>'.htmlspecialchars($item->item_name).'</strong>';
                if ($item->notes) {
                    $html .= '<div class="small text-muted">'.htmlspecialchars($item->notes).'</div>';
                }

                return $html;
            })
            ->addColumn('item_name_formatted', function ($item) {
                $html = '<strong>'.htmlspecialchars($item->item_name).'</strong>';
                if ($item->notes) {
                    $html .= '<div class="small text-muted">'.htmlspecialchars($item->notes).'</div>';
                }

                return $html;
            })
            ->addColumn('qty_unit', function ($item) {
                $qty = (float) $item->quantity;

                return number_format($qty, $qty == intval($qty) ? 0 : 2, ',', '.').' '.($item->unit ?: '');
            })
            ->addColumn('quantity_formatted', function ($item) {
                $qty = (float) $item->quantity;

                return number_format($qty, $qty == intval($qty) ? 0 : 2, ',', '.').' '.($item->unit ?: '');
            })
            ->editColumn('price', function ($item) {
                return 'Rp '.number_format($item->price, 0, ',', '.');
            })
            ->addColumn('price_formatted', function ($item) {
                return 'Rp '.number_format($item->price, 0, ',', '.');
            })
            ->editColumn('total_price', function ($item) {
                return '<strong class="text-primary">Rp '.number_format($item->total_price, 0, ',', '.').'</strong>';
            })
            ->addColumn('total_price_formatted', function ($item) {
                return 'Rp '.number_format($item->total_price, 0, ',', '.');
            })
            ->addColumn('supplier', function ($item) {
                return $item->kasTunai?->description ?: '-';
            })
            ->addColumn('supplier_person', function ($item) {
                return $item->kasTunai?->person_name ?: ($item->kasTunai?->description ?: '-');
            })
            ->addColumn('person_name', function ($item) {
                return $item->kasTunai?->guru ? $item->kasTunai->guru->name : ($item->kasTunai?->person_name ?: '-');
            })
            ->addColumn('responsible_person', function ($item) {
                return $item->kasTunai?->guru ? $item->kasTunai->guru->name : ($item->kasTunai?->person_name ?: '-');
            })
            ->addColumn('evidence_number', function ($item) {
                return $item->kasTunai?->evidence_number ?: '-';
            })
            ->addColumn('actions', function ($item) {
                if (! $item->kas_tunai_id) {
                    return '-';
                }

                return '<button type="button" class="btn btn-info btn-xs btn-view-receipt-items" data-id="'.$item->kas_tunai_id.'" title="Lihat Rincian Belanja Nota"><i class="fas fa-receipt mr-1"></i> Rincian</button>';
            })
            ->addColumn('action', function ($item) {
                if (! $item->kas_tunai_id) {
                    return '-';
                }

                return '<button type="button" class="btn btn-info btn-xs btn-view-receipt-items" data-id="'.$item->kas_tunai_id.'" title="Lihat Rincian Belanja Nota"><i class="fas fa-receipt mr-1"></i> Rincian</button>';
            })
            ->rawColumns(['item_name', 'item_name_formatted', 'total_price', 'action', 'actions'])
            ->make(true);
    }

    public function scanReceipt(Request $request, ReceiptOcrService $ocrService)
    {
        $request->validate([
            'receipt_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:20480',
            'image_base64' => 'nullable|string',
        ]);

        $file = $request->file('receipt_file') ?: $request->input('image_base64');

        if (! $file) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas foto nota tidak ditemukan.',
                'fallback_to_offline' => true,
            ], 422);
        }

        $result = $ocrService->scan($file);

        return response()->json($result);
    }

    /**
     * Parse currency or formatted amount into float.
     * Handles Indonesian currency format (dot thousand separators, comma decimals),
     * standard float format, or raw numeric values.
     */
    private function parseCurrencyAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return 0.0;
        }

        // Strip currency symbols and whitespace
        $str = str_ireplace(['rp', 'rp.', 'idr', ' '], '', $str);

        // Indonesian currency format with dot thousands and comma decimals: "1.500.000,50"
        if (str_contains($str, '.') && str_contains($str, ',')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);

            return (float) preg_replace('/[^0-9.]/', '', $str);
        }

        // Only comma: decimal separator, e.g. "1500,50" or "50,5"
        if (str_contains($str, ',') && ! str_contains($str, '.')) {
            $str = str_replace(',', '.', $str);

            return (float) preg_replace('/[^0-9.]/', '', $str);
        }

        // Multiple dots ("2.000.000") or standard Indonesian dot thousands ("50.000", "6.000")
        if (substr_count($str, '.') > 1 || preg_match('/^\d{1,3}(\.\d{3})+$/', $str)) {
            return (float) str_replace('.', '', $str);
        }

        // Single dot followed by 1 or 2 digits: standard decimal float (e.g. "50.5", "100.25")
        if (preg_match('/^\d+\.\d{1,2}$/', $str)) {
            return (float) $str;
        }

        // Plain numeric string or other formatted numbers: strip non-numeric characters
        return (float) preg_replace('/[^0-9]/', '', $str);
    }
}

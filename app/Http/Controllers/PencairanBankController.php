<?php

namespace App\Http\Controllers;

use App\Exports\PencairanBankExport;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\PencairanBank;
use App\Models\PencairanBankItem;
use App\Models\Perjadin;
use App\Models\PerjadinItem;
use App\Models\SchoolProfile;
use App\Models\UtilityReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PencairanBankController extends Controller
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

    public function index(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $activeFiscalYear = (string) session('fiscal_year', date('Y'));
        $selectedYear = $request->get('year', $activeFiscalYear);

        // Ambil daftar tahun anggaran unik dari letter_date + tahun berjalan
        $bankYears = PencairanBank::where('funding_source', $fundingSource)
            ->whereNotNull('letter_date')
            ->pluck('letter_date')
            ->map(fn ($d) => (int) date('Y', strtotime($d)))
            ->unique();
        $availableYears = $bankYears->push((int) date('Y'))->unique()->sortDesc()->values();

        if ($request->ajax()) {
            $query = PencairanBank::with(['kepalaSekolah', 'bendahara', 'items.rencanaBelanja'])
                ->where('funding_source', $fundingSource)
                ->orderByRaw("CASE WHEN status = 'dicairkan' THEN 1 ELSE 0 END ASC")
                ->orderByRaw("CASE WHEN status = 'dicairkan' THEN cair_date ELSE letter_date END DESC")
                ->orderBy('letter_date', 'desc')
                ->orderBy('id', 'desc');

            if ($selectedYear !== 'all') {
                $query->whereYear('letter_date', $selectedYear);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('btn_expand', function ($row) {
                    return '<button type="button" class="btn btn-xs btn-outline-success btn-toggle-detail px-2 py-0" style="font-size: 0.72rem; border-radius: 4px;" title="Buka / Tutup Rincian"><i class="fas fa-plus"></i></button>';
                })
                ->addColumn('surat_display', function ($row) {
                    $no = '<div class="font-weight-bold text-dark font-tabular">'.e($row->letter_number).'</div>';
                    $tgl = '<small class="text-muted"><i class="far fa-calendar-alt mr-1"></i>'.($row->letter_date ? $row->letter_date->format('d/m/Y') : '-').'</small>';

                    return $no.$tgl;
                })
                ->addColumn('bank_display', function ($row) {
                    $bank = '<div class="font-weight-500 text-dark">'.e($row->bank_name).'</div>';
                    $branch = '<small class="text-muted d-block">'.e($row->bank_branch).'</small>';
                    $rekening = '<small class="text-primary font-tabular">Rek: '.e($row->source_account_number).'</small>';

                    return $bank.$branch.$rekening;
                })
                ->addColumn('rekening_count_display', function ($row) {
                    return '<span class="badge badge-info font-tabular">'.$row->total_accounts.' Rekening</span>';
                })
                ->addColumn('nominal_display', function ($row) {
                    return '<span class="font-tabular font-weight-bold text-dark">Rp '.number_format($row->total_nominal, 0, ',', '.').'</span>';
                })
                ->addColumn('status_display', function ($row) {
                    if ($row->status === 'dicairkan') {
                        $tgl = $row->cair_date ? '<small class="d-block text-muted font-tabular">Tgl: '.$row->cair_date->format('d/m/Y').'</small>' : '';

                        return '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Dicairkan</span>'.$tgl;
                    }

                    return '<span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1"></i>Draft Pengajuan</span>';
                })
                ->addColumn('items_data', function ($row) {
                    return $row->items->map(function ($item) {
                        $desc = $item->description ?: '-';
                        if (str_starts_with($desc, 'Belanja: ')) {
                            $desc = substr($desc, 9);
                        }

                        $noNota = $item->rencanaBelanja ? $item->rencanaBelanja->no_nota : null;
                        $rencanaTitle = $item->rencanaBelanja ? $item->rencanaBelanja->title : null;

                        $typeBadge = '';
                        if ($item->type === 'perjadin') {
                            $typeBadge = '<span class="badge badge-info px-2 py-0" style="font-size:0.7rem;"><i class="fas fa-car mr-1"></i>Perjadin</span>';
                        } elseif ($item->type === 'rencana_nota' || $item->rencanaBelanja) {
                            if ($item->type === 'utilitas' || ($item->rencanaBelanja && $item->rencanaBelanja->type === 'utilitas')) {
                                $typeBadge = '<span class="badge badge-warning text-dark px-2 py-0 font-tabular" style="font-size:0.7rem;"><i class="fas fa-bolt mr-1"></i>Utilitas</span>';
                            } elseif (str_starts_with(strtoupper((string) $item->account_number), 'VA.')) {
                                $typeBadge = '<span class="badge badge-warning text-dark px-2 py-0" style="font-size:0.7rem;"><i class="fas fa-store mr-1"></i>Online</span>';
                            } else {
                                $typeBadge = '<span class="badge badge-primary px-2 py-0 font-tabular" style="font-size:0.7rem;"><i class="fas fa-cart-shopping mr-1"></i>Rencana Belanja</span>';
                            }
                        } elseif ($item->type === 'utilitas') {
                            $typeBadge = '<span class="badge badge-warning text-dark px-2 py-0 font-tabular" style="font-size:0.7rem;"><i class="fas fa-bolt mr-1"></i>Utilitas</span>';
                        } else {
                            $typeBadge = '<span class="badge badge-secondary px-2 py-0" style="font-size:0.7rem;">Belanja</span>';
                        }

                        return [
                            'id' => $item->id,
                            'type' => $item->type,
                            'type_badge' => $typeBadge,
                            'no_nota' => $noNota,
                            'rencana_title' => $rencanaTitle,
                            'recipient_name' => strtoupper((string) $item->recipient_name),
                            'bank_name' => $item->bank_name ?: '-',
                            'account_number' => $item->account_number ?: '-',
                            'amount' => (float) $item->amount,
                            'amount_display' => 'Rp '.number_format((float) $item->amount, 0, ',', '.'),
                            'description' => $desc,
                        ];
                    });
                })
                ->addColumn('action', function ($row) use ($routePrefix) {
                    $btnShow = '<a href="'.route($routePrefix.'.pencairan-bank.show', $row->id).'" class="btn btn-xs btn-info mr-1" title="Detail"><i class="fas fa-eye"></i></a>';
                    $btnExcel = '<a href="'.route($routePrefix.'.pencairan-bank.export-excel', $row->id).'" class="btn btn-xs btn-success mr-1" title="Unduh File Excel (Softcopy Bank Jatim)"><i class="fas fa-file-excel"></i></a>';
                    $btnPrint = '<a href="'.route($routePrefix.'.pencairan-bank.print', $row->id).'" target="_blank" class="btn btn-xs btn-secondary mr-1" title="Cetak 3 Halaman Surat"><i class="fas fa-print"></i></a>';

                    $btnCair = '';
                    if ($row->status !== 'dicairkan') {
                        $btnCair = '<button type="button" class="btn btn-xs btn-success btn-cair mr-1" data-id="'.$row->id.'" data-url="'.route($routePrefix.'.pencairan-bank.cairkan', $row->id).'" title="Konfirmasi Telah Dicairkan Bank"><i class="fas fa-money-bill-wave"></i></button>';
                    }

                    $btnEdit = '<a href="'.route($routePrefix.'.pencairan-bank.edit', $row->id).'" class="btn btn-xs btn-warning mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    $btnDelete = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-url="'.route($routePrefix.'.pencairan-bank.destroy', $row->id).'" data-name="'.e($row->letter_number).'" title="Hapus"><i class="fas fa-trash"></i></button>';

                    return '<div class="d-inline-flex align-items-center gap-1 text-nowrap text-center">'.$btnShow.$btnExcel.$btnPrint.$btnCair.$btnEdit.$btnDelete.'</div>';
                })
                ->rawColumns(['btn_expand', 'surat_display', 'bank_display', 'rekening_count_display', 'nominal_display', 'status_display', 'action'])
                ->make(true);
        }

        $unpaidItems = PerjadinItem::with(['guru.primaryBankAccount', 'perjadin', 'bankAccount'])
            ->whereNull('pencairan_bank_item_id')
            ->where('is_paid', false)
            ->whereHas('perjadin', function ($q) use ($fundingSource, $activeFiscalYear) {
                $q->where('funding_source', $fundingSource);
                $q->where(function ($sub) use ($activeFiscalYear) {
                    $sub->whereYear('departure_date', $activeFiscalYear)
                        ->orWhere(function ($dateQ) use ($activeFiscalYear) {
                            $dateQ->whereNull('departure_date')
                                ->where(function ($fallbackQ) use ($activeFiscalYear) {
                                    $fallbackQ->whereYear('payment_date', $activeFiscalYear)
                                        ->orWhereYear('created_at', $activeFiscalYear);
                                });
                        });
                });
            })
            ->get();

        $unpaidItemsCount = $unpaidItems->count();
        $unpaidTotalAmount = (float) $unpaidItems->sum('net_amount');
        $unpaidPerjadinsCount = $unpaidItems->pluck('perjadin_id')->unique()->count();

        // Ambil data Rencana Belanja (Panduan Belanja) yang belum masuk TNT hanya untuk tahun aktif saja
        $unpaidRencanaNotas = collect([]);
        if ($fundingSource === 'BOSP') {
            $unpaidRencanaQuery = BosRencanaBelanja::with(['items.kertasKerjaItem', 'supplier'])
                ->whereNull('pencairan_bank_item_id')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'realized');
                })
                ->where(function ($q) use ($activeFiscalYear) {
                    $q->where('year', $activeFiscalYear)
                        ->orWhere(function ($sub) use ($activeFiscalYear) {
                            $sub->whereNull('year')
                                ->where(function ($dateQ) use ($activeFiscalYear) {
                                    $dateQ->whereYear('planned_date', $activeFiscalYear)
                                        ->orWhereYear('created_at', $activeFiscalYear);
                                });
                        });
                });

            $unpaidRencanaNotas = $unpaidRencanaQuery
                ->orderBy('planned_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        $unpaidRencanaCount = $unpaidRencanaNotas->count();
        $unpaidRencanaTotalAmount = (float) $unpaidRencanaNotas->sum(function ($r) {
            return (float) $r->total_amount;
        });

        // Pembayaran utilitas kini diproses satu pintu melalui Rencana Belanja (tipe 'utilitas'),
        // sehingga struk utilitas tidak lagi dihitung sebagai tagihan TNT langsung.
        $unpaidUtilityReceipts = collect([]);
        $unpaidUtilityCount = 0;
        $unpaidUtilityTotalAmount = 0.0;

        $totalPendingCount = $unpaidItemsCount + $unpaidRencanaCount;
        $totalPendingAmount = $unpaidTotalAmount + $unpaidRencanaTotalAmount;

        return view('pencairan_bank.index', compact(
            'fundingSource',
            'routePrefix',
            'unpaidItems',
            'unpaidItemsCount',
            'unpaidTotalAmount',
            'unpaidPerjadinsCount',
            'unpaidRencanaNotas',
            'unpaidRencanaCount',
            'unpaidRencanaTotalAmount',
            'unpaidUtilityReceipts',
            'unpaidUtilityCount',
            'unpaidUtilityTotalAmount',
            'totalPendingCount',
            'totalPendingAmount',
            'availableYears',
            'selectedYear',
            'activeFiscalYear'
        ));
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

        // Default Bank credentials based on funding source
        $prefix = strtolower($fundingSource);
        $bankName = $school ? ($school->{$prefix.'_bank_name'} ?? 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR') : 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR';
        $bankBranch = $school ? ($school->{$prefix.'_bank_branch'} ?? 'CABANG KEDIRI') : 'CABANG KEDIRI';
        $bankAddress = $school ? ($school->{$prefix.'_bank_address'} ?? 'Jl. PK. Bangsa No. 122 Kediri') : 'Jl. PK. Bangsa No. 122 Kediri';
        $sourceAccountNum = $school ? ($school->{$prefix.'_account_number'} ?? '1001003336') : '1001003336';
        $sourceAccountName = $school ? ($school->{$prefix.'_account_name'} ?? "20511923 SMKS PGRI KRAS KEDIRI ({$fundingSource})") : "20511923 SMKS PGRI KRAS KEDIRI ({$fundingSource})";

        $gurus = Guru::where('is_active', true)->orderBy('name')->get();
        $activeFiscalYear = (string) session('fiscal_year', date('Y'));

        return view('pencairan_bank.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'defaultKepsekId',
            'defaultBendaharaId',
            'bankName',
            'bankBranch',
            'bankAddress',
            'sourceAccountNum',
            'sourceAccountName',
            'activeFiscalYear'
        ));
    }

    public function store(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
            foreach ($items as $k => $item) {
                if (isset($item['amount']) && is_string($item['amount'])) {
                    $items[$k]['amount'] = (float) str_replace(['.', ','], ['', '.'], $item['amount']);
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'letter_number' => 'required|string|max:100',
            'letter_date' => 'required|date',
            'bank_name' => 'required|string|max:100',
            'bank_branch' => 'required|string|max:100',
            'bank_address' => 'required|string|max:255',
            'source_account_number' => 'required|string|max:50',
            'source_account_name' => 'required|string|max:150',
            'kepala_sekolah_id' => 'required|exists:gurus,id',
            'bendahara_id' => 'required|exists:gurus,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string',
            'items.*.recipient_name' => 'nullable|string|max:150',
            'items.*.bank_name' => 'nullable|string|max:100',
            'items.*.account_number' => 'required|string|max:50',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.perjadin_item_id' => 'nullable|exists:perjadin_items,id',
            'items.*.bos_rencana_belanja_id' => 'nullable|exists:bos_rencana_belanjas,id',
            'items.*.utility_receipt_id' => 'nullable|exists:utility_receipts,id',
        ]);

        $totalNominal = collect($validated['items'])->sum('amount');
        $totalAccounts = count($validated['items']);

        $pencairanBank = DB::transaction(function () use ($validated, $fundingSource, $totalNominal, $totalAccounts) {
            $pencairanBank = PencairanBank::create([
                'funding_source' => $fundingSource,
                'letter_number' => $validated['letter_number'],
                'letter_date' => $validated['letter_date'],
                'bank_name' => $validated['bank_name'],
                'bank_branch' => $validated['bank_branch'],
                'bank_address' => $validated['bank_address'],
                'source_account_number' => $validated['source_account_number'],
                'source_account_name' => $validated['source_account_name'],
                'kepala_sekolah_id' => $validated['kepala_sekolah_id'],
                'bendahara_id' => $validated['bendahara_id'],
                'total_nominal' => $totalNominal,
                'total_accounts' => $totalAccounts,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $bankItem = PencairanBankItem::create([
                    'pencairan_bank_id' => $pencairanBank->id,
                    'type' => $itemData['type'] ?? 'perjadin',
                    'recipient_name' => $itemData['recipient_name'] ?? '',
                    'bank_name' => $itemData['bank_name'] ?? null,
                    'account_number' => $itemData['account_number'],
                    'amount' => $itemData['amount'],
                    'description' => $itemData['description'] ?? null,
                ]);

                // If pulled from PerjadinItem, link it
                if (! empty($itemData['perjadin_item_id'])) {
                    $perjadinItem = PerjadinItem::find($itemData['perjadin_item_id']);
                    if ($perjadinItem) {
                        $perjadinItem->update([
                            'pencairan_bank_item_id' => $bankItem->id,
                        ]);
                        $perjadinItem->perjadin()->update([
                            'status' => 'diajukan_ke_bank',
                        ]);
                    }
                }

                // If pulled from BosRencanaBelanja, link it
                if (! empty($itemData['bos_rencana_belanja_id'])) {
                    $rencanaBelanja = BosRencanaBelanja::find($itemData['bos_rencana_belanja_id']);
                    if ($rencanaBelanja) {
                        $rencanaBelanja->update([
                            'pencairan_bank_item_id' => $bankItem->id,
                            'bank_name' => $itemData['bank_name'] ?? $rencanaBelanja->bank_name,
                            'account_number' => $itemData['account_number'] ?? $rencanaBelanja->account_number,
                            'status' => 'diajukan_ke_bank',
                        ]);
                    }
                }

                // If pulled from UtilityReceipt, link it
                if (! empty($itemData['utility_receipt_id'])) {
                    $receipt = UtilityReceipt::find($itemData['utility_receipt_id']);
                    if ($receipt) {
                        $receipt->update([
                            'pencairan_bank_item_id' => $bankItem->id,
                        ]);
                    }
                }
            }

            return $pencairanBank;
        });

        $routePrefix = $this->getRoutePrefix($fundingSource);

        return redirect()->route($routePrefix.'.pencairan-bank.show', $pencairanBank->id)
            ->with('success', "Surat TNT '{$pencairanBank->letter_number}' berhasil dibuat.");
    }

    public function show(Request $request, PencairanBank $pencairanBank)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $pencairanBank->load(['items.perjadinItem.perjadin', 'items.rencanaBelanja', 'items.utilityReceipt.profile', 'items.utilityReceipt.kertasKerjaItem', 'kepalaSekolah', 'bendahara']);

        return view('pencairan_bank.show', compact('pencairanBank', 'fundingSource', 'routePrefix'));
    }

    public function edit(Request $request, PencairanBank $pencairanBank)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $pencairanBank->load(['items.perjadinItem', 'items.rencanaBelanja', 'items.utilityReceipt.profile', 'items.utilityReceipt.kertasKerjaItem', 'kepalaSekolah', 'bendahara']);
        $gurus = Guru::where('is_active', true)->orderBy('name')->get();

        return view('pencairan_bank.edit', compact('pencairanBank', 'fundingSource', 'gurus', 'routePrefix'));
    }

    public function update(Request $request, PencairanBank $pencairanBank)
    {
        if (! $pencairanBank->exists) {
            $pencairanBank = PencairanBank::findOrFail($request->route('pencairanBank') ?? $request->route('pencairan_bank'));
        }

        $fundingSource = $this->resolveFundingSource($request);

        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
            foreach ($items as $k => $item) {
                if (isset($item['amount']) && is_string($item['amount'])) {
                    $items[$k]['amount'] = (float) str_replace(['.', ','], ['', '.'], $item['amount']);
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'letter_number' => 'required|string|max:100',
            'letter_date' => 'required|date',
            'bank_name' => 'required|string|max:100',
            'bank_branch' => 'required|string|max:100',
            'bank_address' => 'required|string|max:255',
            'source_account_number' => 'required|string|max:50',
            'source_account_name' => 'required|string|max:150',
            'kepala_sekolah_id' => 'required|exists:gurus,id',
            'bendahara_id' => 'required|exists:gurus,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string',
            'items.*.recipient_name' => 'nullable|string|max:150',
            'items.*.bank_name' => 'nullable|string|max:100',
            'items.*.account_number' => 'required|string|max:50',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.perjadin_item_id' => 'nullable|exists:perjadin_items,id',
            'items.*.bos_rencana_belanja_id' => 'nullable|exists:bos_rencana_belanjas,id',
            'items.*.utility_receipt_id' => 'nullable|exists:utility_receipts,id',
        ]);

        $totalNominal = collect($validated['items'])->sum('amount');
        $totalAccounts = count($validated['items']);

        DB::transaction(function () use ($pencairanBank, $validated, $totalNominal, $totalAccounts) {
            $pencairanBank->update([
                'letter_number' => $validated['letter_number'],
                'letter_date' => $validated['letter_date'],
                'bank_name' => $validated['bank_name'],
                'bank_branch' => $validated['bank_branch'],
                'bank_address' => $validated['bank_address'],
                'source_account_number' => $validated['source_account_number'],
                'source_account_name' => $validated['source_account_name'],
                'kepala_sekolah_id' => $validated['kepala_sekolah_id'],
                'bendahara_id' => $validated['bendahara_id'],
                'total_nominal' => $totalNominal,
                'total_accounts' => $totalAccounts,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Unlink previous perjadin, rencana belanja, and utility items before replacing
            foreach ($pencairanBank->items as $oldItem) {
                if ($oldItem->perjadinItem) {
                    $oldItem->perjadinItem->update(['pencairan_bank_item_id' => null]);
                }
                if ($oldItem->rencanaBelanja) {
                    $oldItem->rencanaBelanja->update([
                        'pencairan_bank_item_id' => null,
                        'status' => 'draft',
                    ]);
                }
                if ($oldItem->utilityReceipt) {
                    $oldItem->utilityReceipt->update(['pencairan_bank_item_id' => null]);
                }
            }
            $pencairanBank->items()->delete();

            // Recreate items
            foreach ($validated['items'] as $itemData) {
                $bankItem = PencairanBankItem::create([
                    'pencairan_bank_id' => $pencairanBank->id,
                    'type' => $itemData['type'] ?? 'perjadin',
                    'recipient_name' => $itemData['recipient_name'] ?? '',
                    'bank_name' => $itemData['bank_name'] ?? null,
                    'account_number' => $itemData['account_number'],
                    'amount' => $itemData['amount'],
                    'description' => $itemData['description'] ?? null,
                ]);

                if (! empty($itemData['perjadin_item_id'])) {
                    $perjadinItem = PerjadinItem::find($itemData['perjadin_item_id']);
                    if ($perjadinItem) {
                        $isAlreadyDicairkan = $pencairanBank->status === 'dicairkan';
                        $perjadinItem->update([
                            'pencairan_bank_item_id' => $bankItem->id,
                            'is_paid' => $isAlreadyDicairkan ? true : $perjadinItem->is_paid,
                        ]);
                        $perjadinItem->perjadin()->update([
                            'status' => $isAlreadyDicairkan ? 'lunas' : 'diajukan_ke_bank',
                        ]);
                    }
                }

                if (! empty($itemData['bos_rencana_belanja_id'])) {
                    $rencanaBelanja = BosRencanaBelanja::find($itemData['bos_rencana_belanja_id']);
                    if ($rencanaBelanja) {
                        $isAlreadyDicairkan = $pencairanBank->status === 'dicairkan';
                        $rencanaBelanja->update([
                            'pencairan_bank_item_id' => $bankItem->id,
                            'bank_name' => $itemData['bank_name'] ?? $rencanaBelanja->bank_name,
                            'account_number' => $itemData['account_number'] ?? $rencanaBelanja->account_number,
                            'status' => $isAlreadyDicairkan ? 'realized' : 'diajukan_ke_bank',
                        ]);
                    }
                }

                if (! empty($itemData['utility_receipt_id'])) {
                    $receipt = UtilityReceipt::find($itemData['utility_receipt_id']);
                    if ($receipt) {
                        $receipt->update([
                            'pencairan_bank_item_id' => $bankItem->id,
                        ]);
                    }
                }
            }
        });

        $routePrefix = $this->getRoutePrefix($fundingSource);

        return redirect()->route($routePrefix.'.pencairan-bank.show', $pencairanBank->id)
            ->with('success', "Surat TNT '{$pencairanBank->letter_number}' berhasil diperbarui.");
    }

    public function confirmCair(Request $request, PencairanBank $pencairanBank)
    {
        $validated = $request->validate([
            'cair_date' => 'required|date',
            'bku_number' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($pencairanBank, $validated) {
            $pencairanBank->update([
                'status' => 'dicairkan',
                'cair_date' => $validated['cair_date'],
            ]);

            // Mark all linked items as paid and update their parent models
            $processedPerjadins = [];
            foreach ($pencairanBank->items as $bankItem) {
                // 1. Perjadin items
                if ($bankItem->perjadinItem) {
                    $pItem = $bankItem->perjadinItem;
                    $pItem->update(['is_paid' => true]);

                    $perjadin = $pItem->perjadin;
                    if ($perjadin) {
                        $updateData = [
                            'status' => 'lunas',
                            'payment_date' => $validated['cair_date'],
                        ];
                        if (! empty($validated['bku_number']) && empty($perjadin->bku_number)) {
                            $updateData['bku_number'] = $validated['bku_number'];
                        }
                        $perjadin->update($updateData);
                        $processedPerjadins[$perjadin->id] = $perjadin;
                    }
                }

                // 2. Rencana Belanja / Nota items -> otomatis realisasi ke bos_realisasies
                if ($bankItem->rencanaBelanja) {
                    $rencana = $bankItem->rencanaBelanja;
                    if ($rencana->status !== 'realized') {
                        $noNota = $rencana->no_nota ?: BosRencanaBelanja::generateNoNota($rencana->year, $rencana->month);
                        if (! $rencana->no_nota) {
                            $rencana->no_nota = $noNota;
                        }

                        $year = $rencana->year ?: (int) date('Y', strtotime($validated['cair_date']));
                        $month = $rencana->month ?: date('F', strtotime($validated['cair_date']));

                        foreach ($rencana->items as $rItem) {
                            $note = 'Direalisasikan otomatis via TNT: '.$pencairanBank->letter_number;
                            $kk = $rItem->kertasKerjaItem;
                            if ($kk) {
                                $prevRealized = (float) BosRealisasi::where('bos_kertas_kerja_item_id', $kk->id)->sum('volume');
                                if (($prevRealized + (float) $rItem->volume_plan) > ((float) $kk->volume + 0.001)) {
                                    $note .= ' [PERHATIAN: Realisasi melebihi volume anggaran KK]';
                                }
                            }

                            BosRealisasi::create([
                                'bos_kertas_kerja_item_id' => $rItem->bos_kertas_kerja_item_id,
                                'no_transaksi' => $validated['bku_number'] ?? null,
                                'no_nota' => $noNota,
                                'bos_rencana_belanja_id' => $rencana->id,
                                'year' => $year,
                                'month' => $month,
                                'transaction_date' => $validated['cair_date'],
                                'receipt_date' => $validated['cair_date'],
                                'volume' => $rItem->volume_plan,
                                'unit' => $rItem->unit,
                                'price' => $rItem->price,
                                'total_amount' => $rItem->total_amount,
                                'notes' => $note,
                                'created_by' => Auth::id() ?? 1,
                            ]);
                        }

                        $rencana->update([
                            'no_nota' => $noNota,
                            'status' => 'realized',
                        ]);

                        // Jika rencana belanja membundel struk utilitas, tandai lunas juga struknya
                        if ($rencana->utilityReceipts()->exists()) {
                            $rencana->utilityReceipts()->update([
                                'is_paid' => true,
                                'payment_date' => $validated['cair_date'],
                                'bku_number' => $validated['bku_number'] ?? null,
                            ]);
                        }
                    }
                }

                // 3. Utilitas items -> update status receipt & otomatis realisasi ke bos_realisasies jika terhubung ke Kertas Kerja (budgeted)
                if ($bankItem->utilityReceipt) {
                    $receipt = $bankItem->utilityReceipt;
                    $receipt->update([
                        'is_paid' => true,
                        'payment_date' => $validated['cair_date'],
                        'bku_number' => $validated['bku_number'] ?? $receipt->bku_number,
                    ]);

                    // Jika memiliki relasi dengan kertas kerja BOS (artinya bukan Non-Anggaran seperti SMP PGRI)
                    if ($receipt->bos_kertas_kerja_item_id) {
                        $alreadyRealized = BosRealisasi::where('utility_receipt_id', $receipt->id)->exists();
                        if (! $alreadyRealized) {
                            $receipt->load('kertasKerjaItem', 'profile');
                            $kkItem = $receipt->kertasKerjaItem;
                            $profileName = $receipt->profile?->name ?? 'Utilitas';

                            $cairTimestamp = strtotime($validated['cair_date']);
                            $indonesianMonths = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ];
                            $monthNum = (int) date('n', $cairTimestamp);
                            $monthName = $receipt->billing_month ?: ($indonesianMonths[$monthNum] ?? date('F', $cairTimestamp));
                            $year = $receipt->fiscal_year ?: (int) date('Y', $cairTimestamp);

                            $bkuNo = $receipt->bku_number ?: ($validated['bku_number'] ?? null);
                            $noNota = $receipt->receipt_number ?: ('UTIL-'.str_pad($receipt->id, 4, '0', STR_PAD_LEFT));

                            BosRealisasi::create([
                                'bos_kertas_kerja_item_id' => $receipt->bos_kertas_kerja_item_id,
                                'no_transaksi' => $bkuNo,
                                'no_nota' => $noNota,
                                'bos_rencana_belanja_id' => null,
                                'perjadin_id' => null,
                                'utility_receipt_id' => $receipt->id,
                                'year' => $year,
                                'month' => $monthName,
                                'transaction_date' => $validated['cair_date'],
                                'receipt_date' => $receipt->transaction_date ? $receipt->transaction_date->format('Y-m-d') : $validated['cair_date'],
                                'volume' => 1,
                                'unit' => $kkItem ? $kkItem->unit : 'Bulan',
                                'price' => $bankItem->amount,
                                'total_amount' => $bankItem->amount,
                                'notes' => 'Realisasi otomatis Tagihan Utilitas ('.$profileName.' - '.$monthName.') via TNT: '.$pencairanBank->letter_number,
                                'created_by' => Auth::id() ?? 1,
                            ]);
                        }
                    }
                }
            }

            // Realisasikan Perjadin ke bos_realisasies jika terhubung dengan Kertas Kerja
            foreach ($processedPerjadins as $perjadin) {
                if ($perjadin->bos_kertas_kerja_item_id) {
                    $alreadyRealized = BosRealisasi::where('perjadin_id', $perjadin->id)->exists();
                    if (! $alreadyRealized) {
                        $perjadin->load('kertasKerjaItem');
                        $kkItem = $perjadin->kertasKerjaItem;
                        $countPeserta = $perjadin->items()->count();
                        $totalTransport = (float) $perjadin->items()->sum('net_amount');
                        $unitPrice = $countPeserta > 0 ? ($totalTransport / $countPeserta) : ($kkItem ? (float) $kkItem->price : 0);
                        $unit = $kkItem ? $kkItem->unit : 'oh';

                        $cairTimestamp = strtotime($validated['cair_date']);
                        $indonesianMonths = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ];
                        $monthNum = (int) date('n', $cairTimestamp);
                        $monthName = $indonesianMonths[$monthNum] ?? date('F', $cairTimestamp);
                        $year = (int) date('Y', $cairTimestamp);

                        $bkuNo = $perjadin->bku_number ?: ($validated['bku_number'] ?? null);
                        $noNota = $perjadin->bku_number ?: ('SPPD-'.str_pad($perjadin->id, 4, '0', STR_PAD_LEFT));

                        BosRealisasi::create([
                            'bos_kertas_kerja_item_id' => $perjadin->bos_kertas_kerja_item_id,
                            'no_transaksi' => $bkuNo,
                            'no_nota' => $noNota,
                            'bos_rencana_belanja_id' => null,
                            'perjadin_id' => $perjadin->id,
                            'year' => $year,
                            'month' => $monthName,
                            'transaction_date' => $validated['cair_date'],
                            'receipt_date' => $validated['cair_date'],
                            'volume' => $countPeserta,
                            'unit' => $unit,
                            'price' => $unitPrice,
                            'total_amount' => $totalTransport,
                            'notes' => 'Realisasi otomatis Perjalanan Dinas ke '.$perjadin->destination_name.' ('.$countPeserta.' org) via TNT: '.$pencairanBank->letter_number,
                            'created_by' => Auth::id() ?? 1,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'message' => "Surat TNT '{$pencairanBank->letter_number}' berhasil ditandai telah dicairkan dan rencana belanja/perjadin/utilitas otomatis direalisasikan.",
        ]);
    }

    public function destroy(Request $request, PencairanBank $pencairanBank)
    {
        $num = $pencairanBank->letter_number;

        DB::transaction(function () use ($pencairanBank) {
            // Unlink any perjadin, rencana belanja, and utility items
            foreach ($pencairanBank->items as $item) {
                if ($item->perjadinItem) {
                    $item->perjadinItem->update(['pencairan_bank_item_id' => null, 'is_paid' => false]);
                    $item->perjadinItem->perjadin()->update(['status' => 'draft']);
                    if ($item->perjadinItem->perjadin_id) {
                        BosRealisasi::where('perjadin_id', $item->perjadinItem->perjadin_id)->delete();
                    }
                }
                if ($item->rencanaBelanja) {
                    $item->rencanaBelanja->update([
                        'pencairan_bank_item_id' => null,
                        'status' => 'draft',
                    ]);
                    BosRealisasi::where('bos_rencana_belanja_id', $item->rencanaBelanja->id)->delete();
                }
                if ($item->utilityReceipt) {
                    $item->utilityReceipt->update([
                        'pencairan_bank_item_id' => null,
                        'is_paid' => false,
                        'payment_date' => null,
                        'bku_number' => null,
                    ]);
                    BosRealisasi::where('utility_receipt_id', $item->utilityReceipt->id)->delete();
                }
            }

            $pencairanBank->items()->delete();
            $pencairanBank->delete();
        });

        return response()->json([
            'message' => "Surat TNT '{$num}' berhasil dihapus.",
        ]);
    }

    public function printSurat(Request $request, PencairanBank $pencairanBank)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $routePrefix = $this->getRoutePrefix($fundingSource);
        $school = SchoolProfile::first();
        $pencairanBank->load([
            'items.rencanaBelanja.supplier.bankAccounts',
            'items.perjadinItem.bankAccount',
            'items.perjadinItem.guru.primaryBankAccount',
            'kepalaSekolah',
            'bendahara',
        ]);

        return view('pencairan_bank.print_surat', compact('pencairanBank', 'school', 'fundingSource', 'routePrefix'));
    }

    public function exportExcel(Request $request, PencairanBank $pencairanBank)
    {
        // Sanitize letter_number: replace / with _, dots/commas with -, spaces with _
        // e.g. "400.3.8.7/083.1/SMK PGRI Kras/2026" -> "400-3-8-7_083-1_SMK_PGRI_Kras_2026"
        $cleanLetterNum = $pencairanBank->letter_number ?? '';
        $cleanLetterNum = str_replace('/', '_', $cleanLetterNum);   // / -> _
        $cleanLetterNum = str_replace(['.', ','], '-', $cleanLetterNum); // . -> -
        $cleanLetterNum = str_replace(' ', '_', $cleanLetterNum);   // space -> _
        $cleanLetterNum = preg_replace('/[^A-Za-z0-9_\-]/', '', $cleanLetterNum); // strip remaining special chars
        $cleanLetterNum = preg_replace('/_+/', '_', $cleanLetterNum); // collapse multiple _
        $cleanLetterNum = trim($cleanLetterNum, '_-');

        $fileName = "Daftar_Transfer_Bank_Jatim_TNT_{$cleanLetterNum}.xlsx";

        return Excel::download(new PencairanBankExport($pencairanBank), $fileName);
    }

    public function getUnpaidPerjadinItems(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);
        $activeFiscalYear = (string) session('fiscal_year', date('Y'));
        $targetYear = $request->get('year', $activeFiscalYear);

        $items = PerjadinItem::with(['guru.primaryBankAccount', 'perjadin', 'bankAccount'])
            ->whereNull('pencairan_bank_item_id')
            ->where('is_paid', false)
            ->whereHas('perjadin', function ($q) use ($fundingSource, $targetYear) {
                $q->where('funding_source', $fundingSource);
                if ($targetYear && $targetYear !== 'all') {
                    $q->where(function ($sub) use ($targetYear) {
                        $sub->whereYear('departure_date', $targetYear)
                            ->orWhere(function ($dateQ) use ($targetYear) {
                                $dateQ->whereNull('departure_date')
                                    ->where(function ($fallbackQ) use ($targetYear) {
                                        $fallbackQ->whereYear('payment_date', $targetYear)
                                            ->orWhereYear('created_at', $targetYear);
                                    });
                            });
                    });
                }
            })
            ->get()
            ->map(function ($item) {
                $bank = $item->bankAccount ?? ($item->guru ? $item->guru->primaryBankAccount : null);
                $bankName = $bank ? $bank->bank_name : 'Bank Jatim';
                $accNum = $bank ? $bank->account_number : '-';
                $accountHolder = $bank?->account_holder ?: ($item->guru ? $item->guru->name : '-');

                return [
                    'id' => $item->id,
                    'perjadin_id' => $item->perjadin_id,
                    'tujuan' => $item->perjadin->destination_name ?? '-',
                    'tanggal' => $item->perjadin->departure_date ? $item->perjadin->departure_date->format('d/m/Y') : '-',
                    'maksud' => $item->perjadin->activity_purpose ?? '-',
                    'guru_id' => $item->guru_id,
                    'guru_name' => $item->guru ? $item->guru->full_name_with_title : '-',
                    'recipient_name' => $accountHolder,
                    'account_holder' => $accountHolder,
                    'bank_name' => $bankName,
                    'account_number' => $accNum,
                    'net_amount' => (float) $item->net_amount,
                    'description' => 'Perjadin',
                ];
            });

        return response()->json($items);
    }

    public function getUnpaidRencanaNotas(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($fundingSource !== 'BOSP') {
            return response()->json([]);
        }

        $activeFiscalYear = (string) session('fiscal_year', date('Y'));
        $targetYear = $request->get('year', $activeFiscalYear);

        $withRelations = ['items.kertasKerjaItem', 'supplier.bankAccounts', 'guru.primaryBankAccount'];
        if (Schema::hasTable('utility_receipts')) {
            $withRelations[] = 'utilityReceipts';
        }

        $notasQuery = BosRencanaBelanja::with($withRelations)
            ->whereNull('pencairan_bank_item_id')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'realized');
            });

        if ($targetYear && $targetYear !== 'all') {
            $notasQuery->where(function ($q) use ($targetYear) {
                $q->where('year', $targetYear)
                    ->orWhere(function ($sub) use ($targetYear) {
                        $sub->whereNull('year')
                            ->where(function ($dateQ) use ($targetYear) {
                                $dateQ->whereYear('planned_date', $targetYear)
                                    ->orWhereYear('created_at', $targetYear);
                            });
                    });
            });
        }

        $notas = $notasQuery
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($nota) {
                $supplier = $nota->supplier_name ?: $nota->title;
                $recipientName = '';
                $isOnline = false;
                $marketplaceName = '';

                if ($nota->type === 'utilitas' || $nota->guru_id) {
                    $supplier = $nota->guru ? $nota->guru->name : ($nota->supplier_name ?: 'Petugas Utilitas');
                    $recipientName = $supplier;
                    $isOnline = false;
                    $marketplaceName = 'Utilitas';
                } elseif ($nota->supplier) {
                    if ($nota->supplier->type === 'online') {
                        $isOnline = true;
                        $marketplaceName = $nota->supplier->marketplace_name ?: 'Siplah Toko Ladang';
                        // Ambil nama pemilik rekening supplier online atau default PT. LADANG KARYA HUSADA
                        $recipientName = $nota->supplier->account_holder ?: 'PT. LADANG KARYA HUSADA';
                    } else {
                        // Ambil nama pemilik rekening (account_holder) yang sesuai rekening rencana belanja
                        $accHolder = null;
                        if ($nota->account_number && $nota->supplier->relationLoaded('bankAccounts')) {
                            $cleanNotaAcc = preg_replace('/[^0-9]/', '', $nota->account_number);
                            $matchedAcc = $nota->supplier->bankAccounts->first(function ($acc) use ($cleanNotaAcc) {
                                return $cleanNotaAcc && preg_replace('/[^0-9]/', '', (string) $acc->account_number) === $cleanNotaAcc;
                            });
                            $accHolder = $matchedAcc?->account_holder;
                        }

                        if (! $accHolder) {
                            $accHolder = $nota->supplier->account_holder;
                        }

                        if (! $accHolder && $nota->supplier->relationLoaded('bankAccounts')) {
                            $accHolder = $nota->supplier->bankAccounts->firstWhere('is_primary', true)?->account_holder
                                ?? $nota->supplier->bankAccounts->first()?->account_holder;
                        }

                        $recipientName = $accHolder ?: ($nota->supplier->name ?: ($nota->supplier_name ?: ''));
                    }
                } else {
                    $recipientName = $nota->supplier_name ?: '';
                }

                $itemsSummaryList = [];
                $hasConflict = false;
                $conflictMessages = [];

                foreach ($nota->items as $rItem) {
                    $kk = $rItem->kertasKerjaItem;
                    $itemName = $kk ? $kk->description : 'Barang';
                    $itemsSummaryList[] = $itemName.' ('.$rItem->volume_plan.' '.$rItem->unit.')';

                    if ($kk) {
                        $allKkIds = BosKertasKerjaItem::where('year', $kk->year)
                            ->where('account_code', $kk->account_code)
                            ->where('kegiatan', $kk->kegiatan)
                            ->where('description', $kk->description)
                            ->where('unit', $kk->unit)
                            ->where('price', $kk->price)
                            ->whereHas('file', function ($f) {
                                $f->where('is_active', true);
                            })
                            ->pluck('id');

                        $totalBudget = (float) BosKertasKerjaItem::whereIn('id', $allKkIds)->sum('volume');
                        $totalRealized = (float) BosRealisasi::whereIn('bos_kertas_kerja_item_id', $allKkIds)->sum('volume');
                        $remainingBudget = max(0, $totalBudget - $totalRealized);

                        if ($totalRealized >= ($totalBudget - 0.001)) {
                            $hasConflict = true;
                            $conflictMessages[] = "Barang [{$itemName}] sudah direalisasikan penuh di BKU ({$totalRealized}/{$totalBudget} {$kk->unit}).";
                        } elseif ($rItem->volume_plan > ($remainingBudget + 0.001)) {
                            $hasConflict = true;
                            $conflictMessages[] = "Volume rencana [{$itemName}] ({$rItem->volume_plan} {$rItem->unit}) melebihi sisa anggaran ({$remainingBudget} {$kk->unit}).";
                        }
                    }
                }

                $itemsCount = $nota->items->count();
                $totalAmount = (float) $nota->total_amount;

                return [
                    'id' => $nota->id,
                    'no_nota' => $nota->no_nota ?: '-',
                    'title' => $nota->title,
                    'supplier_name' => $supplier,
                    'recipient_name' => $recipientName,
                    'is_online' => $isOnline,
                    'marketplace_name' => $marketplaceName,
                    'bank_name' => $nota->bank_name ?: ($nota->guru?->primaryBankAccount?->bank_name ?? ($nota->supplier ? $nota->supplier->bank_name : 'Bank Jatim')),
                    'account_number' => $nota->account_number ?: ($nota->guru?->primaryBankAccount?->account_number ?? ($nota->supplier ? $nota->supplier->account_number : '')),
                    'planned_date' => $nota->planned_date ? $nota->planned_date->format('d/m/Y') : '-',
                    'items_count' => $itemsCount,
                    'items_summary' => implode(', ', $itemsSummaryList),
                    'has_conflict' => $hasConflict,
                    'conflict_messages' => $conflictMessages,
                    'total_amount' => $totalAmount,
                    'type' => $nota->type ?: 'belanja',
                    'description' => $nota->title,
                ];
            });

        return response()->json($notas);
    }

    public function getUnpaidUtilityReceipts(Request $request)
    {
        $fundingSource = $this->resolveFundingSource($request);

        if ($fundingSource !== 'BOSP') {
            return response()->json([]);
        }

        $activeFiscalYear = (string) session('fiscal_year', date('Y'));
        $targetYear = $request->get('year', $activeFiscalYear);

        $receiptsQuery = UtilityReceipt::with([
            'profile.defaultReimburseGuru.primaryBankAccount',
            'kertasKerjaItem',
        ])
            ->whereNull('pencairan_bank_item_id')
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
            });

        if ($targetYear && $targetYear !== 'all') {
            $receiptsQuery->where(function ($q) use ($targetYear) {
                $q->where('fiscal_year', $targetYear)
                    ->orWhere(function ($sub) use ($targetYear) {
                        $sub->whereNull('fiscal_year')
                            ->where(function ($dateQ) use ($targetYear) {
                                $dateQ->whereYear('transaction_date', $targetYear)
                                    ->orWhereYear('created_at', $targetYear);
                            });
                    });
            });
        }

        $bendahara = GuruJabatan::getPejabatByDate('bendahara_bosp', date('Y-m-d'))?->guru;
        $defaultBank = $bendahara?->primaryBankAccount;

        $items = $receiptsQuery
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($receipt) use ($bendahara, $defaultBank) {
                // Tentukan penerima reimburse:
                // 1. Guru default dari Master Profil Utilitas
                // 2. Bendahara BOSP
                $guru = $receipt->profile?->defaultReimburseGuru ?? $bendahara;
                $bank = $guru?->primaryBankAccount ?? $defaultBank;

                $bankName = $bank ? $bank->bank_name : 'Bank Jatim';
                $accNum = $bank ? $bank->account_number : '-';
                $accountHolder = $bank?->account_holder ?: ($guru ? $guru->name : '-');

                $profileName = $receipt->profile ? $receipt->profile->name : 'Tagihan Utilitas';
                $period = trim(($receipt->billing_month ? $receipt->billing_month.' ' : '').($receipt->fiscal_year ?: ''));
                $desc = 'Reimburse Tagihan '.trim($profileName.($period ? ' '.$period : ''));

                return [
                    'id' => $receipt->id,
                    'receipt_id' => $receipt->id,
                    'profile_name' => $profileName,
                    'is_budgeted' => (bool) ($receipt->profile?->is_budgeted ?? true),
                    'has_kertas_kerja' => (bool) $receipt->bos_kertas_kerja_item_id,
                    'kertas_kerja_desc' => $receipt->kertasKerjaItem?->description,
                    'transaction_date' => $receipt->transaction_date ? $receipt->transaction_date->format('d/m/Y') : '-',
                    'billing_month' => $receipt->billing_month ?? '-',
                    'fiscal_year' => $receipt->fiscal_year ?? '-',
                    'guru_id' => $guru?->id,
                    'guru_name' => $guru ? $guru->full_name_with_title : '-',
                    'recipient_name' => $accountHolder,
                    'account_holder' => $accountHolder,
                    'bank_name' => $bankName,
                    'account_number' => $accNum,
                    'total_amount' => (float) $receipt->total_amount,
                    'rounded_amount' => (float) $receipt->total_amount,
                    'description' => $desc,
                ];
            });

        return response()->json($items);
    }
}

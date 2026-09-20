<?php

namespace App\Http\Controllers;

use App\Models\BosKertasKerjaItem;
use App\Models\BosPenerimaan;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class BosRealisasiController extends Controller
{
    private $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request)
    {
        $selectedYear = $request->input('year', (string) session('fiscal_year', date('Y')));

        if ($request->ajax()) {
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            if ($isSqlite) {
                $monthMapping = "CASE strftime('%m', transaction_date)
                    WHEN '01' THEN 'Januari'
                    WHEN '02' THEN 'Februari'
                    WHEN '03' THEN 'Maret'
                    WHEN '04' THEN 'April'
                    WHEN '05' THEN 'Mei'
                    WHEN '06' THEN 'Juni'
                    WHEN '07' THEN 'Juli'
                    WHEN '08' THEN 'Agustus'
                    WHEN '09' THEN 'September'
                    WHEN '10' THEN 'Oktober'
                    WHEN '11' THEN 'November'
                    WHEN '12' THEN 'Desember'
                END";
                $orderTypePenerimaanFirst = "CASE WHEN row_type = 'penerimaan' THEN 1 ELSE 2 END";
                $orderTypeBelanjaFirst = "CASE WHEN row_type = 'belanja' THEN 1 ELSE 2 END";
            } else {
                $monthMapping = "CASE MONTH(transaction_date)
                    WHEN 1 THEN 'Januari'
                    WHEN 2 THEN 'Februari'
                    WHEN 3 THEN 'Maret'
                    WHEN 4 THEN 'April'
                    WHEN 5 THEN 'Mei'
                    WHEN 6 THEN 'Juni'
                    WHEN 7 THEN 'Juli'
                    WHEN 8 THEN 'Agustus'
                    WHEN 9 THEN 'September'
                    WHEN 10 THEN 'Oktober'
                    WHEN 11 THEN 'November'
                    WHEN 12 THEN 'Desember'
                END";
                $orderTypePenerimaanFirst = "FIELD(row_type, 'penerimaan', 'belanja')";
                $orderTypeBelanjaFirst = "FIELD(row_type, 'belanja', 'penerimaan')";
            }

            $pQuery = DB::table('bos_penerimaans')
                ->select([
                    DB::raw("'penerimaan' as row_type"),
                    'id as id',
                    'id as row_id',
                    DB::raw('reference_number as no_nota'),
                    DB::raw('reference_number as no_transaksi'),
                    'transaction_date',
                    'transaction_date as receipt_date',
                    DB::raw("{$monthMapping} as month"),
                    'year',
                    DB::raw('NULL as bos_kertas_kerja_item_id'),
                    DB::raw('NULL as account_code'),
                    'description as uraian',
                    'description as description',
                    DB::raw('NULL as bulan_perencanaan'),
                    DB::raw('NULL as volume'),
                    DB::raw('NULL as unit'),
                    DB::raw('NULL as price'),
                    'amount as nominal_masuk',
                    DB::raw('0.00 as nominal_keluar'),
                    'amount as amount_signed',
                    DB::raw("'Penerimaan Kas Bank' as kegiatan"),
                    'notes',
                    'category as penerimaan_category',
                    'receipt_file',
                    'created_at',
                ])
                ->whereNull('deleted_at')
                ->when($selectedYear && $selectedYear !== 'all', function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear);
                });

            $hasRencanaTable = Schema::hasTable('bos_rencana_belanjas');

            $rQuery = DB::table('bos_realisasies as r')
                ->leftJoin('bos_kertas_kerja_items as k', 'r.bos_kertas_kerja_item_id', '=', 'k.id');

            if ($hasRencanaTable) {
                $rQuery->leftJoin('bos_rencana_belanjas as rb', 'r.bos_rencana_belanja_id', '=', 'rb.id');
                $noNotaCol = DB::raw('COALESCE(r.no_nota, rb.no_nota) as no_nota');
            } else {
                $noNotaCol = DB::raw('r.no_nota as no_nota');
            }

            $rQuery->select([
                DB::raw("'belanja' as row_type"),
                'r.id as id',
                'r.id as row_id',
                $noNotaCol,
                'r.no_transaksi',
                'r.transaction_date',
                'r.receipt_date',
                DB::raw("COALESCE({$monthMapping}, r.month) as month"),
                'r.year',
                'r.bos_kertas_kerja_item_id',
                'k.account_code',
                DB::raw('COALESCE(k.description, r.notes) as uraian'),
                DB::raw('COALESCE(k.description, r.notes) as description'),
                DB::raw('COALESCE(k.month, r.month) as bulan_perencanaan'),
                'r.volume',
                'r.unit',
                'r.price',
                DB::raw('0.00 as nominal_masuk'),
                'r.total_amount as nominal_keluar',
                DB::raw('-r.total_amount as amount_signed'),
                DB::raw("COALESCE(k.kegiatan, '-') as kegiatan"),
                'r.notes',
                DB::raw('NULL as penerimaan_category'),
                DB::raw('NULL as receipt_file'),
                'r.created_at',
            ])
                ->whereNull('r.deleted_at')
                ->when($selectedYear && $selectedYear !== 'all', function ($q) use ($selectedYear) {
                    $q->where('r.year', $selectedYear);
                });

            $union = $pQuery->unionAll($rQuery);

            $bkuWithBalance = DB::query()->fromSub($union, 'bku')
                ->select([
                    'bku.*',
                    DB::raw("SUM(amount_signed) OVER (ORDER BY transaction_date ASC, {$orderTypePenerimaanFirst} ASC, row_id ASC) as running_balance"),
                ]);

            $bkuQuery = DB::query()->fromSub($bkuWithBalance, 'bku_bal');

            if ($request->filled('month')) {
                $bkuQuery->where('month', $request->month);
            }

            $totalFilteredSum = (float) (clone $bkuQuery)->where('row_type', 'belanja')->sum('nominal_keluar');

            $bkuQuery->orderBy('transaction_date', 'desc')
                ->orderByRaw("{$orderTypePenerimaanFirst} desc")
                ->orderBy('row_id', 'desc');

            return DataTables::of($bkuQuery)
                ->addIndexColumn()
                ->orderColumn('no_nota', function ($q, $order) {
                    $q->orderBy('no_nota', $order)
                        ->orderBy('transaction_date', $order);
                })
                ->orderColumn('transaction_date', function ($q, $order) use ($orderTypePenerimaanFirst) {
                    $q->orderBy('transaction_date', $order)
                        ->orderByRaw("{$orderTypePenerimaanFirst} {$order}")
                        ->orderBy('row_id', $order);
                })
                ->orderColumn('no_transaksi', function ($q, $order) {
                    $q->orderBy('no_transaksi', $order)
                        ->orderBy('transaction_date', $order);
                })
                ->orderColumn('receipt_date', function ($q, $order) {
                    $q->orderByRaw("COALESCE(receipt_date, transaction_date) {$order}")
                        ->orderBy('transaction_date', $order);
                })
                ->orderColumn('month', function ($q, $order) use ($isSqlite) {
                    if ($isSqlite) {
                        $monthOrderCase = "CASE month
                            WHEN 'Januari' THEN 1
                            WHEN 'Februari' THEN 2
                            WHEN 'Maret' THEN 3
                            WHEN 'April' THEN 4
                            WHEN 'Mei' THEN 5
                            WHEN 'Juni' THEN 6
                            WHEN 'Juli' THEN 7
                            WHEN 'Agustus' THEN 8
                            WHEN 'September' THEN 9
                            WHEN 'Oktober' THEN 10
                            WHEN 'November' THEN 11
                            WHEN 'Desember' THEN 12
                            ELSE 13
                        END";
                        $q->orderByRaw("{$monthOrderCase} {$order}")->orderBy('transaction_date', $order);
                    } else {
                        $q->orderByRaw("FIELD(month, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') {$order}")
                            ->orderBy('transaction_date', $order);
                    }
                })
                ->orderColumn('description', function ($q, $order) {
                    $q->orderBy('uraian', $order);
                })
                ->orderColumn('volume', function ($q, $order) {
                    $q->orderByRaw("COALESCE(volume, 0) {$order}");
                })
                ->orderColumn('price', function ($q, $order) {
                    $q->orderByRaw("COALESCE(price, 0) {$order}");
                })
                ->orderColumn('saldo', function ($q, $order) {
                    $q->orderBy('running_balance', $order);
                })
                ->orderColumn('kegiatan', function ($q, $order) {
                    $q->orderBy('kegiatan', $order)->orderBy('account_code', $order);
                })
                ->addColumn('id', function ($row) {
                    return $row->row_id;
                })
                ->addColumn('no_nota_display', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        return '<span class="badge badge-success font-weight-bold" style="font-size: 0.67rem; padding: 0.2rem 0.35rem;"><i class="fas fa-arrow-down mr-1"></i>PENERIMAAN</span>';
                    }

                    $nota = $row->no_nota;
                    if (! $nota) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<button type="button" class="btn btn-xs btn-outline-info btn-view-nota py-0 px-1 font-tabular font-weight-bold" style="font-size: 0.72rem;" data-nota="'.e($nota).'" title="Klik untuk lihat rincian nota & update massal"><i class="fas fa-receipt mr-1"></i>'.e($nota).'</button>';
                })
                ->addColumn('transaction_date_display', function ($row) {
                    return $row->transaction_date ? date('d/m/Y', strtotime($row->transaction_date)) : '-';
                })
                ->addColumn('receipt_date_display', function ($row) {
                    $d = $row->receipt_date ?: $row->transaction_date;

                    return $d ? date('d/m/Y', strtotime($d)) : '-';
                })
                ->addColumn('kegiatan', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        return '<div class="d-flex align-items-center flex-wrap" style="gap: 4px;">'
                            .'<span class="badge badge-light border text-info" style="font-size: 0.67rem;">'.e($row->kegiatan).'</span>'
                            .'<span class="badge badge-light border text-muted font-tabular" style="font-size: 0.65rem;">KAS / BANK</span>'
                            .'</div>';
                    }

                    $keg = e($row->kegiatan ?: '-');
                    $code = e($row->account_code ?: '');

                    $html = '<div class="text-truncate font-weight-500 text-dark" style="max-width: 165px; font-size: 0.73rem;" data-bs-toggle="tooltip" data-toggle="tooltip" data-placement="top" title="'.$keg.'">'.$keg.'</div>';
                    if ($code && $code !== '-') {
                        $html .= '<div class="mt-1">'
                            .'<span class="badge badge-light border font-tabular text-secondary px-1 py-0" style="font-size: 0.65rem;" title="Kode Rekening"><i class="fas fa-tag mr-1 text-muted"></i>'.$code.'</span>'
                            .'</div>';
                    }

                    return $html;
                })
                ->addColumn('account_code', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        return '<span class="badge badge-light border text-muted" style="font-size: 0.67rem;">KAS / BANK</span>';
                    }

                    return $row->account_code ?: '-';
                })
                ->addColumn('description', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        $html = '<div class="font-weight-bold text-success" style="font-size: 0.83rem;"><i class="fas fa-hand-holding-usd mr-1"></i>'.e($row->uraian).'</div>';
                        if ($row->notes) {
                            $html .= '<div class="small text-muted font-italic mt-0.5" style="font-size: 0.69rem;">'.e($row->notes).'</div>';
                        }

                        return $html;
                    }

                    $planBadge = '';
                    if (! empty($row->bulan_perencanaan)) {
                        $planBadge = ' <span class="badge badge-light border text-primary ms-1 ml-1 font-weight-bold font-tabular" style="font-size: 0.70rem; padding: 0.15rem 0.4rem; vertical-align: middle;" title="Bulan Perencanaan (ARKAS)">'.e($row->bulan_perencanaan).'</span>';
                    }

                    $html = '<div class="font-weight-bold text-dark" style="font-size: 0.83rem; line-height: 1.25;">'.e($row->uraian).$planBadge.'</div>';
                    if ($row->notes) {
                        $html .= '<div class="small text-muted font-italic mt-0.5" style="font-size: 0.69rem;">'.e($row->notes).'</div>';
                    }

                    return $html;
                })
                ->filterColumn('no_nota', function ($q, $keyword) {
                    $q->where('no_nota', 'like', "%{$keyword}%");
                })
                ->filterColumn('no_transaksi', function ($q, $keyword) {
                    $q->where('no_transaksi', 'like', "%{$keyword}%");
                })
                ->filterColumn('account_code', function ($q, $keyword) {
                    $q->where('account_code', 'like', "%{$keyword}%");
                })
                ->filterColumn('description', function ($q, $keyword) {
                    $q->where(function ($sub) use ($keyword) {
                        $sub->where('uraian', 'like', "%{$keyword}%")
                            ->orWhere('bulan_perencanaan', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('kegiatan', function ($q, $keyword) {
                    $q->where(function ($sub) use ($keyword) {
                        $sub->where('kegiatan', 'like', "%{$keyword}%")
                            ->orWhere('account_code', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('total_amount', function ($q, $keyword) {
                    $clean = preg_replace('/[^0-9]/', '', (string) $keyword);
                    if ($clean !== '') {
                        $q->where(function ($sub) use ($clean) {
                            $sub->where('nominal_keluar', 'like', "%{$clean}%")
                                ->orWhere('nominal_masuk', 'like', "%{$clean}%");
                        });
                    }
                })
                ->orderColumn('total_amount', function ($q, $order) {
                    $q->orderByRaw("COALESCE(NULLIF(nominal_keluar, 0), nominal_masuk) {$order}");
                })
                ->addColumn('volume_display', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        return '<span class="text-muted">-</span>';
                    }

                    $unitHtml = $row->unit ? '<div class="text-muted font-weight-normal" style="font-size: 0.67rem; line-height: 1;">'.e($row->unit).'</div>' : '';

                    return '<div style="line-height: 1.15;"><div>'.number_format($row->volume, 0, ',', '.').'</div>'.$unitHtml.'</div>';
                })
                ->addColumn('price_display', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        return '<span class="text-muted">-</span>';
                    }

                    return 'Rp '.number_format($row->price, 0, ',', '.');
                })
                ->addColumn('total_amount_display', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        return '<span class="badge badge-success font-weight-bold font-tabular py-0 px-1" style="font-size: 0.74rem;"><i class="fas fa-plus mr-1"></i>Rp '.number_format($row->nominal_masuk, 0, ',', '.').'</span>';
                    }

                    return 'Rp '.number_format($row->nominal_keluar, 0, ',', '.');
                })
                ->addColumn('saldo_display', function ($row) {
                    $bal = (float) $row->running_balance;
                    $colorClass = $bal >= 0 ? 'text-primary' : 'text-danger';

                    return '<span class="font-weight-bold font-tabular '.$colorClass.'" style="font-size: 0.74rem;">Rp '.number_format($bal, 0, ',', '.').'</span>';
                })
                ->addColumn('action', function ($row) {
                    if ($row->row_type === 'penerimaan') {
                        $receiptUrl = $row->receipt_file ? asset('storage/'.$row->receipt_file) : '';
                        $btn = '<button class="btn btn-warning btn-xs btn-edit-penerimaan mr-1" data-id="'.$row->row_id.'" data-date="'.substr((string) $row->transaction_date, 0, 10).'" data-category="'.e($row->penerimaan_category).'" data-ref="'.e($row->no_transaksi).'" data-amount="'.$row->nominal_masuk.'" data-desc="'.e($row->uraian).'" data-notes="'.e($row->notes).'" data-file="'.$receiptUrl.'" title="Edit Penerimaan"><i class="fas fa-edit"></i></button>';
                        $btn .= '<button class="btn btn-danger btn-xs btn-delete-penerimaan" data-url="'.route('bos.realisasi.penerimaan.destroy', $row->row_id).'" title="Hapus Penerimaan"><i class="fas fa-trash"></i></button>';

                        return $btn;
                    }

                    $btn = '<button class="btn btn-warning btn-xs btn-edit mr-1" data-id="'.$row->row_id.'" data-json="'.e(json_encode($row)).'" title="Edit Realisasi"><i class="fas fa-edit"></i></button>';
                    $btn .= '<button class="btn btn-danger btn-xs btn-delete" data-url="'.route('bos.realisasi.destroy', $row->row_id).'" title="Hapus Realisasi"><i class="fas fa-trash"></i></button>';

                    return $btn;
                })
                ->with('grandTotal', 'Rp '.number_format($totalFilteredSum, 0, ',', '.'))
                ->rawColumns(['action', 'kegiatan', 'no_nota_display', 'description', 'total_amount_display', 'saldo_display', 'volume_display', 'account_code'])
                ->make(true);
        }

        $years = BosKertasKerjaItem::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([(int) $selectedYear]);
        }

        return view('bos.realisasi.index', compact('years', 'selectedYear'));
    }

    /**
     * API: Return financial summary for the selected year.
     * Total Pagu Anggaran, Total Dana Masuk, Total Realisasi, Saldo Rekening Bank, Saldo Pagu, Percentage.
     */
    public function summary(Request $request)
    {
        $year = $request->input('year', (string) session('fiscal_year', date('Y')));

        // Pagu Anggaran Kertas Kerja Aktif
        $paguQuery = BosKertasKerjaItem::whereHas('file', function ($f) {
            $f->where('is_active', true);
        });

        // Total Dana Cair Masuk (Penerimaan)
        $danaMasukQuery = BosPenerimaan::query();

        // Total Belanja Realisasi
        $belanjaQuery = BosRealisasi::query();

        if ($year && $year !== 'all') {
            $paguQuery->where('year', $year);
            $danaMasukQuery->where('year', $year);
            $belanjaQuery->where('year', $year);
        }

        $totalPagu = (float) $paguQuery->sum('total_amount');
        $totalDanaMasuk = (float) $danaMasukQuery->sum('amount');
        $totalBelanja = (float) $belanjaQuery->sum('total_amount');

        // Saldo Rekening Bank Riil (Rekening Koran) = Dana Masuk - Belanja
        $saldoBank = $totalDanaMasuk - $totalBelanja;

        // Sisa Pagu Anggaran Belum Dibelanjakan = Pagu - Belanja
        $sisaPagu = $totalPagu - $totalBelanja;

        // Persentase Belanja terhadap Pagu
        $percentage = $totalPagu > 0 ? round(($totalBelanja / $totalPagu) * 100, 2) : 0;

        return response()->json([
            'total_anggaran' => $totalPagu,
            'total_anggaran_display' => 'Rp '.number_format($totalPagu, 0, ',', '.'),
            'total_dana_masuk' => $totalDanaMasuk,
            'total_dana_masuk_display' => 'Rp '.number_format($totalDanaMasuk, 0, ',', '.'),
            'total_realisasi' => $totalBelanja,
            'total_realisasi_display' => 'Rp '.number_format($totalBelanja, 0, ',', '.'),
            'saldo_bank' => $saldoBank,
            'saldo_bank_display' => 'Rp '.number_format($saldoBank, 0, ',', '.'),
            'saldo' => $sisaPagu,
            'saldo_display' => 'Rp '.number_format($sisaPagu, 0, ',', '.'),
            'percentage' => $percentage,
            'percentage_display' => $percentage.'%',
            'year' => $year,
        ]);
    }

    public function availableItems(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|string',
        ]);

        $year = $request->year;
        $targetMonth = $request->month;

        $targetIndex = array_search($targetMonth, $this->months);
        if ($targetIndex === false) {
            $allowedMonths = $this->months;
        } else {
            $allowedMonths = array_slice($this->months, 0, $targetIndex + 1);
        }

        // Ambil seluruh item Kertas Kerja di tahun tersebut s/d bulan realisasi (Hanya versi aktif)
        $items = BosKertasKerjaItem::where('year', $year)
            ->whereIn('month', $allowedMonths)
            ->whereHas('file', function ($f) {
                $f->where('is_active', true);
            })
            ->with(['realisasis'])
            ->orderByRaw("FIELD(month, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') ASC")
            ->get();

        // Kelompokkan item Kertas Kerja berdasarkan Kode Rekening + Kode/Nama Kegiatan + Nama Barang + Harga Satuan + Satuan
        // Hanya barang yang memenuhi 4 syarat kriteria sama (Rekening, Kegiatan, Uraian, Harga) yang digabung
        $grouped = [];
        $editRealisasiId = $request->query('edit_realisasi_id');

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
                    'primary_item_id' => $item->id,
                    'account_code' => $item->account_code,
                    'program_code' => $item->program_code,
                    'kegiatan' => $item->kegiatan,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'price' => (float) $item->price,
                    'months_included' => [],
                    'total_budget_vol' => 0,
                    'total_realized_vol' => 0,
                    'item_ids' => [],
                ];
            }

            $grouped[$groupKey]['months_included'][] = $item->month;
            $grouped[$groupKey]['total_budget_vol'] += (float) $item->volume;
            $grouped[$groupKey]['item_ids'][] = $item->id;

            // Realisasi yang terkait dengan item ini
            $realizedVol = $item->realisasis
                ->when($editRealisasiId, function ($c) use ($editRealisasiId) {
                    return $c->where('id', '!=', $editRealisasiId);
                })
                ->sum('volume');

            $grouped[$groupKey]['total_realized_vol'] += (float) $realizedVol;
        }

        $available = [];
        foreach ($grouped as $g) {
            $remainingVol = $g['total_budget_vol'] - $g['total_realized_vol'];

            if ($remainingVol > 0.001) {
                $monthsStr = implode(', ', array_unique($g['months_included']));
                $progStr = $g['program_code'] ? " [{$g['program_code']}]" : '';

                $available[] = [
                    'id' => $g['primary_item_id'], // Primary ID representatif
                    'text' => "[{$monthsStr}]{$progStr} {$g['description']} (Rp ".number_format($g['price'], 0, ',', '.').' | Total: '.number_format($g['total_budget_vol'], 0, ',', '.')." {$g['unit']} | Sisa: ".number_format($remainingVol, 0, ',', '.')." {$g['unit']} | Kegiatan: {$g['kegiatan']})",
                    'program_code' => $g['program_code'],
                    'account_code' => $g['account_code'],
                    'kegiatan' => $g['kegiatan'],
                    'description' => $g['description'],
                    'volume_budget' => $g['total_budget_vol'],
                    'remaining_volume' => $remainingVol,
                    'unit' => $g['unit'],
                    'price' => $g['price'],
                ];
            }
        }

        return response()->json($available);
    }

    public function store(Request $request)
    {
        if ($request->has('price')) {
            $request->merge(['price' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', (string) $request->price)))]);
        }
        if ($request->has('volume')) {
            $request->merge(['volume' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $request->volume))]);
        }

        $request->validate([
            'bos_kertas_kerja_item_id' => 'required|exists:bos_kertas_kerja_items,id',
            'no_transaksi' => 'nullable|string|max:50',
            'year' => 'required|integer',
            'month' => 'required|string',
            'transaction_date' => 'required|date',
            'receipt_date' => 'nullable|date',
            'volume' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $primaryItem = BosKertasKerjaItem::findOrFail($request->bos_kertas_kerja_item_id);

        // Ambil akumulasi seluruh alokasi anggaran s/d bulan realisasi untuk kelompok barang yang sama persis (Rekening, Kegiatan, Description, Unit, Price)
        $targetMonth = $request->month;
        $targetIndex = array_search($targetMonth, $this->months);
        $allowedMonths = ($targetIndex === false) ? $this->months : array_slice($this->months, 0, $targetIndex + 1);

        $matchingItems = BosKertasKerjaItem::where('year', $request->year)
            ->whereIn('month', $allowedMonths)
            ->whereHas('file', function ($f) {
                $f->where('is_active', true);
            })
            ->where('account_code', $primaryItem->account_code)
            ->where('kegiatan', $primaryItem->kegiatan)
            ->where('description', $primaryItem->description)
            ->where('unit', $primaryItem->unit)
            ->where('price', $primaryItem->price)
            ->with('realisasis')
            ->get();

        $totalBudgetVol = (float) $matchingItems->sum('volume');
        $totalRealizedVol = (float) $matchingItems->flatMap->realisasis->sum('volume');
        $totalRemainingVol = $totalBudgetVol - $totalRealizedVol;

        if ($request->volume > ($totalRemainingVol + 0.001)) {
            return response()->json([
                'message' => 'Volume realisasi ('.number_format($request->volume, 0, ',', '.').') melebihi sisa akumulasi ketersediaan anggaran ('.number_format($totalRemainingVol, 0, ',', '.')." {$primaryItem->unit}).",
            ], 422);
        }

        $totalAmount = $request->volume * $request->price;

        BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $primaryItem->id,
            'no_transaksi' => $request->no_transaksi,
            'year' => $request->year,
            'month' => $request->month ?: ($primaryItem->month ?: 'Januari'),
            'transaction_date' => $request->transaction_date,
            'receipt_date' => $request->receipt_date,
            'volume' => $request->volume,
            'unit' => $request->unit,
            'price' => $request->price,
            'total_amount' => $totalAmount,
            'notes' => $request->notes,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Transaksi realisasi belanja berhasil disimpan.']);
    }

    public function update(Request $request, BosRealisasi $realisasi)
    {
        if ($request->has('price')) {
            $request->merge(['price' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', (string) $request->price)))]);
        }
        if ($request->has('volume')) {
            $request->merge(['volume' => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $request->volume))]);
        }

        $request->validate([
            'no_transaksi' => 'nullable|string|max:50',
            'transaction_date' => 'required|date',
            'receipt_date' => 'nullable|date',
            'volume' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $item = $realisasi->kertasKerjaItem;

        // Validasi Sisa Volume (tanpa menghitung transaksi ini sendiri)
        $otherRealizedVol = (float) $item->realisasis()->where('id', '!=', $realisasi->id)->sum('volume');
        $remainingVol = (float) $item->volume - $otherRealizedVol;

        if ($request->volume > ($remainingVol + 0.001)) {
            return response()->json([
                'message' => 'Volume realisasi ('.number_format($request->volume, 0, ',', '.').') melebihi sisa volume ketersediaan ('.number_format($remainingVol, 0, ',', '.')." {$item->unit}).",
            ], 422);
        }

        $totalAmount = $request->volume * $request->price;

        $realisasi->update([
            'no_transaksi' => $request->no_transaksi,
            'transaction_date' => $request->transaction_date,
            'receipt_date' => $request->receipt_date,
            'volume' => $request->volume,
            'unit' => $request->unit,
            'price' => $request->price,
            'total_amount' => $totalAmount,
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'Transaksi realisasi belanja berhasil diperbarui.']);
    }

    /**
     * API: Inline update single field from DataTables cell
     */
    public function updateField(Request $request, BosRealisasi $realisasi)
    {
        $request->validate([
            'field' => 'required|in:no_transaksi,transaction_date,receipt_date,volume,price',
            'value' => 'nullable',
        ]);

        $field = $request->field;
        $value = $request->value;

        if (in_array($field, ['volume', 'price']) && $value === null) {
            return response()->json(['message' => 'Nilai tidak boleh kosong.'], 422);
        }

        $newVol = ($field === 'volume') ? floatval($value) : $realisasi->volume;
        $newPrice = ($field === 'price') ? floatval($value) : $realisasi->price;

        if ($field === 'volume') {
            $item = $realisasi->kertasKerjaItem;
            $otherRealizedVol = (float) $item->realisasis()->where('id', '!=', $realisasi->id)->sum('volume');
            $remainingVol = (float) $item->volume - $otherRealizedVol;

            if ($newVol > ($remainingVol + 0.001)) {
                return response()->json([
                    'message' => 'Volume realisasi ('.number_format($newVol, 0, ',', '.').') melebihi sisa ketersediaan ('.number_format($remainingVol, 0, ',', '.')." {$item->unit}).",
                ], 422);
            }
        }

        $totalAmount = $newVol * $newPrice;

        $updateData = [
            $field => $value,
            'total_amount' => $totalAmount,
        ];

        if ($field === 'volume') {
            $updateData['volume'] = $newVol;
        } elseif ($field === 'price') {
            $updateData['price'] = $newPrice;
        }

        $realisasi->update($updateData);

        return response()->json([
            'message' => 'Data tersimpan.',
            'realisasi' => $realisasi->fresh(),
        ]);
    }

    public function destroy(BosRealisasi $realisasi)
    {
        $rencanaId = $realisasi->bos_rencana_belanja_id;

        $realisasi->delete();

        if ($rencanaId) {
            $hasOtherRealisasi = BosRealisasi::where('bos_rencana_belanja_id', $rencanaId)->exists();
            if (! $hasOtherRealisasi) {
                BosRencanaBelanja::where('id', $rencanaId)
                    ->where('status', 'realized')
                    ->update(['status' => 'draft']);
            }
        }

        return response()->json(['message' => 'Transaksi realisasi belanja berhasil dihapus.']);
    }

    /**
     * Get details of all realisasi items associated with a specific no_nota
     */
    public function notaDetails(Request $request)
    {
        $noNota = trim((string) $request->query('no_nota'));
        if (! $noNota) {
            return response()->json(['message' => 'No. Nota tidak valid.'], 422);
        }

        // Cari realisasi berdasarkan no_nota atau relasi rencana belanja
        $items = BosRealisasi::where(function ($q) use ($noNota) {
            $q->where('no_nota', $noNota)
                ->orWhereHas('rencanaBelanja', function ($sub) use ($noNota) {
                    $sub->where('no_nota', $noNota);
                });
        })
            ->with(['kertasKerjaItem', 'rencanaBelanja'])
            ->orderBy('id', 'asc')
            ->get();

        // Jika tidak ditemukan di tabel realisasi, coba cari di rencana belanja
        if ($items->isEmpty()) {
            $rencana = BosRencanaBelanja::where('no_nota', $noNota)
                ->with(['items.kertasKerjaItem'])
                ->first();

            if (! $rencana) {
                return response()->json(['message' => 'Data transaksi dengan No. Nota '.$noNota.' tidak ditemukan.'], 404);
            }

            $totalAmount = (float) $rencana->items->sum('total_amount');
            $totalItems = $rencana->items->count();

            $itemDetails = $rencana->items->map(function ($it, $idx) {
                return [
                    'index' => $idx + 1,
                    'id' => $it->id,
                    'account_code' => $it->kertasKerjaItem->account_code ?? '-',
                    'description' => $it->kertasKerjaItem->description ?? '-',
                    'kegiatan' => $it->kertasKerjaItem->kegiatan ?? '-',
                    'volume' => (float) $it->volume_plan,
                    'unit' => $it->unit,
                    'price' => (float) $it->price,
                    'price_display' => 'Rp '.number_format($it->price, 0, ',', '.'),
                    'total_amount' => (float) $it->total_amount,
                    'total_amount_display' => 'Rp '.number_format($it->total_amount, 0, ',', '.'),
                ];
            });

            $plannedDateStr = $rencana->planned_date ? $rencana->planned_date->format('Y-m-d') : null;
            $plannedDateDisplay = $rencana->planned_date ? $rencana->planned_date->format('d/m/Y') : '-';

            return response()->json([
                'no_nota' => $noNota,
                'title' => $rencana->title,
                'supplier_name' => $rencana->supplier_name,
                'no_transaksi' => null,
                'transaction_date' => $plannedDateStr,
                'transaction_date_display' => $plannedDateDisplay,
                'receipt_date' => $plannedDateStr,
                'receipt_date_display' => $plannedDateDisplay,
                'month' => $rencana->month,
                'year' => $rencana->year,
                'total_items' => $totalItems,
                'total_amount' => $totalAmount,
                'total_amount_display' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
                'items' => $itemDetails,
            ]);
        }

        $first = $items->first();
        $rencana = $first->rencanaBelanja ?: BosRencanaBelanja::where('no_nota', $noNota)->first();

        $totalAmount = (float) $items->sum('total_amount');
        $totalItems = $items->count();

        $itemDetails = $items->map(function ($it, $idx) {
            return [
                'index' => $idx + 1,
                'id' => $it->id,
                'account_code' => $it->kertasKerjaItem->account_code ?? '-',
                'description' => $it->kertasKerjaItem->description ?? '-',
                'kegiatan' => $it->kertasKerjaItem->kegiatan ?? '-',
                'volume' => (float) $it->volume,
                'unit' => $it->unit,
                'price' => (float) $it->price,
                'price_display' => 'Rp '.number_format($it->price, 0, ',', '.'),
                'total_amount' => (float) $it->total_amount,
                'total_amount_display' => 'Rp '.number_format($it->total_amount, 0, ',', '.'),
            ];
        });

        $transDateStr = null;
        $transDateDisplay = '-';
        if ($first->transaction_date) {
            $transDateStr = $first->transaction_date instanceof \DateTimeInterface
                ? $first->transaction_date->format('Y-m-d')
                : substr((string) $first->transaction_date, 0, 10);
            $transDateDisplay = $first->transaction_date instanceof \DateTimeInterface
                ? $first->transaction_date->format('d/m/Y')
                : date('d/m/Y', strtotime((string) $first->transaction_date));
        }

        $receiptDateStr = null;
        $receiptDateDisplay = '-';
        if ($first->receipt_date) {
            $receiptDateStr = $first->receipt_date instanceof \DateTimeInterface
                ? $first->receipt_date->format('Y-m-d')
                : substr((string) $first->receipt_date, 0, 10);
            $receiptDateDisplay = $first->receipt_date instanceof \DateTimeInterface
                ? $first->receipt_date->format('d/m/Y')
                : date('d/m/Y', strtotime((string) $first->receipt_date));
        }

        return response()->json([
            'no_nota' => $noNota,
            'title' => $rencana ? $rencana->title : 'Nota Belanja',
            'supplier_name' => $rencana ? $rencana->supplier_name : null,
            'no_transaksi' => $first->no_transaksi,
            'transaction_date' => $transDateStr,
            'transaction_date_display' => $transDateDisplay,
            'receipt_date' => $receiptDateStr,
            'receipt_date_display' => $receiptDateDisplay,
            'month' => $first->month,
            'year' => $first->year,
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
            'total_amount_display' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
            'items' => $itemDetails,
        ]);
    }

    /**
     * Bulk update transaction number, transaction date, and receipt date by no_nota
     */
    public function bulkUpdateNota(Request $request)
    {
        $request->validate([
            'no_nota' => 'required|string',
            'no_transaksi' => 'nullable|string|max:50',
            'transaction_date' => 'required|date',
            'receipt_date' => 'nullable|date',
        ]);

        $noNota = trim((string) $request->no_nota);

        $items = BosRealisasi::where(function ($q) use ($noNota) {
            $q->where('no_nota', $noNota)
                ->orWhereHas('rencanaBelanja', function ($sub) use ($noNota) {
                    $sub->where('no_nota', $noNota);
                });
        })->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Tidak ada item realisasi dengan No. Nota tersebut.'], 404);
        }

        $receiptDate = $request->receipt_date ?: $request->transaction_date;

        foreach ($items as $item) {
            $item->update([
                'no_transaksi' => $request->no_transaksi,
                'no_nota' => $noNota,
                'transaction_date' => $request->transaction_date,
                'receipt_date' => $receiptDate,
            ]);
        }

        return response()->json([
            'message' => 'Berhasil memperbarui data transaksi untuk '.$items->count()." item pada No. Nota {$noNota}.",
        ]);
    }

    /**
     * Simpan transaksi penerimaan dana BOS (Dana Masuk).
     */
    public function storePenerimaan(Request $request)
    {
        $amount = (float) preg_replace('/[^0-9]/', '', (string) $request->amount);

        $request->merge(['amount' => $amount]);

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'category' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('receipt_file')) {
            $filePath = $request->file('receipt_file')->store('bos_penerimaan', 'public');
        }

        $year = (int) date('Y', strtotime($validated['transaction_date']));

        $penerimaan = BosPenerimaan::create([
            'funding_source' => 'BOS',
            'year' => $year,
            'category' => $validated['category'],
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'receipt_file' => $filePath,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penerimaan dana BOS berhasil dicatat.',
            'data' => $penerimaan,
        ]);
    }

    /**
     * Update data penerimaan dana BOS.
     */
    public function updatePenerimaan(Request $request, BosPenerimaan $penerimaan)
    {
        $amount = (float) preg_replace('/[^0-9]/', '', (string) $request->amount);

        $request->merge(['amount' => $amount]);

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'category' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = $penerimaan->receipt_file;
        if ($request->hasFile('receipt_file')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('receipt_file')->store('bos_penerimaan', 'public');
        }

        $year = (int) date('Y', strtotime($validated['transaction_date']));

        $penerimaan->update([
            'year' => $year,
            'category' => $validated['category'],
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'receipt_file' => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data penerimaan dana BOS berhasil diperbarui.',
            'data' => $penerimaan,
        ]);
    }

    /**
     * Hapus data penerimaan dana BOS.
     */
    public function destroyPenerimaan(BosPenerimaan $penerimaan)
    {
        if ($penerimaan->receipt_file && Storage::disk('public')->exists($penerimaan->receipt_file)) {
            Storage::disk('public')->delete($penerimaan->receipt_file);
        }

        $penerimaan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data penerimaan dana berhasil dihapus.',
        ]);
    }
}

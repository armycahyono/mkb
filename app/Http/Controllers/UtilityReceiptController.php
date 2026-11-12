<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessUtilityReceiptOcrJob;
use App\Models\BosKertasKerjaItem;
use App\Models\UtilityProfile;
use App\Models\UtilityReceipt;
use App\Services\UtilityReceiptOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UtilityReceiptController extends Controller
{
    protected array $indonesianMonths = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];

    public function index(Request $request)
    {
        $activeFiscalYear = $request->input('year', session('fiscal_year', date('Y')));
        $isAllYears = ($activeFiscalYear === 'all');
        if (! $isAllYears) {
            $activeFiscalYear = (int) $activeFiscalYear;
        }

        $defaultMonth = date('m');
        $selectedMonth = $request->input('month', $defaultMonth);
        if ($selectedMonth !== 'all' && strlen((string) $selectedMonth) === 1) {
            $selectedMonth = '0'.$selectedMonth;
        }

        $isAllMonths = ($selectedMonth === 'all');
        $months = $this->indonesianMonths;
        $selectedMonthName = $isAllMonths ? 'Semua Bulan' : ($months[$selectedMonth] ?? 'Januari');

        // Daftar tahun yang tersedia
        $receiptYears = UtilityReceipt::whereNotNull('fiscal_year')
            ->pluck('fiscal_year')
            ->unique();
        $availableYears = $receiptYears->push((int) date('Y'));
        if (! $isAllYears) {
            $availableYears->push($activeFiscalYear);
        }
        $availableYears = $availableYears->unique()->sortDesc()->values();

        // 9 Master Profil Tagihan Rutin
        $profiles = UtilityProfile::where('is_active', true)->orderBy('sort_order', 'asc')->get();

        // Ambil data bukti pembayaran untuk menghitung matriks kepatuhan & rata-rata tagihan
        $receiptsQuery = UtilityReceipt::with('profile');

        if (! $isAllYears) {
            $receiptsQuery->where('fiscal_year', $activeFiscalYear);
        }

        if (! $isAllMonths) {
            $receiptsQuery->where(function ($q) use ($selectedMonth, $selectedMonthName) {
                $q->where('billing_month', $selectedMonth)
                    ->orWhere('billing_month', $selectedMonthName)
                    ->orWhere(function ($sub) use ($selectedMonth) {
                        $sub->where(function ($emptyQ) {
                            $emptyQ->whereNull('billing_month')->orWhere('billing_month', '');
                        })->whereMonth('transaction_date', $selectedMonth);
                    });
            });
        }

        $monthReceipts = $receiptsQuery->get();

        // Ambil data pembayaran bulan sekarang untuk keperluan tampilan perbandingan jika memilih Semua Bulan
        $nowMonthCode = date('m');
        $nowMonthName = $this->indonesianMonths[$nowMonthCode] ?? '';
        $currentMonthReceipts = UtilityReceipt::query()
            ->where(function ($q) use ($nowMonthCode, $nowMonthName) {
                $q->where('billing_month', $nowMonthCode)
                    ->orWhere('billing_month', $nowMonthName)
                    ->orWhere(function ($sub) use ($nowMonthCode) {
                        $sub->where(function ($emptyQ) {
                            $emptyQ->whereNull('billing_month')->orWhere('billing_month', '');
                        })->whereMonth('transaction_date', $nowMonthCode);
                    });
            })
            ->when(! $isAllYears, fn ($q) => $q->where('fiscal_year', $activeFiscalYear))
            ->get();

        // Matriks status per profil
        $profileMatrix = $profiles->map(function ($profile) use ($monthReceipts, $currentMonthReceipts) {
            $receipts = $monthReceipts->where('utility_profile_id', $profile->id);
            $isPaid = $receipts->isNotEmpty();
            $latestReceipt = $receipts->sortByDesc('transaction_date')->first();
            $totalPaid = (float) $receipts->sum('total_amount');
            $receiptCount = $receipts->count();

            // Pembayaran bulan sekarang (current month)
            $currReceipts = $currentMonthReceipts->where('utility_profile_id', $profile->id);
            $currentMonthPaid = (float) $currReceipts->sum('total_amount');

            // Hitung jumlah bulan unik yang memiliki pembayaran
            $distinctMonthsCount = $receipts->map(function ($r) {
                $yr = $r->fiscal_year ?: ($r->transaction_date ? $r->transaction_date->format('Y') : '');
                $mNum = $this->resolveMonthNumber($r->billing_month) ?: ($r->transaction_date ? (int) $r->transaction_date->format('m') : 0);

                return $yr.'-'.$mNum;
            })->filter(fn ($v) => ! str_ends_with($v, '-0'))->unique()->count();

            // Hitung rata-rata tagihan per bulan aktif dan proyeksi rencana anggaran 12 bulan
            $activeCount = max($distinctMonthsCount, ($receiptCount > 0 ? 1 : 0));
            $avgPerMonth = $activeCount > 0 ? ($totalPaid / $activeCount) : 0;
            $projectedAnnual = $avgPerMonth * 12;
            $minAmount = $receiptCount > 0 ? (float) $receipts->min('total_amount') : 0;
            $maxAmount = $receiptCount > 0 ? (float) $receipts->max('total_amount') : 0;

            return [
                'profile' => $profile,
                'is_paid' => $isPaid,
                'receipt_count' => $receiptCount,
                'distinct_months_count' => $distinctMonthsCount,
                'total_paid' => $totalPaid,
                'current_month_paid' => $currentMonthPaid,
                'avg_per_month' => (float) $avgPerMonth,
                'projected_annual' => (float) $projectedAnnual,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'latest_receipt' => $latestReceipt,
            ];
        });

        // Hitung total ringkasan
        $paidCount = $profileMatrix->where('is_paid', true)->count();
        $totalProfilesCount = $profiles->count();
        $monthTotalExpenses = (float) $monthReceipts->sum('total_amount');

        // Total pengeluaran tahun berjalan / semua tahun
        $totalQuery = UtilityReceipt::query();
        if (! $isAllYears) {
            $totalQuery->where('fiscal_year', $activeFiscalYear);
        }
        $yearTotalExpenses = (float) $totalQuery->sum('total_amount');

        $totalMonthlyAvg = (float) $profileMatrix->sum('avg_per_month');
        $totalProjectedAnnual = (float) $profileMatrix->sum('projected_annual');

        $kertasKerjaItems = BosKertasKerjaItem::where('year', (int) ($isAllYears ? date('Y') : $activeFiscalYear))
            ->whereHas('file', fn ($f) => $f->where('is_active', true))
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('kegiatan', 'like', '%pembayaran daya%listrik%')
                        ->orWhere('kegiatan', 'like', '%pembayaran%biaya telepon%')
                        ->orWhere('kegiatan', 'like', '%pembayaran%jasa internet%')
                        ->orWhere('program_code', 'like', '06.07.01%')
                        ->orWhere('program_code', 'like', '07.06.01%')
                        ->orWhere('program_code', 'like', '06.07.04%')
                        ->orWhere('program_code', 'like', '07.06.04%')
                        ->orWhere('program_code', 'like', '06.07.05%')
                        ->orWhere('program_code', 'like', '07.06.05%');
                });
            })
            ->with(['utilityProfile'])
            ->orderByRaw("CASE month 
                WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 
                WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 
                WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 
                WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 
                ELSE 99 END ASC")
            ->orderBy('description')
            ->get();

        return view('utility_receipts.index', compact(
            'activeFiscalYear',
            'isAllYears',
            'selectedMonth',
            'selectedMonthName',
            'isAllMonths',
            'months',
            'availableYears',
            'profiles',
            'profileMatrix',
            'kertasKerjaItems',
            'paidCount',
            'totalProfilesCount',
            'monthTotalExpenses',
            'yearTotalExpenses',
            'totalMonthlyAvg',
            'totalProjectedAnnual'
        ));
    }

    public function data(Request $request)
    {
        $year = $request->input('year', session('fiscal_year', date('Y')));
        $month = $request->input('month');
        $category = $request->input('category');
        $profileId = $request->input('profile_id');

        $query = UtilityReceipt::with(['profile', 'creator', 'kertasKerjaItem']);

        if (! empty($year) && $year !== 'all') {
            $query->where('fiscal_year', (int) $year);
        }

        if (! empty($month) && $month !== 'all') {
            $monthName = $this->indonesianMonths[$month] ?? null;
            $query->where(function ($q) use ($month, $monthName) {
                $q->where('billing_month', $month)
                    ->orWhere('billing_month', $monthName)
                    ->orWhere(function ($sub) use ($month) {
                        $sub->where(function ($emptyQ) {
                            $emptyQ->whereNull('billing_month')->orWhere('billing_month', '');
                        })->whereMonth('transaction_date', $month);
                    });
            });
        }

        if (! empty($profileId)) {
            $query->where('utility_profile_id', $profileId);
        }

        if (! empty($category) && $category !== 'all' && $category !== 'rekap_avg') {
            $query->whereHas('profile', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        $effectiveYear = (! empty($year) && $year !== 'all') ? (int) $year : (int) session('fiscal_year', date('Y'));

        // Preload seluruh item kertas kerja utilitas untuk tahun aktif (untuk kalkulasi anggaran YTD cepat)
        $utilityKkItems = BosKertasKerjaItem::where('year', $effectiveYear)
            ->whereHas('file', fn ($f) => $f->where('is_active', true))
            ->where(function ($q) {
                $q->where('kegiatan', 'like', '%pembayaran daya%listrik%')
                    ->orWhere('kegiatan', 'like', '%pembayaran%biaya telepon%')
                    ->orWhere('kegiatan', 'like', '%pembayaran%jasa internet%')
                    ->orWhere('program_code', 'like', '06.07.01%')
                    ->orWhere('program_code', 'like', '07.06.01%')
                    ->orWhere('program_code', 'like', '06.07.04%')
                    ->orWhere('program_code', 'like', '07.06.04%')
                    ->orWhere('program_code', 'like', '06.07.05%')
                    ->orWhere('program_code', 'like', '07.06.05%');
            })
            ->get();

        // Preload seluruh bukti pembayaran utilitas tahun aktif
        $yearReceipts = UtilityReceipt::where('fiscal_year', $effectiveYear)->get();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date_display', function ($row) {
                $tgl = $row->transaction_date ? $row->transaction_date->format('d/m/Y') : '-';
                $jam = $row->transaction_date ? $row->transaction_date->format('H:i:s') : '';

                return '<div class="font-weight-bold text-dark font-tabular">'.$tgl.'</div>'.
                       ($jam ? '<small class="text-muted d-block">'.$jam.'</small>' : '');
            })
            ->addColumn('profile_display', function ($row) {
                $badge = $row->profile ? $row->profile->category_badge : '<span class="badge badge-secondary px-2 py-1">Utilitas</span>';
                $nama = $row->profile ? e($row->profile->name) : (e($row->customer_name) ?: 'Tagihan Utilitas');

                return '<div class="mb-1">'.$badge.'</div>'.
                       '<div class="font-weight-bold text-dark" style="font-size: 0.85rem;">'.$nama.'</div>';
            })
            ->addColumn('account_display', function ($row) {
                $nama = e($row->customer_name ?: '-');
                $idpel = $row->customer_number ? '<small class="text-muted d-block font-tabular font-weight-500">ID: '.e($row->customer_number).'</small>' : '';
                $meter = ($row->meter_number && $row->meter_number !== $row->customer_number) ? '<small class="text-muted d-block font-tabular">Mtr: '.e($row->meter_number).'</small>' : '';

                return '<div class="font-weight-bold text-dark" style="font-size: 0.85rem;">'.$nama.'</div>'.$idpel.$meter;
            })
            ->addColumn('tariff_display', function ($row) {
                $tarif = $row->tariff_power ? '<span class="badge badge-light border px-2 py-0.5 font-weight-bold">'.e($row->tariff_power).'</span>' : '-';
                $kwh = $row->kwh_amount ? '<small class="text-muted d-block mt-1 font-tabular">'.number_format((float) $row->kwh_amount, 1, ',', '.').' kWh</small>' : '';

                return '<div>'.$tarif.'</div>'.$kwh;
            })
            ->addColumn('breakdown_display', function ($row) {
                $base = '<div>Pokok/Token: <strong class="font-tabular">'.$row->formatted_base_amount.'</strong></div>';
                $admin = '<div>Admin: <span class="font-tabular">'.$row->formatted_admin_fee.'</span></div>';
                $pbjt = $row->tax_pbjt > 0 ? '<div>Pajak PBJT: <span class="font-tabular">Rp '.number_format((float) $row->tax_pbjt, 0, ',', '.').'</span></div>' : '';

                return '<div class="text-muted" style="font-size: 0.75rem; line-height: 1.35;">'.$base.$admin.$pbjt.'</div>';
            })
            ->addColumn('total_display', function ($row) {
                return '<div class="font-weight-bold font-tabular text-dark" style="font-size: 0.95rem;">'.$row->formatted_total_amount.'</div>'.
                       '<small class="text-muted">'.e($row->billing_month).' '.$row->fiscal_year.'</small>';
            })
            ->addColumn('kertas_kerja_display', function ($row) {
                if ($row->bos_kertas_kerja_item_id && $row->kertasKerjaItem) {
                    $kk = $row->kertasKerjaItem;
                    $kkTotal = (float) ($kk->total_amount ?: ($kk->price * ($kk->volume ?: 1)));

                    return '<span class="badge badge-success px-2 py-1 font-weight-500" title="'.e($kk->kegiatan).' - Total: Rp '.number_format($kkTotal, 0, ',', '.').'"><i class="fas fa-link mr-1"></i>'.e($kk->description).' ('.e($kk->month).')</span>';
                }

                if ($row->profile && ! $row->profile->is_budgeted) {
                    return '<span class="badge badge-secondary px-2 py-1" title="SMP PGRI / Internal: Tidak dianggarkan BOS"><i class="fas fa-ban mr-1"></i>Non-BOS</span>';
                }

                return '<span class="badge badge-light border text-muted px-2 py-1"><i class="fas fa-unlink mr-1 text-warning"></i>Belum Terhubung</span>';
            })
            ->addColumn('token_display', function ($row) use ($utilityKkItems, $yearReceipts, $month) {
                $tokenHtml = '';
                if (! $row->token_number) {
                    $tokenHtml = '<div class="text-muted font-italic" style="font-size: 0.74rem;">- (Bukan Token)</div>';
                } else {
                    $clean = $row->clean_token_number;
                    $tokenHtml = '<div class="d-inline-flex align-items-center gap-1 bg-warning-subtle border border-warning px-2 py-0.5 rounded" style="background-color: #fef3c7; border-color: #fde68a;">'.
                           '<span class="font-tabular font-weight-bold text-dark font-monospace" style="font-size: 0.78rem; letter-spacing: 0.5px;">'.e($row->token_number).'</span>'.
                           '<button type="button" class="btn btn-xs btn-link text-warning-emphasis p-0 ml-1.5 btn-copy-token" data-token="'.e($clean).'" title="Salin 20 Angka Token"><i class="fas fa-copy"></i></button>'.
                           '</div>';
                }

                $kkHtml = '';
                if ($row->bos_kertas_kerja_item_id && $row->kertasKerjaItem) {
                    $kk = $row->kertasKerjaItem;
                    $kkTotal = (float) ($kk->total_amount ?: ($kk->price * ($kk->volume ?: 1)));
                    $badgeKk = '<span class="badge badge-success px-2 py-0.5 font-weight-500" style="white-space: normal; text-align: left; display: inline-block; max-width: 230px; font-size: 0.72rem;" title="'.e($kk->kegiatan).' - Alokasi Bulan Ini: Rp '.number_format($kkTotal, 0, ',', '.').'"><i class="fas fa-link mr-1"></i>'.e($kk->description).' ('.e($kk->month).')</span>';

                    // Hitung sisa anggaran kumulatif s.d. bulan terpilih / bulan transaksi
                    $targetMonthNum = (! empty($month) && $month !== 'all')
                        ? $this->resolveMonthNumber($month)
                        : ($this->resolveMonthNumber($row->billing_month) ?: ($row->transaction_date ? (int) $row->transaction_date->format('m') : 12));

                    $targetMonthPad = str_pad((string) $targetMonthNum, 2, '0', STR_PAD_LEFT);
                    $targetMonthName = $this->indonesianMonths[$targetMonthPad] ?? ('Bulan '.$targetMonthNum);

                    // Cocokkan item kertas kerja terkait
                    $matchedKk = $utilityKkItems->filter(function ($item) use ($row, $kk) {
                        if ($row->utility_profile_id && $item->utility_profile_id) {
                            return (int) $item->utility_profile_id === (int) $row->utility_profile_id;
                        }
                        if ($item->kegiatan === $kk->kegiatan && $item->description === $kk->description) {
                            return true;
                        }

                        return false;
                    });

                    $budgetYtd = (float) $matchedKk->filter(fn ($item) => $this->resolveMonthNumber($item->month) <= $targetMonthNum)->sum('total_amount');

                    // Cocokkan realisasi pembayaran terkait s.d. target month
                    $matchedKkIds = $matchedKk->pluck('id')->filter()->all();
                    $matchedReceipts = $yearReceipts->filter(function ($item) use ($row, $matchedKkIds, $targetMonthNum) {
                        $rM = $this->resolveMonthNumber($item->billing_month) ?: ($item->transaction_date ? (int) $item->transaction_date->format('m') : 0);
                        if ($rM > $targetMonthNum) {
                            return false;
                        }
                        if ($row->utility_profile_id && $item->utility_profile_id) {
                            return (int) $item->utility_profile_id === (int) $row->utility_profile_id;
                        }
                        if ($item->bos_kertas_kerja_item_id && in_array($item->bos_kertas_kerja_item_id, $matchedKkIds)) {
                            return true;
                        }

                        return false;
                    });

                    $spentYtd = (float) $matchedReceipts->sum('total_amount');
                    $remainingYtd = $budgetYtd - $spentYtd;
                    $isOverBudget = ($remainingYtd < 0);

                    $budgetTooltip = 'Anggaran s.d. '.$targetMonthName.': Rp '.number_format($budgetYtd, 0, ',', '.').' | Terbayar s.d. '.$targetMonthName.': Rp '.number_format($spentYtd, 0, ',', '.');

                    if ($isOverBudget) {
                        $warningHtml = '<div class="mt-1" title="'.e($budgetTooltip).'">'.
                            '<span class="badge badge-danger text-white px-1.5 py-0.5 font-weight-bold" style="background-color: #ef4444 !important; font-size: 0.69rem; display: inline-flex; align-items: center; gap: 3px;">'.
                            '<i class="fas fa-exclamation-triangle"></i> Over: -Rp '.number_format(abs($remainingYtd), 0, ',', '.').' (s.d. '.$targetMonthName.')'.
                            '</span>'.
                            '</div>';
                    } else {
                        $warningHtml = '<div class="mt-1 font-tabular" style="font-size: 0.71rem; color: #16a34a;" title="'.e($budgetTooltip).'">'.
                            '<i class="fas fa-check-circle mr-1"></i>Sisa Anggaran: <strong>Rp '.number_format($remainingYtd, 0, ',', '.').'</strong> (s.d. '.$targetMonthName.')'.
                            '</div>';
                    }

                    $kkHtml = '<div class="mt-1">'.$badgeKk.$warningHtml.'</div>';
                } elseif ($row->profile && ! $row->profile->is_budgeted) {
                    $kkHtml = '<div class="mt-1"><span class="badge badge-secondary px-2 py-0.5" style="font-size: 0.72rem;" title="SMP PGRI / Internal: Tidak dianggarkan BOS"><i class="fas fa-ban mr-1"></i>Non-BOS</span></div>';
                } else {
                    $kkHtml = '<div class="mt-1"><span class="badge badge-light border text-muted px-2 py-0.5" style="font-size: 0.72rem;"><i class="fas fa-unlink mr-1 text-warning"></i>Belum Terhubung</span></div>';
                }

                return '<div>'.$tokenHtml.$kkHtml.'</div>';
            })
            ->addColumn('receipt_image_display', function ($row) {
                if (! $row->receipt_file) {
                    return '<span class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-image-slash mr-1"></i>Tidak ada</span>';
                }

                $url = $row->receipt_url;

                return '<button type="button" class="btn btn-xs btn-outline-info btn-view-receipt p-1" data-url="'.e($url).'" data-title="'.e($row->customer_name ?: 'Struk Utilitas').'" title="Lihat Foto Struk">'.
                       '<img src="'.e($url).'" alt="Struk" class="rounded border" style="width: 38px; height: 50px; object-fit: cover;">'.
                       '<span class="d-block text-center mt-0.5" style="font-size: 0.65rem;">Lihat</span>'.
                       '</button>';
            })
            ->addColumn('status_display', function ($row) {
                return $row->ocr_status_badge;
            })
            ->addColumn('action', function ($row) {
                $btnView = '<button type="button" class="btn btn-xs btn-info btn-detail mr-1" data-id="'.$row->id.'" title="Detail Bukti"><i class="fas fa-eye"></i></button>';
                $btnEdit = '<button type="button" class="btn btn-xs btn-warning btn-edit mr-1" data-id="'.$row->id.'" data-kertas-kerja-id="'.$row->bos_kertas_kerja_item_id.'" title="Edit Data"><i class="fas fa-edit"></i></button>';
                $btnRetry = '';
                if (in_array($row->ocr_status, ['failed', 'pending'])) {
                    $spin = $row->ocr_status === 'processing' ? ' fa-spin' : '';
                    $btnRetry = '<button type="button" class="btn btn-xs btn-outline-primary btn-retry-ocr mr-1" data-id="'.$row->id.'" title="Proses Ulang OCR"><i class="fas fa-sync-alt'.$spin.'"></i></button>';
                }
                $btnDelete = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-id="'.$row->id.'" data-url="'.route('bos.utilitas.destroy', $row->id).'" data-name="'.e($row->customer_name ?: 'Struk').'" title="Hapus"><i class="fas fa-trash"></i></button>';

                return '<div class="d-inline-flex align-items-center text-nowrap">'.$btnView.$btnEdit.$btnRetry.$btnDelete.'</div>';
            })
            ->rawColumns(['date_display', 'profile_display', 'account_display', 'tariff_display', 'breakdown_display', 'total_display', 'kertas_kerja_display', 'token_display', 'receipt_image_display', 'status_display', 'action'])
            ->make(true);
    }

    public function scanReceipt(Request $request, UtilityReceiptOcrService $ocrService)
    {
        $request->validate([
            'receipt_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:20480',
            'image_base64' => 'nullable|string',
            'raw_text' => 'nullable|string',
            'engine' => 'nullable|string|in:auto,local',
        ]);

        $file = $request->file('receipt_file') ?: $request->input('image_base64');
        $rawText = $request->input('raw_text');
        $engine = $request->input('engine', 'local');

        if (! $file && empty($rawText)) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas foto struk atau teks pemindaian tidak ditemukan.',
            ], 422);
        }

        $result = $ocrService->scan($file ?: '', $rawText, $engine);

        if ($result['success'] && ! empty($result['data'])) {
            $duplicate = UtilityReceipt::findDuplicate($result['data']);
            if ($duplicate) {
                $result['duplicate'] = [
                    'id' => $duplicate->id,
                    'billing_month' => $duplicate->billing_month,
                    'fiscal_year' => $duplicate->fiscal_year,
                    'total_amount' => $duplicate->total_amount,
                    'receipt_number' => $duplicate->receipt_number,
                    'transaction_date' => $duplicate->transaction_date ? $duplicate->transaction_date->format('d/m/Y H:i') : '-',
                    'profile_name' => $duplicate->profile ? $duplicate->profile->name : ($duplicate->customer_name ?: 'Utilitas'),
                ];
            }
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $this->sanitizeReceiptInputs($request);

        $validated = $request->validate([
            'utility_profile_id' => 'nullable|exists:utility_profiles,id',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'billing_month' => 'required|string|max:20',
            'transaction_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
            'customer_number' => 'nullable|string|max:100',
            'meter_number' => 'nullable|string|max:100',
            'tariff_power' => 'nullable|string|max:100',
            'kwh_amount' => 'nullable|numeric',
            'base_amount' => 'nullable|numeric',
            'tax_pbjt' => 'nullable|numeric',
            'tax_ppn' => 'nullable|numeric',
            'admin_fee' => 'nullable|numeric',
            'total_amount' => 'required|numeric|min:0',
            'token_number' => 'nullable|string|max:100',
            'payment_merchant' => 'nullable|string|max:150',
            'merchant_name' => 'nullable|string|max:150',
            'receipt_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:20480',
            'image_base64' => 'nullable|string',
            'notes' => 'nullable|string',
            'ocr_raw_data' => 'nullable',
            'bos_kertas_kerja_item_id' => 'nullable|exists:bos_kertas_kerja_items,id',
        ]);

        // Cek duplikasi struk
        $duplicate = UtilityReceipt::findDuplicate($validated);
        if ($duplicate) {
            $profileName = $duplicate->profile ? $duplicate->profile->name : ($duplicate->customer_name ?: 'Utilitas');

            return response()->json([
                'success' => false,
                'message' => 'Struk ini terdeteksi DUPLIKAT dan sudah pernah tersimpan sebelumnya pada data ID #'.$duplicate->id.' ('.$profileName.', Bulan '.$duplicate->billing_month.' '.$duplicate->fiscal_year.' - Rp '.number_format((float) $duplicate->total_amount, 0, ',', '.').'). Sistem mencegah penyimpanan ganda.',
                'duplicate_id' => $duplicate->id,
            ], 422);
        }

        // Simpan file struk jika ada (utamakan image_base64 jika tersedia dan telah diputar/diselaraskan)
        $filePath = null;
        if (! empty($request->input('image_base64')) && str_starts_with($request->input('image_base64'), 'data:image')) {
            $base64 = $request->input('image_base64');
            [$meta, $data] = explode(',', $base64, 2);
            $ext = 'jpg';
            if (preg_match('/data:image\/([a-zA-Z+]+);base64/', $meta, $matches)) {
                $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            }
            $fileName = 'utility_receipts/'.Str::random(30).'.'.$ext;
            Storage::disk('public')->put($fileName, base64_decode($data));
            $filePath = $fileName;
        } elseif ($request->hasFile('receipt_file')) {
            $filePath = $request->file('receipt_file')->store('utility_receipts', 'public');
        }

        // Format raw data jika berupa string json
        $rawOcr = $request->input('ocr_raw_data');
        if (is_string($rawOcr)) {
            $rawOcr = json_decode($rawOcr, true);
        }

        $profileId = $validated['utility_profile_id'] ?? null;
        $kertasKerjaItemId = $validated['bos_kertas_kerja_item_id'] ?? null;
        if ($profileId) {
            $profile = UtilityProfile::find($profileId);
            if ($profile && ! $profile->is_budgeted) {
                $kertasKerjaItemId = null; // SMP PGRI / Non-BOS tidak boleh dihubungkan ke kertas kerja BOS
            } elseif ($profile && $profile->is_budgeted && empty($kertasKerjaItemId)) {
                // Auto-match ke kertas kerja item jika belum terisi
                $kertasKerjaItemId = $this->findMatchingKertasKerjaItemId(
                    (int) $profileId,
                    (int) $validated['fiscal_year'],
                    $validated['billing_month'],
                    (float) ($validated['total_amount'] ?? 0)
                );
            }
        }
        if ($kertasKerjaItemId) {
            $kki = BosKertasKerjaItem::find($kertasKerjaItemId);
            if ($kki && ! $this->isAllowedUtilityKegiatan($kki)) {
                $kertasKerjaItemId = null;
            }
        }

        $receipt = UtilityReceipt::create([
            'utility_profile_id' => $profileId,
            'bos_kertas_kerja_item_id' => $kertasKerjaItemId,
            'fiscal_year' => (int) $validated['fiscal_year'],
            'billing_month' => $validated['billing_month'],
            'transaction_date' => $validated['transaction_date'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_number' => $validated['customer_number'] ?? null,
            'meter_number' => $validated['meter_number'] ?? null,
            'tariff_power' => $validated['tariff_power'] ?? null,
            'kwh_amount' => $validated['kwh_amount'] ?? null,
            'base_amount' => $validated['base_amount'] ?? 0,
            'tax_pbjt' => $validated['tax_pbjt'] ?? 0,
            'tax_ppn' => $validated['tax_ppn'] ?? 0,
            'admin_fee' => $validated['admin_fee'] ?? 0,
            'total_amount' => $validated['total_amount'],
            'token_number' => $validated['token_number'] ?? null,
            'payment_merchant' => $validated['payment_merchant'] ?? $validated['merchant_name'] ?? $request->input('merchant_name') ?? null,
            'receipt_file' => $filePath,
            'ocr_status' => 'completed',
            'ocr_processed_at' => now(),
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran utilitas berhasil disimpan.',
            'data' => $receipt,
        ]);
    }

    public function batchUpload(Request $request)
    {
        $request->validate([
            'receipt_files' => 'required|array|min:1|max:20',
            'receipt_files.*' => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:20480',
            'fiscal_year' => 'nullable|integer|min:2020|max:2099',
            'billing_month' => 'nullable|string|max:20',
        ]);

        $fiscalYear = (int) $request->input('fiscal_year', session('fiscal_year', date('Y')));
        $billingMonth = $request->input('billing_month', date('m'));
        if (strlen((string) $billingMonth) === 1) {
            $billingMonth = '0'.$billingMonth;
        }

        $uploadedReceipts = [];
        $files = $request->file('receipt_files');

        foreach ($files as $file) {
            $filePath = $file->store('utility_receipts', 'public');

            $receipt = UtilityReceipt::create([
                'fiscal_year' => $fiscalYear,
                'billing_month' => $billingMonth,
                'transaction_date' => now(),
                'total_amount' => 0,
                'receipt_file' => $filePath,
                'ocr_status' => 'pending',
                'ocr_attempts' => 0,
                'created_by' => Auth::id(),
            ]);

            // Dispatch background OCR processing job
            ProcessUtilityReceiptOcrJob::dispatch($receipt);

            $uploadedReceipts[] = [
                'id' => $receipt->id,
                'filename' => $file->getClientOriginalName(),
                'receipt_url' => $receipt->receipt_url,
                'ocr_status' => $receipt->ocr_status,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedReceipts).' berkas struk berhasil diunggah dan dijadwalkan untuk diproses di latar belakang.',
            'total_uploaded' => count($uploadedReceipts),
            'receipts' => $uploadedReceipts,
        ]);
    }

    public function pollStatus(Request $request)
    {
        $year = $request->input('year', session('fiscal_year', date('Y')));
        $isAllYears = ($year === 'all');
        $month = $request->input('month', date('m'));
        $isAllMonths = ($month === 'all');

        if ($month !== 'all' && strlen((string) $month) === 1) {
            $month = '0'.$month;
        }
        $monthName = $isAllMonths ? 'Semua Bulan' : ($this->indonesianMonths[$month] ?? 'Januari');

        $pendingCount = UtilityReceipt::whereIn('ocr_status', ['pending', 'processing'])->count();
        $failedCount = UtilityReceipt::where('ocr_status', 'failed')->count();

        $recentQuery = UtilityReceipt::with('profile');
        if (! $isAllYears) {
            $recentQuery->where('fiscal_year', (int) $year);
        }

        $recentReceipts = $recentQuery->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'customer_name' => $r->customer_name ?: ($r->profile?->name ?? 'Struk Sedang Diproses...'),
                    'profile_name' => $r->profile?->name ?? '-',
                    'total_amount' => $r->formatted_total_amount,
                    'ocr_status' => $r->ocr_status,
                    'ocr_badge' => $r->ocr_status_badge,
                    'ocr_error_message' => $r->ocr_error_message,
                    'receipt_url' => $r->receipt_url,
                    'updated_at' => $r->updated_at?->diffForHumans(),
                ];
            });

        // Matrix stats
        $profiles = UtilityProfile::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        $monthReceiptsQuery = UtilityReceipt::where('ocr_status', 'completed');
        if (! $isAllYears) {
            $monthReceiptsQuery->where('fiscal_year', (int) $year);
        }
        if (! $isAllMonths) {
            $monthReceiptsQuery->where(function ($q) use ($month, $monthName) {
                $q->where('billing_month', $month)
                    ->orWhere('billing_month', $monthName)
                    ->orWhere(function ($sub) use ($month) {
                        $sub->where(function ($emptyQ) {
                            $emptyQ->whereNull('billing_month')->orWhere('billing_month', '');
                        })->whereMonth('transaction_date', $month);
                    });
            });
        }
        $monthReceipts = $monthReceiptsQuery->get();

        $paidCount = 0;
        foreach ($profiles as $p) {
            if ($monthReceipts->where('utility_profile_id', $p->id)->isNotEmpty()) {
                $paidCount++;
            }
        }

        $monthTotalExpenses = (float) $monthReceipts->sum('total_amount');
        $totalYearQuery = UtilityReceipt::where('ocr_status', 'completed');
        if (! $isAllYears) {
            $totalYearQuery->where('fiscal_year', (int) $year);
        }
        $yearTotalExpenses = (float) $totalYearQuery->sum('total_amount');

        return response()->json([
            'success' => true,
            'has_pending' => $pendingCount > 0,
            'pending_count' => $pendingCount,
            'failed_count' => $failedCount,
            'receipts' => $recentReceipts,
            'matrix' => [
                'paid_count' => $paidCount,
                'total_profiles' => $profiles->count(),
                'month_total' => 'Rp '.number_format($monthTotalExpenses, 0, ',', '.'),
                'month_total_raw' => $monthTotalExpenses,
                'year_total' => 'Rp '.number_format($yearTotalExpenses, 0, ',', '.'),
                'year_total_raw' => $yearTotalExpenses,
            ],
        ]);
    }

    public function retryOcr(Request $request, $id)
    {
        $receipt = UtilityReceipt::findOrFail($id);

        if (! $receipt->receipt_file || ! Storage::disk('public')->exists($receipt->receipt_file)) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas gambar struk tidak ditemukan pada server untuk diproses ulang.',
            ], 422);
        }

        $receipt->update([
            'ocr_status' => 'pending',
            'ocr_error_message' => null,
            'ocr_attempts' => 0,
        ]);

        ProcessUtilityReceiptOcrJob::dispatch($receipt);

        return response()->json([
            'success' => true,
            'message' => 'Pemindaian ulang OCR berhasil dijadwalkan.',
            'receipt' => $receipt,
        ]);
    }

    public function show($id)
    {
        $receipt = UtilityReceipt::with(['profile', 'creator', 'kertasKerjaItem'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'receipt' => $receipt,
            'receipt_url' => $receipt->receipt_url,
            'clean_token' => $receipt->clean_token_number,
            'kertas_kerja_id' => $receipt->bos_kertas_kerja_item_id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->sanitizeReceiptInputs($request);

        $receipt = UtilityReceipt::findOrFail($id);

        $validated = $request->validate([
            'utility_profile_id' => 'nullable|exists:utility_profiles,id',
            'bos_kertas_kerja_item_id' => 'nullable|exists:bos_kertas_kerja_items,id',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'billing_month' => 'required|string|max:20',
            'transaction_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
            'customer_number' => 'nullable|string|max:100',
            'meter_number' => 'nullable|string|max:100',
            'tariff_power' => 'nullable|string|max:100',
            'kwh_amount' => 'nullable|numeric',
            'base_amount' => 'nullable|numeric',
            'tax_pbjt' => 'nullable|numeric',
            'tax_ppn' => 'nullable|numeric',
            'admin_fee' => 'nullable|numeric',
            'total_amount' => 'required|numeric|min:0',
            'token_number' => 'nullable|string|max:100',
            'payment_merchant' => 'nullable|string|max:150',
            'receipt_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:20480',
            'image_base64' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (! empty($request->input('image_base64')) && str_starts_with($request->input('image_base64'), 'data:image')) {
            if ($receipt->receipt_file && Storage::disk('public')->exists($receipt->receipt_file)) {
                Storage::disk('public')->delete($receipt->receipt_file);
            }
            $base64 = $request->input('image_base64');
            [$meta, $data] = explode(',', $base64, 2);
            $ext = 'jpg';
            if (preg_match('/data:image\/([a-zA-Z+]+);base64/', $meta, $matches)) {
                $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            }
            $fileName = 'utility_receipts/'.Str::random(30).'.'.$ext;
            Storage::disk('public')->put($fileName, base64_decode($data));
            $validated['receipt_file'] = $fileName;
        } elseif ($request->hasFile('receipt_file')) {
            if ($receipt->receipt_file && Storage::disk('public')->exists($receipt->receipt_file)) {
                Storage::disk('public')->delete($receipt->receipt_file);
            }
            $validated['receipt_file'] = $request->file('receipt_file')->store('utility_receipts', 'public');
        }

        $profileId = $validated['utility_profile_id'] ?? null;
        $kertasKerjaItemId = $validated['bos_kertas_kerja_item_id'] ?? null;
        if ($profileId) {
            $profile = UtilityProfile::find($profileId);
            if ($profile && ! $profile->is_budgeted) {
                $kertasKerjaItemId = null; // SMP PGRI / Non-BOS tidak boleh dihubungkan ke kertas kerja BOS
            } elseif ($profile && $profile->is_budgeted && empty($kertasKerjaItemId)) {
                $kertasKerjaItemId = $this->findMatchingKertasKerjaItemId(
                    (int) $profileId,
                    (int) $validated['fiscal_year'],
                    $validated['billing_month'],
                    (float) ($validated['total_amount'] ?? 0)
                );
            }
        }
        if ($kertasKerjaItemId) {
            $kki = BosKertasKerjaItem::find($kertasKerjaItemId);
            if ($kki && ! $this->isAllowedUtilityKegiatan($kki)) {
                $kertasKerjaItemId = null;
            }
        }
        $validated['bos_kertas_kerja_item_id'] = $kertasKerjaItemId;
        $validated['ocr_status'] = 'completed';

        $receipt->update($validated);
        $receipt->load(['profile', 'kertasKerjaItem']);

        return response()->json([
            'success' => true,
            'message' => 'Data bukti pembayaran berhasil diperbarui.',
            'data' => $receipt,
            'receipt' => $receipt,
        ]);
    }

    public function destroy($id)
    {
        $receipt = UtilityReceipt::findOrFail($id);

        if ($receipt->receipt_file && Storage::disk('public')->exists($receipt->receipt_file)) {
            Storage::disk('public')->delete($receipt->receipt_file);
        }

        $receipt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran utilitas berhasil dihapus.',
        ]);
    }

    /**
     * Sanitize monetary and decimal inputs by stripping non-numeric formatting
     */
    private function sanitizeReceiptInputs(Request $request): void
    {
        $monetaryFields = ['base_amount', 'admin_fee', 'tax_pbjt', 'tax_ppn', 'total_amount'];
        foreach ($monetaryFields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if (is_string($val)) {
                    $trimmed = trim($val);
                    if ($trimmed === '') {
                        $request->merge([$field => null]);
                    } elseif (preg_match('/^\d+\.\d{1,2}$/', $trimmed)) {
                        $request->merge([$field => (float) $trimmed]);
                    } else {
                        $cleaned = preg_replace('/[^0-9]/', '', $trimmed);
                        $request->merge([$field => $cleaned !== '' ? (float) $cleaned : 0]);
                    }
                }
            }
        }

        if ($request->has('kwh_amount')) {
            $val = $request->input('kwh_amount');
            if (is_string($val)) {
                $trimmed = trim($val);
                if ($trimmed === '') {
                    $request->merge(['kwh_amount' => null]);
                } else {
                    if (str_contains($trimmed, ',')) {
                        $cleaned = str_replace('.', '', $trimmed);
                        $cleaned = str_replace(',', '.', $cleaned);
                    } else {
                        $cleaned = $trimmed;
                    }
                    $request->merge(['kwh_amount' => is_numeric($cleaned) ? (float) $cleaned : null]);
                }
            }
        }
    }

    /**
     * Pastikan item kertas kerja termasuk dalam kegiatan utilitas resmi BOS:
     * - Pembayaran daya listrik (06.07.01 / 07.06.01)
     * - Pembayaran biaya telepon (06.07.04 / 07.06.04)
     * - Pembayaran jasa internet (06.07.05 / 07.06.05)
     */
    private function isAllowedUtilityKegiatan(BosKertasKerjaItem $item): bool
    {
        $kegiatan = strtolower($item->kegiatan ?? '');
        $code = str_replace('.', '', (string) ($item->program_code ?? ''));

        if (str_contains($kegiatan, 'daya listrik') || str_contains($kegiatan, 'daya dan jasa listrik') || str_starts_with($code, '060701') || str_starts_with($code, '070601')) {
            return true;
        }

        if (str_contains($kegiatan, 'biaya telepon') || str_contains($kegiatan, 'pembayaran telepon') || str_starts_with($code, '060704') || str_starts_with($code, '070604')) {
            return true;
        }

        if (str_contains($kegiatan, 'jasa internet') || str_contains($kegiatan, 'pembayaran internet') || str_starts_with($code, '060705') || str_starts_with($code, '070605')) {
            return true;
        }

        return false;
    }

    /**
     * Konversi string nama bulan atau angka bulan ke format integer 1..12
     */
    private function resolveMonthNumber(mixed $val): int
    {
        if (empty($val) || $val === 'all') {
            return 0;
        }

        if (is_numeric($val)) {
            $num = (int) $val;

            return ($num >= 1 && $num <= 12) ? $num : 0;
        }

        $lower = strtolower(trim((string) $val));
        $map = [
            'januari' => 1, 'january' => 1, 'jan' => 1, '01' => 1, '1' => 1,
            'februari' => 2, 'february' => 2, 'feb' => 2, '02' => 2, '2' => 2,
            'maret' => 3, 'march' => 3, 'mar' => 3, '03' => 3, '3' => 3,
            'april' => 4, 'apr' => 4, '04' => 4, '4' => 4,
            'mei' => 5, 'may' => 5, '05' => 5, '5' => 5,
            'juni' => 6, 'june' => 6, 'jun' => 6, '06' => 6, '6' => 6,
            'juli' => 7, 'july' => 7, 'jul' => 7, '07' => 7, '7' => 7,
            'agustus' => 8, 'august' => 8, 'agu' => 8, 'aug' => 8, '08' => 8, '8' => 8,
            'september' => 9, 'sep' => 9, '09' => 9, '9' => 9,
            'oktober' => 10, 'october' => 10, 'okt' => 10, 'oct' => 10, '10' => 10,
            'november' => 11, 'nov' => 11, '11' => 11,
            'desember' => 12, 'december' => 12, 'des' => 12, 'dec' => 12, '12' => 12,
        ];

        return $map[$lower] ?? 0;
    }

    /**
     * Cari ID BosKertasKerjaItem yang paling cocok berdasarkan profil, tahun, bulan, dan nominal
     */
    public function findMatchingKertasKerjaItemId(int $profileId, int $fiscalYear, string $billingMonth, float $totalAmount = 0): ?int
    {
        $profile = UtilityProfile::find($profileId);
        if (! $profile || ! $profile->is_budgeted) {
            return null;
        }

        $monthNum = $this->resolveMonthNumber($billingMonth);
        if ($monthNum < 1 || $monthNum > 12) {
            return null;
        }

        $monthPad = str_pad((string) $monthNum, 2, '0', STR_PAD_LEFT);
        $monthName = $this->indonesianMonths[$monthPad] ?? '';

        // Prioritas 1: Cari yang eksplisit punya utility_profile_id cocok
        $directMatch = BosKertasKerjaItem::where('utility_profile_id', $profileId)
            ->where('year', $fiscalYear)
            ->where(function ($q) use ($monthNum, $monthPad, $monthName) {
                $q->where('month', $monthName)
                    ->orWhere('month', $monthPad)
                    ->orWhere('month', (string) $monthNum);
            })
            ->first();

        if ($directMatch) {
            return $directMatch->id;
        }

        // Prioritas 2: Fallback berdasarkan kategori profil dan kemiripan nominal jika profil belum terhubung di item
        $category = $profile->category;
        $query = BosKertasKerjaItem::whereNull('utility_profile_id')
            ->where('year', $fiscalYear)
            ->where(function ($q) use ($monthNum, $monthPad, $monthName) {
                $q->where('month', $monthName)
                    ->orWhere('month', $monthPad)
                    ->orWhere('month', (string) $monthNum);
            });

        if ($category === 'internet') {
            $query->where(function ($q) {
                $q->where('kegiatan', 'like', '%jasa internet%')
                    ->orWhere('program_code', 'like', '06.07.05%')
                    ->orWhere('program_code', 'like', '07.06.05%');
            });
        } elseif ($category === 'telkom_speedy') {
            $query->where(function ($q) {
                $q->where('kegiatan', 'like', '%telepon%')
                    ->orWhere('kegiatan', 'like', '%jasa internet%')
                    ->orWhere('program_code', 'like', '06.07.04%')
                    ->orWhere('program_code', 'like', '07.06.04%')
                    ->orWhere('program_code', 'like', '06.07.05%')
                    ->orWhere('program_code', 'like', '07.06.05%');
            });
        } else {
            $query->where(function ($q) {
                $q->where('kegiatan', 'like', '%listrik%')
                    ->orWhere('program_code', 'like', '06.07.01%')
                    ->orWhere('program_code', 'like', '07.06.01%');
            });
        }

        $candidates = $query->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            return $candidates->first()->id;
        }

        // Jika ada banyak kandidat, pilih yang selisih nominalnya paling kecil
        $closest = $candidates->sortBy(function ($item) use ($totalAmount) {
            $itemTotal = (float) ($item->total_amount ?: ($item->price * ($item->volume ?: 1)));

            return abs($itemTotal - $totalAmount);
        })->first();

        return $closest?->id;
    }
}

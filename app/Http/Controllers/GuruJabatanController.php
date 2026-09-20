<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\Jabatan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class GuruJabatanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $scope = $request->input('scope', 'signatory');
            $statusFilter = $request->input('status');
            $today = Carbon::today()->toDateString();
            $in30Days = Carbon::today()->addDays(30)->toDateString();

            $query = Jabatan::query()
                ->with(['guruJabatans' => function ($q) {
                    $q->with('guru')->orderByDesc('start_date')->orderByDesc('id');
                }]);

            if ($scope === 'signatory') {
                $query->where('is_signatory', true);
            }

            if ($statusFilter === 'active') {
                $query->whereHas('guruJabatans', function ($q) use ($today) {
                    $q->where('is_active', true)
                        ->where(function ($sub) use ($today) {
                            $sub->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                        });
                });
            } elseif ($statusFilter === 'expiring_soon') {
                $query->whereHas('guruJabatans', function ($q) use ($today, $in30Days) {
                    $q->where('is_active', true)
                        ->whereNotNull('end_date')
                        ->whereDate('end_date', '>=', $today)
                        ->whereDate('end_date', '<=', $in30Days);
                });
            } elseif ($statusFilter === 'expired') {
                $query->whereHas('guruJabatans', function ($q) use ($today) {
                    $q->where('is_active', true)
                        ->whereNotNull('end_date')
                        ->whereDate('end_date', '<', $today);
                });
            } elseif ($statusFilter === 'vacant') {
                $query->whereDoesntHave('guruJabatans', function ($q) use ($today) {
                    $q->where('is_active', true)
                        ->where(function ($sub) use ($today) {
                            $sub->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                        });
                });
            }

            // Hierarchical sorting: KS first, then BEND_BOSP, BEND_BPOPP, BEND_SEKOLAH, other signatories, WAKA, then others
            $query->orderByRaw("CASE 
                WHEN code IN ('KS', 'KEPSEK') THEN 1 
                WHEN code IN ('BEND_BOSP', 'BEND_BOS') THEN 2 
                WHEN code = 'BEND_BPOPP' THEN 3 
                WHEN code = 'BEND_SEKOLAH' THEN 4 
                WHEN is_signatory = 1 THEN 5 
                WHEN code LIKE 'WAKA%' THEN 6 
                ELSE 7 
            END, name ASC");

            return DataTables::of($query)
                ->filter(function ($q) use ($request) {
                    if ($search = $request->input('search.value')) {
                        $q->where(function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereHas('guruJabatans.guru', function ($gq) use ($search) {
                                    $gq->where('name', 'like', "%{$search}%")
                                        ->orWhere('nip', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->addIndexColumn()
                ->addColumn('jabatan_display', function ($row) {
                    $signatoryBadge = $row->is_signatory
                        ? '<span class="badge badge-primary ml-1" title="Pejabat Penandatangan Dokumen SPJ"><i class="fas fa-file-signature mr-1"></i>SPJ</span>'
                        : '';
                    $count = $row->guruJabatans->count();
                    $historyBadge = $count > 0
                        ? '<span class="badge badge-light border ml-1 font-tabular text-muted" title="'.$count.' riwayat penugasan"><i class="fas fa-history mr-1"></i>'.$count.' SK</span>'
                        : '<span class="badge badge-light border ml-1 text-muted" style="font-size:0.68rem;">Belum ada SK</span>';

                    return '<div><strong class="text-dark" style="font-size:0.875rem;">'.e($row->name).'</strong>'.$signatoryBadge.$historyBadge.'</div>';
                })
                ->addColumn('pejabat_display', function ($row) use ($today) {
                    $activeHolders = $row->guruJabatans->filter(function ($item) use ($today) {
                        if (! $item->is_active) {
                            return false;
                        }
                        $startOk = is_null($item->start_date) || $item->start_date->toDateString() <= $today;
                        $endOk = is_null($item->end_date) || $item->end_date->toDateString() >= $today;

                        return $startOk && $endOk;
                    });
                    if ($activeHolders->isEmpty()) {
                        $activeHolders = $row->guruJabatans->where('is_active', true);
                    }

                    if ($activeHolders->count() > 1) {
                        $html = '<div class="mb-1"><span class="badge badge-info"><i class="fas fa-users mr-1"></i>'.$activeHolders->count().' Anggota Aktif</span></div>';
                        $html .= '<ul class="list-unstyled mb-0" style="font-size: 0.8125rem;">';
                        foreach ($activeHolders as $holder) {
                            if ($holder->guru) {
                                $nip = $holder->guru->nip ? ' <span class="text-muted font-tabular" style="font-size:0.75rem;">(NIP. '.e($holder->guru->nip).')</span>' : '';
                                $html .= '<li class="py-1 d-flex align-items-start border-bottom border-light"><i class="fas fa-user-check text-success mr-2 mt-1" style="font-size:0.72rem;"></i><div><a href="'.route('guru.show', $holder->guru_id).'" class="font-weight-bold text-dark text-decoration-none">'.e($holder->guru->full_name_with_title).'</a>'.$nip.'</div></li>';
                            }
                        }
                        $html .= '</ul>';

                        return $html;
                    } elseif ($activeHolders->count() === 1) {
                        $active = $activeHolders->first();
                        if ($active && $active->guru) {
                            $name = '<a href="'.route('guru.show', $active->guru_id).'" class="font-weight-bold text-dark text-decoration-none">'.e($active->guru->full_name_with_title).'</a>';
                            $nip = $active->guru->nip ? '<small class="text-muted d-block font-tabular">NIP. '.e($active->guru->nip).'</small>' : '';

                            return $name.$nip;
                        }
                    }

                    $latest = $row->guruJabatans->first();
                    if ($latest && $latest->guru) {
                        $name = '<div class="text-dark font-weight-500">'.e($latest->guru->full_name_with_title).'</div>';
                        $statusBadge = '<span class="badge badge-warning text-dark font-tabular mt-1" style="font-size: 0.68rem;"><i class="fas fa-user-clock mr-1"></i>Demisioner (Belum Ada Pengganti)</span>';

                        return $name.$statusBadge;
                    }

                    return '<span class="badge badge-light text-muted border"><i class="fas fa-user-slash mr-1 text-danger"></i>Formasi Belum Terisi</span>';
                })
                ->addColumn('sk_display', function ($row) use ($today) {
                    $activeHolders = $row->guruJabatans->filter(function ($item) use ($today) {
                        if (! $item->is_active) {
                            return false;
                        }
                        $startOk = is_null($item->start_date) || $item->start_date->toDateString() <= $today;
                        $endOk = is_null($item->end_date) || $item->end_date->toDateString() >= $today;

                        return $startOk && $endOk;
                    });
                    if ($activeHolders->isEmpty()) {
                        $activeHolders = $row->guruJabatans->where('is_active', true);
                    }

                    if ($activeHolders->isNotEmpty()) {
                        $uniqueSks = $activeHolders->pluck('sk_number')->filter()->unique();
                        $primary = $activeHolders->first();

                        if ($uniqueSks->count() > 1) {
                            $html = '<div class="font-tabular font-weight-500 text-dark">'.$uniqueSks->count().' SK Berbeda</div>';
                            $filesCount = $activeHolders->filter(fn ($h) => ! empty($h->sk_file))->count();
                            if ($filesCount > 0) {
                                $html .= '<small class="text-muted d-block font-tabular">'.$filesCount.' Berkas PDF SK</small>';
                            }

                            return $html;
                        } elseif ($primary && ($primary->sk_number || $primary->sk_date || $primary->sk_file)) {
                            $skNum = $primary->sk_number ? '<div class="font-tabular font-weight-500 text-dark">'.e($primary->sk_number).'</div>' : '<span class="text-muted">-</span>';
                            $skDate = $primary->sk_date ? '<small class="text-muted d-block font-tabular">Tgl: '.$primary->sk_date->format('d/m/Y').'</small>' : '';
                            $skFile = '';
                            if ($primary->sk_file) {
                                $skFile = '<a href="'.route('guru-jabatan.sk-file', $primary->id).'" target="_blank" class="badge badge-danger text-white mt-1 d-inline-flex align-items-center" style="font-size: 0.72rem; padding: 3px 6px; text-decoration: none;" title="Buka Dokumen SK (PDF)"><i class="fas fa-file-pdf mr-1"></i> File SK</a>';
                            }

                            return $skNum.$skDate.$skFile;
                        }
                    }

                    $official = $row->guruJabatans->first();
                    if ($official && ($official->sk_number || $official->sk_date || $official->sk_file)) {
                        $skNum = $official->sk_number ? '<div class="font-tabular font-weight-500 text-dark">'.e($official->sk_number).'</div>' : '<span class="text-muted">-</span>';
                        $skDate = $official->sk_date ? '<small class="text-muted d-block font-tabular">Tgl: '.$official->sk_date->format('d/m/Y').'</small>' : '';
                        $skFile = '';
                        if ($official->sk_file) {
                            $skFile = '<a href="'.route('guru-jabatan.sk-file', $official->id).'" target="_blank" class="badge badge-danger text-white mt-1 d-inline-flex align-items-center" style="font-size: 0.72rem; padding: 3px 6px; text-decoration: none;" title="Buka Dokumen SK (PDF)"><i class="fas fa-file-pdf mr-1"></i> File SK</a>';
                        }

                        return $skNum.$skDate.$skFile;
                    }

                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('masa_tugas_display', function ($row) use ($today) {
                    $activeHolders = $row->guruJabatans->filter(function ($item) use ($today) {
                        if (! $item->is_active) {
                            return false;
                        }
                        $startOk = is_null($item->start_date) || $item->start_date->toDateString() <= $today;
                        $endOk = is_null($item->end_date) || $item->end_date->toDateString() >= $today;

                        return $startOk && $endOk;
                    });
                    if ($activeHolders->isEmpty()) {
                        $activeHolders = $row->guruJabatans->where('is_active', true);
                    }

                    $official = $activeHolders->first() ?: $row->guruJabatans->first();

                    if ($official) {
                        $typeBadge = '';
                        if ($official->period_type === 'tahun_pelajaran') {
                            $typeBadge = '<span class="badge badge-info mr-1" style="font-size:0.65rem;" title="Tahun Pelajaran">TP</span>';
                        } elseif ($official->period_type === 'multi_tahun') {
                            $typeBadge = '<span class="badge badge-secondary mr-1" style="font-size:0.65rem;" title="Multi-Tahun">Multi</span>';
                        } elseif ($official->period_type === 'tahun_anggaran') {
                            $typeBadge = '<span class="badge badge-primary mr-1" style="font-size:0.65rem;" title="Tahun Anggaran">TA</span>';
                        }

                        $period = '<div class="d-flex align-items-center flex-wrap">'.$typeBadge.'<span class="badge badge-light border font-tabular">'.e($official->period_year).'</span></div>';
                        $start = $official->start_date ? $official->start_date->format('d/m/Y') : 'Awal';
                        $end = $official->end_date ? $official->end_date->format('d/m/Y') : 'Sekarang';
                        $dates = '<small class="text-muted d-block font-tabular">'.$start.' s/d '.$end.'</small>';

                        $statusExp = '';
                        $expiredCount = $activeHolders->filter(fn ($h) => $h->expiration_status === 'expired')->count();
                        $expiringSoonCount = $activeHolders->filter(fn ($h) => $h->expiration_status === 'expiring_soon')->count();

                        if ($expiredCount > 0) {
                            $statusExp = '<span class="badge badge-danger d-block mt-1 font-tabular" style="font-size: 0.7rem;"><i class="fas fa-exclamation-triangle mr-1"></i>'.$expiredCount.' SK Kadaluarsa</span>';
                        } elseif ($expiringSoonCount > 0) {
                            $minDays = $activeHolders->filter(fn ($h) => $h->expiration_status === 'expiring_soon')->min('days_remaining');
                            $statusExp = '<span class="badge badge-warning text-dark d-block mt-1 font-tabular" style="font-size: 0.7rem;"><i class="fas fa-clock mr-1"></i>H-'.$minDays.' Berakhir</span>';
                        }

                        return $period.$dates.$statusExp;
                    }

                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('status_display', function ($row) use ($today) {
                    $activeHolders = $row->guruJabatans->filter(function ($item) use ($today) {
                        if (! $item->is_active) {
                            return false;
                        }
                        $startOk = is_null($item->start_date) || $item->start_date->toDateString() <= $today;
                        $endOk = is_null($item->end_date) || $item->end_date->toDateString() >= $today;

                        return $startOk && $endOk;
                    });
                    if ($activeHolders->isEmpty()) {
                        $activeHolders = $row->guruJabatans->where('is_active', true);
                    }

                    $expiredCount = $activeHolders->filter(fn ($h) => $h->expiration_status === 'expired')->count();
                    $expiringSoonCount = $activeHolders->filter(fn ($h) => $h->expiration_status === 'expiring_soon')->count();

                    if ($activeHolders->count() > 1) {
                        if ($expiredCount > 0) {
                            return '<span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>'.$expiredCount.' Kadaluarsa</span>';
                        } elseif ($expiringSoonCount > 0) {
                            return '<span class="badge badge-warning text-dark px-2 py-1 font-tabular"><i class="fas fa-clock mr-1"></i>'.$activeHolders->count().' Anggota (H-Segera)</span>';
                        }

                        return '<span class="badge badge-success px-2 py-1"><i class="fas fa-users mr-1"></i>'.$activeHolders->count().' Anggota Aktif</span>';
                    } elseif ($activeHolders->count() === 1) {
                        $active = $activeHolders->first();
                        if ($active->expiration_status === 'expired') {
                            return '<span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>SK Kadaluarsa</span>';
                        } elseif ($active->expiration_status === 'expiring_soon') {
                            return '<span class="badge badge-warning text-dark px-2 py-1 font-tabular"><i class="fas fa-clock mr-1"></i>H-'.$active->days_remaining.' Berakhir</span>';
                        }

                        return '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Terisi & Aktif</span>';
                    }

                    $latest = $row->guruJabatans->first();
                    if ($latest) {
                        return '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-user-clock mr-1"></i>Demisioner</span>';
                    }

                    return '<span class="badge badge-light border text-muted px-2 py-1"><i class="fas fa-minus-circle mr-1 text-muted"></i>Formasi Kosong</span>';
                })
                ->addColumn('action', function ($row) use ($today) {
                    $activeHolders = $row->guruJabatans->filter(function ($item) use ($today) {
                        if (! $item->is_active) {
                            return false;
                        }
                        $startOk = is_null($item->start_date) || $item->start_date->toDateString() <= $today;
                        $endOk = is_null($item->end_date) || $item->end_date->toDateString() >= $today;

                        return $startOk && $endOk;
                    });
                    if ($activeHolders->isEmpty()) {
                        $activeHolders = $row->guruJabatans->where('is_active', true);
                    }

                    $latest = $activeHolders->first() ?: $row->guruJabatans->first();

                    $lastEndDate = $latest && $latest->end_date ? $latest->end_date : null;
                    $suggestedStart = $lastEndDate ? $lastEndDate->copy()->addDay()->format('Y-m-d') : Carbon::today()->format('Y-m-d');
                    $suggestedStartFormatted = $lastEndDate ? $lastEndDate->copy()->addDay()->format('d/m/Y') : Carbon::today()->format('d/m/Y');

                    $activeList = $activeHolders->map(function ($h) {
                        return [
                            'id' => $h->guru_id,
                            'name' => $h->guru ? $h->guru->full_name_with_title : '-',
                            'nip' => $h->guru ? $h->guru->nip : null,
                        ];
                    })->values();

                    $lastOfficialInfo = [
                        'has_previous' => (bool) $latest,
                        'is_team' => (bool) $row->is_team,
                        'active_count' => $activeHolders->count(),
                        'active_members' => $activeList,
                        'guru_id' => $latest ? $latest->guru_id : null,
                        'guru_name' => $latest && $latest->guru ? $latest->guru->full_name_with_title : null,
                        'last_end_date' => $lastEndDate ? $lastEndDate->format('Y-m-d') : null,
                        'last_end_date_formatted' => $lastEndDate ? $lastEndDate->format('d/m/Y') : null,
                        'suggested_start_date' => $suggestedStart,
                        'suggested_start_date_formatted' => $suggestedStartFormatted,
                        'period_type' => $latest ? ($latest->period_type ?: 'tahun_anggaran') : 'tahun_anggaran',
                        'is_active' => $latest ? (bool) $latest->is_active : false,
                    ];

                    $btnText = $row->is_team
                        ? '<i class="fas fa-users-cog mr-1"></i>Atur / Tambah Tim'
                        : '<i class="fas fa-user-edit mr-1"></i>Tetapkan / Ganti';

                    $assignBtn = '<button type="button" class="btn btn-xs btn-primary btn-assign-jabatan mr-1" data-id="'.$row->id.'" data-name="'.e($row->name).'" data-code="'.e($row->code).'" data-signatory="'.($row->is_signatory ? '1' : '0').'" data-is-team="'.($row->is_team ? '1' : '0').'" data-last="'.e(json_encode($lastOfficialInfo)).'" title="Tetapkan / Perpanjang / Tambah Anggota">'.$btnText.'</button>';

                    $count = $row->guruJabatans->count();
                    $historyBtn = '<button type="button" class="btn btn-xs btn-outline-secondary btn-toggle-history" data-id="'.$row->id.'" title="Lihat Riwayat Pejabat"><i class="fas fa-history mr-1"></i>Riwayat ('.$count.')</button>';

                    return '<div class="text-nowrap text-center">'.$assignBtn.$historyBtn.'</div>';
                })
                ->addColumn('history', function ($row) {
                    return $row->guruJabatans->map(function ($h) {
                        return [
                            'id' => $h->id,
                            'guru_id' => $h->guru_id,
                            'guru_name' => $h->guru ? $h->guru->full_name_with_title : '-',
                            'guru_nip' => $h->guru ? $h->guru->nip : null,
                            'sk_number' => $h->sk_number ?: '-',
                            'sk_date' => $h->sk_date ? $h->sk_date->format('d/m/Y') : null,
                            'sk_date_raw' => $h->sk_date ? $h->sk_date->format('Y-m-d') : null,
                            'sk_file_url' => $h->sk_file ? route('guru-jabatan.sk-file', $h->id) : null,
                            'period_year' => $h->period_year,
                            'period_type' => $h->period_type,
                            'start_date' => $h->start_date ? $h->start_date->format('d/m/Y') : '-',
                            'start_date_raw' => $h->start_date ? $h->start_date->format('Y-m-d') : null,
                            'end_date' => $h->end_date ? $h->end_date->format('d/m/Y') : 'Sekarang',
                            'end_date_raw' => $h->end_date ? $h->end_date->format('Y-m-d') : null,
                            'is_active' => (bool) $h->is_active,
                            'notes' => $h->notes,
                            'update_url' => route('guru-jabatan.update', $h->id),
                            'delete_url' => route('guru-jabatan.destroy', $h->id),
                        ];
                    })->values();
                })
                ->rawColumns(['jabatan_display', 'pejabat_display', 'sk_display', 'masa_tugas_display', 'status_display', 'action'])
                ->make(true);
        }

        $gurus = Guru::where('is_active', true)->orderBy('name', 'asc')->get();
        $jabatans = Jabatan::orderBy('name', 'asc')->get();
        $years = GuruJabatan::all()->map(function ($row) {
            return $row->start_date ? $row->start_date->format('Y') : $row->period_year;
        })->filter()->unique()->sortDesc()->values();
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        // Monitoring SK Kadaluarsa & Mendekati Kadaluarsa
        $totalActive = GuruJabatan::activeCurrently()->count();
        $expiredList = GuruJabatan::with(['guru', 'jabatan'])->expiredActive()->get();
        $expiringSoonList = GuruJabatan::with(['guru', 'jabatan'])->expiringSoon(30)->get();

        // Pengecekan Pejabat Penandatangan SPJ (is_signatory = true)
        $signatoryJabatans = Jabatan::where('is_signatory', true)->get();
        $today = Carbon::today()->toDateString();
        $missingSignatories = collect();
        $expiredSignatories = collect();

        foreach ($signatoryJabatans as $jabatan) {
            $activePenugasan = GuruJabatan::with('guru')
                ->where('jabatan_id', $jabatan->id)
                ->where('is_active', true)
                ->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                })
                ->first();

            if (! $activePenugasan) {
                $expiredPenugasan = GuruJabatan::with('guru')
                    ->where('jabatan_id', $jabatan->id)
                    ->where('is_active', true)
                    ->whereNotNull('end_date')
                    ->whereDate('end_date', '<', $today)
                    ->orderByDesc('end_date')
                    ->first();

                if ($expiredPenugasan) {
                    $expiredSignatories->push([
                        'jabatan' => $jabatan,
                        'penugasan' => $expiredPenugasan,
                    ]);
                } else {
                    $missingSignatories->push($jabatan);
                }
            }
        }

        $signatoryCount = Jabatan::where('is_signatory', true)->count();
        $allCount = Jabatan::count();

        return view('master.jabatan.penugasan', compact(
            'gurus',
            'jabatans',
            'years',
            'totalActive',
            'expiredList',
            'expiringSoonList',
            'missingSignatories',
            'expiredSignatories',
            'signatoryCount',
            'allCount'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guru_id' => 'nullable|exists:gurus,id',
            'guru_ids' => 'nullable|array',
            'guru_ids.*' => 'exists:gurus,id',
            'jabatan_id' => 'required|exists:jabatans,id',
            'sk_number' => 'nullable|string|max:100',
            'sk_date' => 'nullable|date',
            'sk_file' => 'nullable|file|mimes:pdf|max:5120',
            'period_year' => 'nullable|string|max:20',
            'period_type' => 'nullable|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable',
            'notes' => 'nullable|string',
            'replace_active' => 'nullable',
            'action_mode' => 'nullable|string',
        ]);

        $guruIds = collect($request->input('guru_ids', []))
            ->filter()
            ->unique();
        if ($request->filled('guru_id')) {
            $guruIds->push($request->input('guru_id'));
        }
        $guruIds = $guruIds->unique()->values();

        if ($guruIds->isEmpty()) {
            return response()->json([
                'message' => 'Pilih setidaknya satu guru / pejabat.',
                'errors' => ['guru_id' => ['Pilih setidaknya satu guru / pejabat.']],
            ], 422);
        }

        // Otomatis tentukan keaktifan berdasarkan tanggal jika tidak dikirim secara manual
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        } else {
            $today = Carbon::today();
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = ! empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->endOfDay() : null;

            if ($endDate) {
                $validated['is_active'] = $today->betweenIncluded($startDate, $endDate);
            } else {
                $validated['is_active'] = $today->gte($startDate);
            }
        }

        $validated['period_type'] = $validated['period_type'] ?? 'tahun_anggaran';

        // Auto-generate period_year jika tidak diisi
        if (empty($validated['period_year'])) {
            $startYear = Carbon::parse($validated['start_date'])->year;
            if ($validated['period_type'] === 'tahun_pelajaran') {
                $validated['period_year'] = $startYear.'/'.($startYear + 1);
            } elseif ($validated['period_type'] === 'multi_tahun') {
                $endYear = ! empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->year : ($startYear + 4);
                $validated['period_year'] = $startYear.' - '.$endYear;
            } else {
                $validated['period_year'] = (string) $startYear;
            }
        }

        $jabatan = Jabatan::findOrFail($validated['jabatan_id']);
        $actionMode = $request->input('action_mode');

        // Tentukan apakah harus mendemisionerkan pejabat sebelumnya
        $shouldReplace = false;
        if ($actionMode === 'replace') {
            $shouldReplace = true;
        } elseif ($actionMode === 'add_team' || $actionMode === 'append') {
            $shouldReplace = false;
        } elseif ($request->has('replace_active')) {
            $shouldReplace = $request->boolean('replace_active');
        } elseif (! $jabatan->is_team) {
            $shouldReplace = true;
        }

        // Serah Terima / Cut-off Otomatis jika menggantikan pejabat sebelumnya
        if ($validated['is_active'] && $shouldReplace) {
            $prevActiveList = GuruJabatan::where('jabatan_id', $validated['jabatan_id'])
                ->where('is_active', true)
                ->get();

            $prevEndDate = Carbon::parse($validated['start_date'])->subDay()->toDateString();
            $newGuruNames = Guru::whereIn('id', $guruIds)->pluck('name')->join(', ');

            foreach ($prevActiveList as $prev) {
                $updateData = ['is_active' => false];
                if (is_null($prev->end_date) || $prev->end_date->toDateString() >= $validated['start_date']) {
                    $updateData['end_date'] = $prevEndDate;
                }
                if (! $guruIds->contains($prev->guru_id)) {
                    $prevNotes = $prev->notes ? $prev->notes.' | ' : '';
                    $updateData['notes'] = $prevNotes.'Digantikan oleh '.($newGuruNames ?: 'pejabat baru').' per '.Carbon::parse($validated['start_date'])->format('d/m/Y');
                }
                $prev->update($updateData);
            }
        }

        // Upload Berkas SK PDF
        $skFilePath = null;
        if ($request->hasFile('sk_file')) {
            $skFilePath = $request->file('sk_file')->store('sk_jabatan', 'public');
        }

        unset($validated['replace_active'], $validated['action_mode'], $validated['guru_ids'], $validated['guru_id']);
        if ($skFilePath) {
            $validated['sk_file'] = $skFilePath;
        }

        $created = [];
        foreach ($guruIds as $gId) {
            $itemData = $validated;
            $itemData['guru_id'] = $gId;
            $penugasan = GuruJabatan::create($itemData);
            $penugasan->load(['guru', 'jabatan']);
            $created[] = $penugasan;
        }

        // Sinkronisasi otomatis role akun pengguna yang tertaut dengan profil guru
        $affectedGuruIds = $guruIds->merge(isset($prevActiveList) ? $prevActiveList->pluck('guru_id') : collect())->unique();
        foreach ($affectedGuruIds as $affectedId) {
            $affectedGuru = Guru::with(['activeJabatans.jabatan', 'user'])->find($affectedId);
            if ($affectedGuru && $affectedGuru->user) {
                $affectedGuru->user->syncRoleFromActiveJabatan();
            }
        }

        if (count($created) === 1) {
            $first = $created[0];
            $msg = "Penugasan '{$first->guru->full_name_with_title}' sebagai '{$first->jabatan->name}' berhasil disimpan.";
        } else {
            $first = $created[0];
            $count = count($created);
            $names = collect($created)->map(fn ($p) => $p->guru ? $p->guru->name : '')->filter()->join(', ');
            $msg = "Penugasan {$count} anggota ({$names}) pada '{$first->jabatan->name}' berhasil disimpan.";
        }

        return response()->json([
            'message' => $msg,
            'data' => $created,
        ]);
    }

    public function update(Request $request, GuruJabatan $guruJabatan)
    {
        $validated = $request->validate([
            'guru_id' => 'required|exists:gurus,id',
            'jabatan_id' => 'required|exists:jabatans,id',
            'sk_number' => 'nullable|string|max:100',
            'sk_date' => 'nullable|date',
            'sk_file' => 'nullable|file|mimes:pdf|max:5120',
            'period_year' => 'nullable|string|max:20',
            'period_type' => 'nullable|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        } else {
            $today = Carbon::today();
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = ! empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->endOfDay() : null;

            if ($endDate) {
                $validated['is_active'] = $today->betweenIncluded($startDate, $endDate);
            } else {
                $validated['is_active'] = $today->gte($startDate);
            }
        }
        if (isset($validated['period_type'])) {
            $validated['period_type'] = $validated['period_type'] ?: 'tahun_anggaran';
        }

        // Auto-generate period_year jika tidak diisi
        if (empty($validated['period_year'])) {
            $startYear = Carbon::parse($validated['start_date'])->year;
            $pType = $validated['period_type'] ?? 'tahun_anggaran';
            if ($pType === 'tahun_pelajaran') {
                $validated['period_year'] = $startYear.'/'.($startYear + 1);
            } elseif ($pType === 'multi_tahun') {
                $endYear = ! empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->year : ($startYear + 4);
                $validated['period_year'] = $startYear.' - '.$endYear;
            } else {
                $validated['period_year'] = (string) $startYear;
            }
        }

        // Upload Berkas SK PDF Pengganti
        if ($request->hasFile('sk_file')) {
            if ($guruJabatan->sk_file && Storage::disk('public')->exists($guruJabatan->sk_file)) {
                Storage::disk('public')->delete($guruJabatan->sk_file);
            }
            $validated['sk_file'] = $request->file('sk_file')->store('sk_jabatan', 'public');
        }

        $guruJabatan->update($validated);
        $guruJabatan->load(['guru.user', 'jabatan']);
        $guruJabatan->guru?->user?->syncRoleFromActiveJabatan();

        return response()->json([
            'message' => "Penugasan '{$guruJabatan->guru->full_name_with_title}' sebagai '{$guruJabatan->jabatan->name}' berhasil diperbarui.",
        ]);
    }

    public function serveSkFile(GuruJabatan $guruJabatan)
    {
        if (! $guruJabatan->sk_file || ! Storage::disk('public')->exists($guruJabatan->sk_file)) {
            abort(404, 'Berkas SK tidak ditemukan.');
        }

        $filename = 'SK_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $guruJabatan->sk_number ?: 'Jabatan_'.$guruJabatan->id).'.pdf';

        return response()->file(Storage::disk('public')->path($guruJabatan->sk_file), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function toggleStatus(GuruJabatan $guruJabatan)
    {
        $guruJabatan->update([
            'is_active' => ! $guruJabatan->is_active,
        ]);

        $guruJabatan->load(['guru.user']);
        $guruJabatan->guru?->user?->syncRoleFromActiveJabatan();

        $statusText = $guruJabatan->is_active ? 'Aktif Menjabat' : 'Demisioner';

        return response()->json([
            'message' => "Status penugasan diubah menjadi {$statusText}.",
            'is_active' => $guruJabatan->is_active,
        ]);
    }

    public function destroy(GuruJabatan $guruJabatan)
    {
        if ($guruJabatan->sk_file && Storage::disk('public')->exists($guruJabatan->sk_file)) {
            Storage::disk('public')->delete($guruJabatan->sk_file);
        }

        $guru = $guruJabatan->guru;
        $guruJabatan->delete();

        if ($guru) {
            $guru->load(['activeJabatans.jabatan', 'user']);
            $guru->user?->syncRoleFromActiveJabatan();
        }

        return response()->json([
            'message' => 'Data penugasan jabatan berhasil dihapus.',
        ]);
    }
}

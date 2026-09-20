<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Guru::with(['primaryBankAccount', 'activeJabatans.jabatan'])
                ->when($request->status !== null && $request->status !== '', function ($q) use ($request) {
                    $q->where('is_active', $request->status === '1');
                })
                ->when($request->employment_status, function ($q) use ($request) {
                    $q->where('employment_status', $request->employment_status);
                })
                ->orderBy('name', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_display', function ($row) {
                    $nameWithTitle = e($row->full_name_with_title);
                    $nipText = $row->nip ? '<small class="text-muted d-block font-tabular">NIP. '.e($row->nip).'</small>' : '';

                    return '<a href="'.route('guru.show', $row->id).'" class="font-weight-bold text-dark text-decoration-none">'.$nameWithTitle.'</a>'.$nipText;
                })
                ->addColumn('identity_display', function ($row) {
                    $genderBadge = $row->gender === 'L'
                        ? '<span class="badge badge-info mr-1" title="Laki-laki">L</span>'
                        : '<span class="badge badge-pink mr-1" title="Perempuan">P</span>';
                    $religion = e($row->religion ?? '-');

                    return $genderBadge.' <small class="text-muted">'.$religion.'</small>';
                })
                ->addColumn('employment_display', function ($row) {
                    $statusColor = match ($row->employment_status) {
                        'PNS' => 'success',
                        'PPPK' => 'primary',
                        'GTT' => 'warning',
                        'GTY' => 'info',
                        'PTY' => 'secondary',
                        'PTT' => 'light text-dark border',
                        default => 'secondary',
                    };
                    $statusBadge = '<span class="badge badge-'.$statusColor.'">'.e($row->employment_status).'</span>';
                    $pangkat = $row->pangkat_golongan ? '<small class="text-muted d-block font-tabular">'.e($row->pangkat_golongan).'</small>' : '';

                    return $statusBadge.$pangkat;
                })
                ->addColumn('active_jabatans_display', function ($row) {
                    if ($row->activeJabatans->isEmpty()) {
                        return '<span class="text-muted">-</span>';
                    }

                    $badges = $row->activeJabatans->map(function ($gj) {
                        $name = e($gj->jabatan->name ?? '-');
                        $class = ($gj->jabatan->is_signatory ?? false) ? 'badge-primary font-weight-bold' : 'badge-light border';

                        return '<span class="badge '.$class.' mr-1 mb-1">'.$name.'</span>';
                    })->implode(' ');

                    return $badges;
                })
                ->addColumn('bank_account_display', function ($row) {
                    $bank = $row->primaryBankAccount;
                    if (! $bank) {
                        return '<span class="text-muted small">Belum diatur</span>';
                    }

                    return '<div class="small font-tabular"><strong>'.e($bank->bank_name).'</strong><br><span class="text-dark">'.e($bank->account_number).'</span><br><small class="text-muted">a.n '.e($bank->account_holder).'</small></div>';
                })
                ->addColumn('contact_display', function ($row) {
                    $phone = $row->phone ? '<div><i class="fas fa-phone-alt text-success mr-1"></i><span class="font-tabular">'.e($row->phone).'</span></div>' : '';
                    $email = $row->email ? '<div><i class="fas fa-envelope text-muted mr-1"></i><small>'.e($row->email).'</small></div>' : '';

                    return $phone || $email ? $phone.$email : '<span class="text-muted">-</span>';
                })
                ->addColumn('status_display', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success">Aktif</span>'
                        : '<span class="badge badge-secondary">Non-Aktif</span>';
                })
                ->addColumn('action', function ($row) {
                    $showBtn = '<a href="'.route('guru.show', $row->id).'" class="btn btn-xs btn-info mr-1" title="Detail Profil"><i class="fas fa-eye"></i></a>';
                    $editBtn = '<a href="'.route('guru.edit', $row->id).'" class="btn btn-xs btn-warning mr-1" title="Edit Data"><i class="fas fa-edit"></i></a>';
                    $delBtn = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-url="'.route('guru.destroy', $row->id).'" data-name="'.e($row->full_name_with_title).'" title="Hapus Data"><i class="fas fa-trash"></i></button>';

                    return '<div class="text-nowrap text-center">'.$showBtn.$editBtn.$delBtn.'</div>';
                })
                ->rawColumns(['name_display', 'identity_display', 'employment_display', 'active_jabatans_display', 'bank_account_display', 'contact_display', 'status_display', 'action'])
                ->make(true);
        }

        return view('master.guru.index');
    }

    public function create()
    {
        return view('master.guru.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:20',
            'nuptk' => 'nullable|string|max:30',
            'npwp' => 'nullable|string|max:30',
            'name' => 'required|string|max:150',
            'title_prefix' => 'nullable|string|max:50',
            'title_suffix' => 'nullable|string|max:50',
            'gender' => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:30',
            'employment_status' => 'required|in:PNS,PPPK,GTT,GTY,PTY,Honorer,PTT',
            'pangkat_golongan' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'is_active' => 'nullable|boolean',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:50',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_holder' => 'nullable|string|max:150',
            'bank_accounts.*.is_primary' => 'nullable|boolean',
            'bank_accounts.*.notes' => 'nullable|string|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');

        DB::beginTransaction();
        try {
            $guru = Guru::create($validated);

            if (! empty($request->bank_accounts)) {
                $hasPrimary = false;
                $savedCount = 0;
                foreach ($request->bank_accounts as $index => $acc) {
                    $bankName = trim($acc['bank_name'] ?? '');
                    $accNumber = trim($acc['account_number'] ?? '');
                    $accHolder = trim($acc['account_holder'] ?? '');

                    // Abaikan baris jika nama bank, nomor rekening, dan pemilik kosong semua
                    if ($bankName === '' && $accNumber === '' && $accHolder === '') {
                        continue;
                    }

                    $isPrimary = ! empty($acc['is_primary']);
                    if ($isPrimary) {
                        $hasPrimary = true;
                    }

                    // Jika tidak ada yang ditandai primary, rekening pertama otomatis jadi primary
                    if ($savedCount === 0 && ! $hasPrimary) {
                        $isPrimary = true;
                    }

                    GuruBankAccount::create([
                        'guru_id' => $guru->id,
                        'bank_name' => $bankName ?: 'Bank Jatim',
                        'account_number' => $accNumber ?: null,
                        'account_holder' => $accHolder ?: ($guru->name ?? '-'),
                        'is_primary' => $isPrimary,
                        'notes' => $acc['notes'] ?? null,
                    ]);
                    $savedCount++;
                }
            }

            DB::commit();

            return redirect()->route('guru.index')
                ->with('success', "Data Guru '{$guru->full_name_with_title}' berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data guru: '.$e->getMessage());
        }
    }

    public function show(Guru $guru)
    {
        $guru->load(['bankAccounts', 'jabatans.jabatan']);

        return view('master.guru.show', compact('guru'));
    }

    public function edit(Guru $guru)
    {
        $guru->load('bankAccounts');

        return view('master.guru.edit', compact('guru'));
    }

    public function update(Request $request, Guru $guru)
    {
        $validated = $request->validate([
            'nip' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:20',
            'nuptk' => 'nullable|string|max:30',
            'npwp' => 'nullable|string|max:30',
            'name' => 'required|string|max:150',
            'title_prefix' => 'nullable|string|max:50',
            'title_suffix' => 'nullable|string|max:50',
            'gender' => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:30',
            'employment_status' => 'required|in:PNS,PPPK,GTT,GTY,PTY,Honorer,PTT',
            'pangkat_golongan' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'is_active' => 'nullable|boolean',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:50',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_holder' => 'nullable|string|max:150',
            'bank_accounts.*.is_primary' => 'nullable|boolean',
            'bank_accounts.*.notes' => 'nullable|string|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');

        DB::beginTransaction();
        try {
            $guru->update($validated);

            // Sinkronisasi rekening bank: hapus rekening lama dan simpan yang baru
            GuruBankAccount::where('guru_id', $guru->id)->delete();

            if (! empty($request->bank_accounts)) {
                $primarySet = false;
                $savedCount = 0;
                foreach ($request->bank_accounts as $index => $acc) {
                    $bankName = trim($acc['bank_name'] ?? '');
                    $accNumber = trim($acc['account_number'] ?? '');
                    $accHolder = trim($acc['account_holder'] ?? '');

                    if ($bankName === '' && $accNumber === '' && $accHolder === '') {
                        continue;
                    }

                    $isPrimary = ! empty($acc['is_primary']);
                    if ($isPrimary) {
                        $primarySet = true;
                    }

                    if ($savedCount === 0 && ! $primarySet) {
                        $isPrimary = true;
                    }

                    GuruBankAccount::create([
                        'guru_id' => $guru->id,
                        'bank_name' => $bankName ?: 'Bank Jatim',
                        'account_number' => $accNumber ?: null,
                        'account_holder' => $accHolder ?: ($guru->name ?? '-'),
                        'is_primary' => $isPrimary,
                        'notes' => $acc['notes'] ?? null,
                    ]);
                    $savedCount++;
                }
            }

            DB::commit();

            return redirect()->route('guru.index')
                ->with('success', "Data Guru '{$guru->full_name_with_title}' berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data guru: '.$e->getMessage());
        }
    }

    public function destroy(Guru $guru)
    {
        $name = $guru->full_name_with_title;
        $guru->delete();

        return response()->json([
            'message' => "Data Guru '{$name}' berhasil dihapus.",
        ]);
    }
}

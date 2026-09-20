<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            $query = Supplier::with('bankAccounts')->orderBy('name', 'asc');

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status_pkp')) {
                $query->where('status_pkp', $request->status_pkp);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active === '1' || $request->is_active === 1);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_display', function ($row) {
                    $name = '<div class="font-weight-bold text-dark mb-1" style="font-size:0.88rem;">'.e($row->name).'</div>';
                    $contact = [];
                    if ($row->pic_name) {
                        $contact[] = '<i class="far fa-user mr-1 text-muted"></i>'.e($row->pic_name);
                    }
                    if ($row->phone) {
                        $contact[] = '<i class="fas fa-phone-alt mr-1 text-muted"></i>'.e($row->phone);
                    }
                    $sub = ! empty($contact) ? '<small class="text-muted d-block">'.implode(' &nbsp;|&nbsp; ', $contact).'</small>' : '';

                    return $name.$sub;
                })
                ->addColumn('type_display', function ($row) {
                    if ($row->type === 'online') {
                        $mp = $row->marketplace_name ? '<small class="text-muted d-block mt-1 font-weight-normal">'.e($row->marketplace_name).'</small>' : '';

                        return '<span class="badge badge-info"><i class="fas fa-globe mr-1"></i>Online</span>'.$mp;
                    }

                    return '<span class="badge badge-light border text-dark"><i class="fas fa-store mr-1 text-secondary"></i>Offline</span>';
                })
                ->addColumn('category_display', function ($row) {
                    return $row->category_badge;
                })
                ->addColumn('tax_display', function ($row) {
                    $html = '<div class="small mb-1">'.$row->status_pkp_badge.'</div>';
                    if ($row->npwp) {
                        $html .= '<div class="small font-tabular"><i class="fas fa-id-card mr-1 text-muted"></i>'.e($row->npwp).'</div>';
                    } elseif ($row->nik) {
                        $html .= '<div class="small text-muted font-tabular">NIK: '.e($row->nik).'</div>';
                    } else {
                        $html .= '<div class="small text-muted font-italic">- Tanpa NPWP -</div>';
                    }

                    return $html;
                })
                ->addColumn('bank_display', function ($row) {
                    if ($row->type === 'online') {
                        $bank = e($row->bank_name ?? 'Bank Jatim');
                        $acc = '<span class="badge badge-warning text-dark"><i class="fas fa-bolt mr-1"></i>VA otomatis per transaksi</span>';

                        return '<div class="small"><span class="badge badge-light border text-primary font-weight-bold mr-1">'.$bank.'</span>'.$acc.'</div>';
                    }

                    // Toko Offline
                    $accounts = $row->bankAccounts;
                    if ($accounts && $accounts->count() > 0) {
                        $html = '<div class="small">';
                        foreach ($accounts as $idx => $acc) {
                            $isPrim = $acc->is_primary ? '<span class="badge badge-success px-1 py-0 mr-1" style="font-size:0.65rem;">Utama</span>' : '';
                            $note = $acc->notes ? ' <small class="text-muted">('.e($acc->notes).')</small>' : '';
                            $holder = $acc->account_holder ? '<span class="text-dark font-weight-500 d-block" style="font-size:0.75rem;"><i class="far fa-user mr-1 text-muted"></i>a.n. '.e($acc->account_holder).'</span>' : '';
                            $html .= '<div class="'.($idx > 0 ? 'mt-1 pt-1 border-top' : '').'">'
                                .$isPrim
                                .'<span class="badge badge-light border font-weight-bold mr-1">'.e($acc->bank_name).'</span>'
                                .'<span class="font-tabular font-weight-bold">'.e($acc->account_number).'</span>'
                                .$note
                                .$holder
                                .'</div>';
                        }
                        $html .= '</div>';

                        return $html;
                    }

                    if (! $row->bank_name && ! $row->account_number) {
                        return '<span class="text-muted small font-italic">- Belum diset -</span>';
                    }

                    $bank = e($row->bank_name ?? 'Bank');
                    $acc = e($row->account_number ?? '-');
                    $holder = $row->account_holder ? '<span class="text-dark font-weight-500 d-block" style="font-size:0.75rem;"><i class="far fa-user mr-1 text-muted"></i>a.n. '.e($row->account_holder).'</span>' : '';

                    return '<div class="small"><span class="badge badge-light border text-primary font-weight-bold mr-1">'.$bank.'</span><span class="font-tabular font-weight-bold">'.$acc.'</span>'.$holder.'</div>';
                })
                ->addColumn('address_display', function ($row) {
                    if (! $row->address) {
                        return '<span class="text-muted small font-italic">-</span>';
                    }

                    return '<div class="small"><i class="fas fa-map-marker-alt mr-1 text-muted"></i>'.e($row->address).'</div>';
                })
                ->addColumn('status_display', function ($row) {
                    if ($row->is_active) {
                        return '<button type="button" class="btn btn-xs btn-outline-success btn-toggle-status" data-id="'.$row->id.'" title="Klik untuk nonaktifkan"><i class="fas fa-check-circle mr-1"></i>Aktif</button>';
                    }

                    return '<button type="button" class="btn btn-xs btn-outline-secondary btn-toggle-status" data-id="'.$row->id.'" title="Klik untuk aktifkan"><i class="fas fa-times-circle mr-1"></i>Nonaktif</button>';
                })
                ->addColumn('action', function ($row) {
                    $btnEdit = '<button type="button" class="btn btn-xs btn-warning btn-edit mr-1" data-id="'.$row->id.'" title="Edit Supplier"><i class="fas fa-edit"></i></button>';
                    $btnDelete = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-url="'.route('supplier.destroy', $row->id).'" data-name="'.e($row->name).'" title="Hapus"><i class="fas fa-trash"></i></button>';

                    return '<div class="text-nowrap text-center">'.$btnEdit.$btnDelete.'</div>';
                })
                ->rawColumns(['name_display', 'type_display', 'category_display', 'tax_display', 'bank_display', 'address_display', 'status_display', 'action'])
                ->make(true);
        }

        $counts = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', true)->count(),
            'pkp' => Supplier::where('status_pkp', 'pkp')->count(),
            'offline' => Supplier::where('type', 'offline')->count(),
            'online' => Supplier::where('type', 'online')->count(),
            'atk' => Supplier::where('category', 'atk_fotokopi')->count(),
            'sarpras' => Supplier::where('category', 'sarpras_bangunan')->count(),
        ];

        $categories = Supplier::categories();

        return view('master.supplier.index', compact('counts', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:offline,online',
            'marketplace_name' => 'nullable|string|max:100',
            'pic_name' => 'nullable|string|max:150',
            'category' => 'required|string|max:50',
            'npwp' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:20',
            'status_pkp' => 'required|string|in:non_pkp,pkp',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:150',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:100',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_holder' => 'nullable|string|max:150',
            'bank_accounts.*.is_primary' => 'nullable',
            'bank_accounts.*.notes' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $type = $validated['type'] ?? 'offline';
        $validated['type'] = $type;
        $validated['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;

        if ($type === 'online') {
            $validated['marketplace_name'] = $validated['marketplace_name'] ?: 'Siplah Toko Ladang';
            $validated['bank_name'] = $validated['bank_name'] ?: 'Bank Jatim';
            $validated['account_number'] = null; // VA otomatis generate per transaksi / invoice di rencana nota
            $validated['account_holder'] = 'PT. LADANG KARYA HUSADA';
        } else {
            $validated['account_holder'] = $validated['account_holder'] ?? null;
        }

        $supplier = Supplier::create($validated);

        // Multi bank accounts untuk toko offline
        if ($type === 'offline') {
            $this->syncBankAccounts($supplier, $request->bank_accounts ?? []);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier '{$supplier->name}' berhasil ditambahkan.",
                'data' => $supplier->load('bankAccounts'),
            ]);
        }

        return redirect()->route('supplier.index')->with('success', "Supplier '{$supplier->name}' berhasil ditambahkan.");
    }

    public function show($id)
    {
        $supplier = Supplier::with('bankAccounts')->findOrFail($id);

        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:offline,online',
            'marketplace_name' => 'nullable|string|max:100',
            'pic_name' => 'nullable|string|max:150',
            'category' => 'required|string|max:50',
            'npwp' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:20',
            'status_pkp' => 'required|string|in:non_pkp,pkp',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:150',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:100',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_holder' => 'nullable|string|max:150',
            'bank_accounts.*.is_primary' => 'nullable',
            'bank_accounts.*.notes' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $type = $validated['type'] ?? ($supplier->type ?? 'offline');
        $validated['type'] = $type;
        $validated['is_active'] = $request->has('is_active') ? (bool) $request->is_active : false;

        if ($type === 'online') {
            $validated['marketplace_name'] = $validated['marketplace_name'] ?: 'Siplah Toko Ladang';
            $validated['bank_name'] = $validated['bank_name'] ?: 'Bank Jatim';
            $validated['account_number'] = null; // VA otomatis generate per transaksi / invoice di rencana nota
            $validated['account_holder'] = 'PT. LADANG KARYA HUSADA';
        } else {
            $validated['account_holder'] = $validated['account_holder'] ?? null;
        }

        $supplier->update($validated);

        // Multi bank accounts untuk toko offline
        if ($type === 'offline') {
            $this->syncBankAccounts($supplier, $request->bank_accounts ?? []);
        } else {
            $supplier->bankAccounts()->delete();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier '{$supplier->name}' berhasil diperbarui.",
                'data' => $supplier->fresh('bankAccounts'),
            ]);
        }

        return redirect()->route('supplier.index')->with('success', "Supplier '{$supplier->name}' berhasil diperbarui.");
    }

    private function syncBankAccounts(Supplier $supplier, array $bankAccountsData): void
    {
        $validAccounts = [];
        $primaryIndex = null;

        foreach ($bankAccountsData as $idx => $acc) {
            $bankName = trim($acc['bank_name'] ?? '');
            $accNumber = trim($acc['account_number'] ?? '');
            if ($bankName !== '' || $accNumber !== '') {
                $isPrimary = ! empty($acc['is_primary']) && ($acc['is_primary'] == '1' || $acc['is_primary'] === true);
                if ($isPrimary && $primaryIndex === null) {
                    $primaryIndex = count($validAccounts);
                }
                $validAccounts[] = [
                    'bank_name' => $bankName ?: 'Bank Jatim',
                    'account_number' => $accNumber,
                    'account_holder' => trim($acc['account_holder'] ?? '') ?: null,
                    'is_primary' => false,
                    'notes' => $acc['notes'] ?? null,
                ];
            }
        }

        // Jika belum ada yang primary tapi ada akun valid, jadikan yang pertama primary
        if (count($validAccounts) > 0) {
            if ($primaryIndex === null) {
                $primaryIndex = 0;
            }
            $validAccounts[$primaryIndex]['is_primary'] = true;
        }

        $supplier->bankAccounts()->delete();
        foreach ($validAccounts as $va) {
            $supplier->bankAccounts()->create($va);
        }

        // Sinkronkan ke tabel suppliers kolom utama
        if (count($validAccounts) > 0) {
            $prim = $validAccounts[$primaryIndex];
            $supplier->update([
                'bank_name' => $prim['bank_name'],
                'account_number' => $prim['account_number'],
                'account_holder' => $prim['account_holder'],
            ]);
        }
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier '{$name}' berhasil dihapus.",
            ]);
        }

        return redirect()->route('supplier.index')->with('success', "Supplier '{$name}' berhasil dihapus.");
    }

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $supplier->is_active,
            'message' => "Status supplier '{$supplier->name}' berhasil diubah.",
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        $query = Supplier::with('bankAccounts')->where('is_active', true);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('pic_name', 'like', "%{$q}%")
                    ->orWhere('bank_name', 'like', "%{$q}%")
                    ->orWhere('account_number', 'like', "%{$q}%");
            });
        }

        $suppliers = $query->orderBy('name', 'asc')->limit(25)->get();

        return response()->json($suppliers->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'type' => $s->type,
                'marketplace_name' => $s->marketplace_name,
                'pic_name' => $s->pic_name,
                'category' => $s->category,
                'category_label' => $s->category_label,
                'bank_name' => $s->bank_name,
                'account_number' => $s->account_number,
                'account_holder' => $s->account_holder,
                'bank_accounts' => $s->bankAccounts,
                'npwp' => $s->npwp,
                'status_pkp' => $s->status_pkp,
                'phone' => $s->phone,
                'address' => $s->address,
                'city' => $s->city,
            ];
        }));
    }

    /**
     * Store a new supplier category (for dynamic category creation).
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:150',
        ]);

        $label = trim($request->label);
        // Generate a slug-like key from the label
        $key = Str::slug($label, '_');

        // Ensure key is unique
        $existing = SupplierCategory::where('key', $key)->first();
        if ($existing) {
            return response()->json([
                'key' => $existing->key,
                'label' => $existing->label,
            ]);
        }

        $maxOrder = SupplierCategory::where('key', '!=', 'lainnya')->max('sort_order') ?? 0;

        $category = SupplierCategory::create([
            'key' => $key,
            'label' => $label,
            'is_default' => false,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'key' => $category->key,
            'label' => $category->label,
        ], 201);
    }
}

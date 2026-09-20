<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\UtilityProfile;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UtilityProfileController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = UtilityProfile::with(['defaultReimburseGuru.primaryBankAccount'])
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc');

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('is_budgeted')) {
                $query->where('is_budgeted', $request->is_budgeted == '1');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_display', function ($row) {
                    $code = '<span class="badge badge-light border text-muted mr-1">'.e($row->code).'</span>';
                    $name = '<span class="font-weight-bold text-dark">'.e($row->name).'</span>';
                    $provider = $row->provider ? '<small class="text-muted d-block mt-0.5"><i class="fas fa-building mr-1"></i>'.e($row->provider).'</small>' : '';

                    return $code.$name.$provider;
                })
                ->addColumn('category_display', function ($row) {
                    return $row->category_badge;
                })
                ->addColumn('identity_display', function ($row) {
                    $custName = '<div class="font-weight-600 text-dark">'.e($row->customer_name).'</div>';
                    $details = [];
                    if ($row->customer_number) {
                        $details[] = 'IDPEL: <span class="font-tabular font-weight-bold">'.e($row->customer_number).'</span>';
                    }
                    if ($row->meter_number) {
                        $details[] = 'No. Meter: <span class="font-tabular font-weight-bold">'.e($row->meter_number).'</span>';
                    }
                    if ($row->tariff_power) {
                        $details[] = 'Daya: <span class="badge badge-light border">'.e($row->tariff_power).'</span>';
                    }
                    $sub = ! empty($details) ? '<small class="text-muted d-block mt-0.5">'.implode(' &nbsp;|&nbsp; ', $details).'</small>' : '';

                    return $custName.$sub;
                })
                ->addColumn('budget_display', function ($row) {
                    if ($row->is_budgeted) {
                        return '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Dianggarkan (BOS)</span>';
                    }

                    return '<span class="badge badge-secondary px-2 py-1" title="Hanya dicatat di struk, tidak masuk RKAS/Kertas Kerja BOS"><i class="fas fa-ban mr-1"></i>Non-BOS (Internal)</span>';
                })
                ->addColumn('reimburse_display', function ($row) {
                    if ($row->defaultReimburseGuru) {
                        $guruName = e($row->defaultReimburseGuru->name);
                        $acc = $row->defaultReimburseGuru->primaryBankAccount;
                        $accStr = $acc ? '<small class="text-muted font-tabular d-block">'.$acc->bank_name.' - '.$acc->account_number.'</small>' : '';

                        return '<div class="small font-weight-500 text-dark"><i class="fas fa-user-check text-success mr-1"></i>'.$guruName.'</div>'.$accStr;
                    }

                    return '<span class="text-muted small font-italic">- Bendahara BOSP -</span>';
                })
                ->addColumn('status_display', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success">Aktif</span>'
                        : '<span class="badge badge-danger">Nonaktif</span>';
                })
                ->addColumn('action', function ($row) {
                    $jsonData = htmlspecialchars(json_encode([
                        'id' => $row->id,
                        'code' => $row->code,
                        'name' => $row->name,
                        'category' => $row->category,
                        'customer_name' => $row->customer_name,
                        'customer_number' => $row->customer_number,
                        'meter_number' => $row->meter_number,
                        'idpel' => $row->idpel,
                        'tariff_power' => $row->tariff_power,
                        'provider' => $row->provider,
                        'payment_method' => $row->payment_method,
                        'description' => $row->description,
                        'sort_order' => $row->sort_order,
                        'is_active' => $row->is_active ? 1 : 0,
                        'is_budgeted' => $row->is_budgeted ? 1 : 0,
                        'default_reimburse_guru_id' => $row->default_reimburse_guru_id,
                    ]), ENT_QUOTES, 'UTF-8');

                    $btnEdit = '<button type="button" class="btn btn-xs btn-warning btn-edit mr-1" data-json="'.$jsonData.'" title="Edit Objek"><i class="fas fa-edit"></i></button>';
                    $btnDelete = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-url="'.route('master.utilitas.destroy', $row->id).'" data-name="'.e($row->name).'" title="Hapus"><i class="fas fa-trash"></i></button>';

                    return '<div class="text-nowrap text-center">'.$btnEdit.$btnDelete.'</div>';
                })
                ->rawColumns(['name_display', 'category_display', 'identity_display', 'budget_display', 'reimburse_display', 'status_display', 'action'])
                ->make(true);
        }

        $gurus = Guru::where('is_active', true)->with('primaryBankAccount')->orderBy('name')->get();
        $counts = [
            'total' => UtilityProfile::count(),
            'budgeted' => UtilityProfile::where('is_budgeted', true)->count(),
            'non_budgeted' => UtilityProfile::where('is_budgeted', false)->count(),
            'active' => UtilityProfile::where('is_active', true)->count(),
        ];

        return view('master.utility_profiles.index', compact('gurus', 'counts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:utility_profiles,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:listrik_pasca,listrik_pra,telkom_speedy,internet,lainnya',
            'customer_name' => 'required|string|max:255',
            'customer_number' => 'nullable|string|max:100',
            'meter_number' => 'nullable|string|max:100',
            'idpel' => 'nullable|string|max:100',
            'tariff_power' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_budgeted' => 'required|boolean',
            'is_active' => 'required|boolean',
            'default_reimburse_guru_id' => 'nullable|exists:gurus,id',
        ]);

        $profile = UtilityProfile::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Data pokok utilitas '{$profile->name}' berhasil ditambahkan.",
                'data' => $profile,
            ]);
        }

        return redirect()->route('master.utilitas.index')->with('success', "Data pokok utilitas '{$profile->name}' berhasil ditambahkan.");
    }

    public function show(UtilityProfile $utilita)
    {
        return response()->json($utilita);
    }

    public function update(Request $request, UtilityProfile $utilita)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:utility_profiles,code,'.$utilita->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:listrik_pasca,listrik_pra,telkom_speedy,internet,lainnya',
            'customer_name' => 'required|string|max:255',
            'customer_number' => 'nullable|string|max:100',
            'meter_number' => 'nullable|string|max:100',
            'idpel' => 'nullable|string|max:100',
            'tariff_power' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_budgeted' => 'required|boolean',
            'is_active' => 'required|boolean',
            'default_reimburse_guru_id' => 'nullable|exists:gurus,id',
        ]);

        $utilita->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Data pokok utilitas '{$utilita->name}' berhasil diperbarui.",
                'data' => $utilita,
            ]);
        }

        return redirect()->route('master.utilitas.index')->with('success', "Data pokok utilitas '{$utilita->name}' berhasil diperbarui.");
    }

    public function destroy(UtilityProfile $utilita)
    {
        $name = $utilita->name;
        $receiptCount = $utilita->receipts()->count();

        if ($receiptCount > 0) {
            $utilita->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => "Objek utilitas '{$name}' memiliki {$receiptCount} riwayat struk, sehingga statusnya diubah menjadi Nonaktif.",
            ]);
        }

        $utilita->delete();

        return response()->json([
            'success' => true,
            'message' => "Data pokok utilitas '{$name}' berhasil dihapus.",
        ]);
    }
}

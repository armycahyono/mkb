<?php

namespace App\Http\Controllers;

use App\Exports\InstansiTemplateExport;
use App\Imports\InstansiImport;
use App\Models\Instansi;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class InstansiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Instansi::query()->orderBy('name', 'asc');

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_display', function ($row) {
                    $name = '<div class="font-weight-bold text-dark" style="font-size:0.85rem;">'.e($row->name).'</div>';
                    $contact = [];
                    if ($row->pic_name) {
                        $contact[] = '<i class="far fa-user mr-1 text-muted"></i>'.e($row->pic_name);
                    }
                    if ($row->phone) {
                        $contact[] = '<i class="fas fa-phone-alt mr-1 text-muted"></i>'.e($row->phone);
                    }
                    $sub = ! empty($contact) ? '<small class="text-muted d-block mt-1">'.implode(' &nbsp;|&nbsp; ', $contact).'</small>' : '';

                    return $name.$sub;
                })
                ->addColumn('category_display', function ($row) {
                    return $row->category_badge;
                })
                ->addColumn('address_display', function ($row) {
                    $addr = e($row->address ?? '-');
                    $city = $row->city ? '<span class="badge badge-light border ml-1">'.e($row->city).'</span>' : '';

                    return '<div class="small">'.$addr.$city.'</div>';
                })
                ->addColumn('action', function ($row) {
                    $btnEdit = '<button type="button" class="btn btn-xs btn-warning btn-edit mr-1" data-id="'.$row->id.'" data-name="'.e($row->name).'" data-category="'.e($row->category).'" data-address="'.e($row->address).'" data-city="'.e($row->city).'" data-phone="'.e($row->phone).'" data-pic="'.e($row->pic_name).'" data-notes="'.e($row->notes).'" title="Edit"><i class="fas fa-edit"></i></button>';
                    $btnDelete = '<button type="button" class="btn btn-xs btn-danger btn-delete" data-url="'.route('instansi.destroy', $row->id).'" data-name="'.e($row->name).'" title="Hapus"><i class="fas fa-trash"></i></button>';

                    return '<div class="text-nowrap text-center">'.$btnEdit.$btnDelete.'</div>';
                })
                ->rawColumns(['name_display', 'category_display', 'address_display', 'action'])
                ->make(true);
        }

        $counts = [
            'total' => Instansi::count(),
            'sekolah' => Instansi::where('category', 'sekolah')->count(),
            'dudi' => Instansi::where('category', 'dudi')->count(),
            'dinas' => Instansi::where('category', 'dinas')->count(),
            'lainnya' => Instansi::where('category', 'lainnya')->count(),
        ];

        return view('master.instansi.index', compact('counts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:sekolah,dudi,dinas,lainnya',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'pic_name' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        $instansi = Instansi::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Instansi '{$instansi->name}' berhasil ditambahkan.",
                'data' => $instansi,
            ]);
        }

        return redirect()->route('instansi.index')->with('success', "Instansi '{$instansi->name}' berhasil ditambahkan.");
    }

    public function show(Instansi $instansi)
    {
        return response()->json($instansi);
    }

    public function update(Request $request, Instansi $instansi)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:sekolah,dudi,dinas,lainnya',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'pic_name' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        $instansi->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Instansi '{$instansi->name}' berhasil diperbarui.",
                'data' => $instansi,
            ]);
        }

        return redirect()->route('instansi.index')->with('success', "Instansi '{$instansi->name}' berhasil diperbarui.");
    }

    public function destroy(Instansi $instansi)
    {
        $name = $instansi->name;
        $instansi->delete();

        return response()->json([
            'success' => true,
            'message' => "Instansi '{$name}' berhasil dihapus.",
        ]);
    }

    /**
     * Endpoint API untuk autocomplete / search instansi dari form Perjadin
     */
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        $query = Instansi::query();
        if (! empty($q)) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            });
        }

        $results = $query->orderBy('name', 'asc')->limit(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'category_label' => $item->category_label,
                'address' => $item->address ?? '',
                'city' => $item->city ?? 'Kediri',
                'phone' => $item->phone ?? '',
            ];
        });

        return response()->json($results);
    }

    /**
     * Download template Excel untuk import instansi
     */
    public function downloadTemplate()
    {
        return Excel::download(new InstansiTemplateExport, 'template_import_instansi.xlsx');
    }

    /**
     * Upload & import instansi dari file Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new InstansiImport;
            Excel::import($import, $request->file('excel_file'));

            $counts = $import->getCounts();
            $msg = "Berhasil mengimpor data instansi. Ditambahkan: {$counts['created']} instansi baru, Diperbarui: {$counts['updated']} instansi.";
            if ($counts['skipped'] > 0) {
                $msg .= " ({$counts['skipped']} data dilewati).";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'counts' => $counts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file Excel: '.$e->getMessage(),
            ], 422);
        }
    }
}

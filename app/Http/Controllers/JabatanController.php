<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Jabatan::withCount(['guruJabatans', 'activeHolders'])
                ->orderBy('is_signatory', 'desc')
                ->orderBy('name', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_display', function ($row) {
                    $signatory = $row->is_signatory
                        ? '<span class="badge badge-primary ml-1" title="Penandatangan Dokumen Keuangan / SPPD"><i class="fas fa-file-signature mr-1"></i>Penandatangan SPJ</span>'
                        : '';

                    return '<strong>'.e($row->name).'</strong>'.$signatory;
                })
                ->addColumn('active_holders_count', function ($row) {
                    $count = $row->active_holders_count;
                    if ($count > 0) {
                        return '<span class="badge badge-success font-tabular">'.$count.' Guru Menjabat</span>';
                    }

                    return '<span class="badge badge-secondary font-tabular">Kosong</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button type="button" class="btn btn-xs btn-warning btn-edit-jabatan mr-1" '
                        .'data-id="'.$row->id.'" '
                        .'data-name="'.e($row->name).'" '
                        .'data-code="'.e($row->code ?? '').'" '
                        .'data-description="'.e($row->description ?? '').'" '
                        .'data-signatory="'.($row->is_signatory ? '1' : '0').'" '
                        .'data-update-url="'.route('jabatan.update', $row->id).'" '
                        .'title="Edit Jabatan"><i class="fas fa-edit"></i></button>';
                    $btn .= '<button type="button" class="btn btn-xs btn-danger btn-delete-jabatan" data-url="'.route('jabatan.destroy', $row->id).'" data-name="'.e($row->name).'" title="Hapus Jabatan"><i class="fas fa-trash"></i></button>';

                    return '<div class="text-nowrap text-center">'.$btn.'</div>';
                })
                ->rawColumns(['name_display', 'active_holders_count', 'action'])
                ->make(true);
        }

        return view('master.jabatan.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'is_signatory' => 'nullable',
            'description' => 'nullable|string',
        ]);

        $validated['is_signatory'] = $request->boolean('is_signatory');

        if (empty($validated['code'])) {
            $baseCode = Str::upper(Str::slug($validated['name'], '_'));
            $code = $baseCode;
            $counter = 1;
            while (Jabatan::where('code', $code)->exists()) {
                $code = $baseCode.'_'.$counter++;
            }
            $validated['code'] = $code;
        }

        $jabatan = Jabatan::create($validated);

        return response()->json([
            'message' => "Master Jabatan '{$jabatan->name}' berhasil ditambahkan.",
            'jabatan' => $jabatan,
        ]);
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'is_signatory' => 'nullable',
            'description' => 'nullable|string',
        ]);

        $validated['is_signatory'] = $request->boolean('is_signatory');

        if (empty($validated['code'])) {
            $validated['code'] = $jabatan->code ?: Str::upper(Str::slug($validated['name'], '_'));
        }

        $jabatan->update($validated);

        return response()->json([
            'message' => "Master Jabatan '{$jabatan->name}' berhasil diperbarui.",
            'jabatan' => $jabatan,
        ]);
    }

    public function destroy(Jabatan $jabatan)
    {
        $activeHoldersCount = $jabatan->activeHolders()->count();
        if ($activeHoldersCount > 0) {
            return response()->json([
                'message' => "Jabatan '{$jabatan->name}' tidak dapat dihapus karena masih aktif dijabat oleh {$activeHoldersCount} orang guru.",
            ], 422);
        }

        $name = $jabatan->name;
        $jabatan->delete();

        return response()->json([
            'message' => "Master Jabatan '{$name}' berhasil dihapus.",
        ]);
    }
}

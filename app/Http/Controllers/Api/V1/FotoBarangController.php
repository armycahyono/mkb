<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BarangDokumentasiResource;
use App\Models\BarangDokumentasi;
use App\Models\BarangDokumentasiFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FotoBarangController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', FiscalYearHelper::getActiveYear());
        $fundingSource = $request->query('funding_source', 'BOSP');

        $query = BarangDokumentasi::where('funding_source', $fundingSource)
            ->where('year', $year)
            ->with(['fotos', 'creator.guru'])
            ->orderBy('created_at', 'desc');

        return BarangDokumentasiResource::collection($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uraian' => 'required|string',
            'nama_toko' => 'nullable|string|max:150',
            'no_nota' => 'nullable|string|max:100',
            'tanggal_terima' => 'required|date',
            'nominal' => 'nullable|numeric|min:0',
            'kas_tunai_id' => 'nullable|exists:kas_tunais,id',
            'funding_source' => 'nullable|string',
            'photos' => 'required|array|min:1|max:3',
            'photos.*' => 'image|max:5120', // max 5MB
        ]);

        $year = (int) date('Y', strtotime($validated['tanggal_terima']));
        $fundingSource = $validated['funding_source'] ?? 'BOS';

        $dokumentasi = DB::transaction(function () use ($validated, $year, $fundingSource, $request) {
            $dok = BarangDokumentasi::create([
                'funding_source' => $fundingSource,
                'year' => $year,
                'no_nota' => $validated['no_nota'] ?? null,
                'tanggal_terima' => $validated['tanggal_terima'],
                'nama_toko' => $validated['nama_toko'] ?? null,
                'uraian' => $validated['uraian'],
                'nominal' => $validated['nominal'] ?? null,
                'kas_tunai_id' => $validated['kas_tunai_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $safeName = preg_replace('/[^A-Za-z0-9\-_]/', '-', $validated['uraian']);
            $safeName = trim(preg_replace('/-+/', '-', $safeName), '-');
            $baseDir = "dokumentasi_barang/{$year}/{$safeName}_{$dok->id}";

            foreach ($validated['photos'] as $index => $photo) {
                $filename = 'foto_'.($index + 1).'_'.Str::random(8).'.'.$photo->getClientOriginalExtension();
                $path = $photo->storeAs($baseDir, $filename, 'public');

                BarangDokumentasiFoto::create([
                    'barang_dokumentasi_id' => $dok->id,
                    'urutan' => $index + 1,
                    'file_path' => $path,
                    'keterangan' => trim($validated['uraian']),
                ]);
            }

            return $dok;
        });

        return new BarangDokumentasiResource($dokumentasi->load('fotos'));
    }
}

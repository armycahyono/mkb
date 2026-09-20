<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BarangDokumentasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'funding_source' => $this->funding_source,
            'year' => $this->year,
            'no_nota' => $this->no_nota,
            'tanggal_terima' => $this->tanggal_terima?->toDateString(),
            'nama_toko' => $this->nama_toko,
            'uraian' => $this->uraian,
            'nominal' => (float) ($this->nominal ?? 0),
            'foto_count' => $this->whenLoaded('fotos', fn () => $this->fotos->count(), 0),
            'is_complete' => $this->whenLoaded('fotos', fn () => $this->fotos->count() >= 3, false),
            'status_badge' => $this->status_badge,
            'sender_name' => $this->sender_name,
            'fotos' => $this->whenLoaded('fotos', function () {
                return $this->fotos->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'urutan' => $foto->urutan,
                        'file_url' => Storage::disk('public')->url($foto->file_path),
                        'keterangan' => $foto->keterangan,
                    ];
                });
            }),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanBarangResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tahun_anggaran' => (int) $this->tahun_anggaran,
            'nama_barang' => $this->nama_barang,
            'spesifikasi' => $this->spesifikasi,
            'volume' => (float) $this->volume,
            'satuan' => $this->satuan,
            'harga' => (float) $this->harga,
            'jumlah' => (float) $this->jumlah,
            'kegiatan' => $this->kegiatan,
            'bulan_pakai' => $this->bulan_pakai ? (int) $this->bulan_pakai : null,
            'keterangan' => $this->keterangan,
            'realisasi' => (bool) $this->realisasi,
            'status_label' => $this->realisasi ? 'Terealisasi' : 'Menunggu',
            'pemohon_name' => $this->pemohon?->name ?? $this->guru?->name ?? 'Staf Sekolah',
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}

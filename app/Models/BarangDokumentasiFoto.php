<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BarangDokumentasiFoto extends Model
{
    use HasFactory;

    protected $table = 'barang_dokumentasi_fotos';

    protected $fillable = [
        'barang_dokumentasi_id',
        'urutan',
        'file_path',
        'keterangan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function barangDokumentasi()
    {
        return $this->belongsTo(BarangDokumentasi::class, 'barang_dokumentasi_id');
    }

    public function getDownloadFilenameAttribute(): string
    {
        $parent = $this->barangDokumentasi;
        $nota = $parent ? $parent->safe_nota_name : 'NOTA';
        $urutan = 'Foto_'.($this->urutan ?: 1);

        // Keterangan foto diutamakan, jika kosong gunakan uraian barang dari parent
        $rawText = ! empty(trim((string) $this->keterangan))
            ? $this->keterangan
            : ($parent && ! empty(trim((string) $parent->uraian)) ? $parent->uraian : 'Dokumentasi');

        $safeText = preg_replace('/[^A-Za-z0-9\-_]/', '_', Str::title($rawText));
        $safeText = trim(preg_replace('/_+/', '_', $safeText), '_');

        // Batasi panjang teks keterangan jika terlalu panjang
        if (strlen($safeText) > 60) {
            $safeText = substr($safeText, 0, 60);
        }

        $extension = pathinfo($this->file_path, PATHINFO_EXTENSION) ?: 'jpg';

        return "{$nota}_{$urutan}_{$safeText}.{$extension}";
    }
}

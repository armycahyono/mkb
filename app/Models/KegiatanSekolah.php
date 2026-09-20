<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KegiatanSekolah extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kegiatan_sekolahs';

    protected $fillable = [
        'nama',
        'kode',
        'arkas_kegiatan_kode',
        'color',
        'is_active',
        'urutan',
        'keterangan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * Relasi ke referensi resmi ARKAS Kegiatan berdasarkan kode kegiatan.
     */
    public function arkasKegiatan(): BelongsTo
    {
        return $this->belongsTo(ArkasKegiatan::class, 'arkas_kegiatan_kode', 'kode_kegiatan');
    }

    /**
     * Relasi ke pengajuan barang RKAS.
     */
    public function pengajuanBarangs(): HasMany
    {
        return $this->hasMany(PengajuanBarang::class, 'kegiatan_sekolah_id');
    }

    /**
     * Teks lengkap kegiatan ARKAS rujukan (Kode - Nama Kegiatan).
     */
    public function getFullArkasKegiatanAttribute(): string
    {
        if ($this->arkasKegiatan) {
            return "{$this->arkasKegiatan->kode_kegiatan} - {$this->arkasKegiatan->kegiatan}";
        }

        return (string) ($this->arkas_kegiatan_kode ?: '');
    }
}

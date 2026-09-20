<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_barangs';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'tahun_anggaran',
        'guru_id',
        'pemohon_id',
        'nama_barang',
        'spesifikasi',
        'volume',
        'satuan',
        'harga',
        'jumlah',
        'kegiatan',
        'kegiatan_sekolah',
        'kegiatan_sekolah_id',
        'kode_rekening',
        'bulan_pakai',
        'keterangan',
        'realisasi',
        'is_recurring',
        'recurring_group_id',
        'utility_profile_id',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'realisasi' => 'boolean',
    ];

    public function utilityProfile()
    {
        return $this->belongsTo(UtilityProfile::class, 'utility_profile_id');
    }

    /**
     * Sibling items belonging to the same recurring group.
     */
    public function recurringSiblings()
    {
        return $this->hasMany(PengajuanBarang::class, 'recurring_group_id', 'recurring_group_id');
    }

    /**
     * Relasi ke Kegiatan Sekolah (program konkret internal sekolah).
     */
    public function kegiatanSekolah()
    {
        return $this->belongsTo(KegiatanSekolah::class, 'kegiatan_sekolah_id');
    }

    /**
     * Relation to the teacher (pemohon).
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Relation to the user who submitted the request.
     */
    public function pemohon()
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    /**
     * Relation to paired BOS Kertas Kerja items.
     */
    public function kertasKerjaItems()
    {
        return $this->belongsToMany(BosKertasKerjaItem::class, 'bos_kertas_kerja_item_pengajuans', 'pengajuan_barang_id', 'bos_kertas_kerja_item_id')
            ->withTimestamps()
            ->withPivot('notes');
    }

    /**
     * Accessor for total price (harga * jumlah).
     */
    protected function getTotalAttribute(): float
    {
        return (float) $this->harga * $this->jumlah;
    }
}

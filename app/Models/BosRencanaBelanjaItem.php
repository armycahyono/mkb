<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BosRencanaBelanjaItem extends Model
{
    use HasFactory;

    protected $table = 'bos_rencana_belanja_items';

    protected $fillable = [
        'bos_rencana_belanja_id',
        'bos_kertas_kerja_item_id',
        'nama_pasar',
        'spesifikasi',
        'volume_plan',
        'unit',
        'price',
        'total_amount',
    ];

    protected $casts = [
        'volume_plan' => 'float',
        'price' => 'float',
        'total_amount' => 'float',
    ];

    public function rencanaBelanja()
    {
        return $this->belongsTo(BosRencanaBelanja::class, 'bos_rencana_belanja_id');
    }

    public function kertasKerjaItem()
    {
        return $this->belongsTo(BosKertasKerjaItem::class, 'bos_kertas_kerja_item_id');
    }

    public function getEffectiveDescriptionAttribute(): string
    {
        return $this->nama_pasar ?: ($this->kertasKerjaItem?->description ?? '-');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BosRealisasi extends Model
{
    use SoftDeletes;

    protected $table = 'bos_realisasies';

    protected $fillable = [
        'bos_kertas_kerja_item_id',
        'no_transaksi',
        'no_nota',
        'bos_rencana_belanja_id',
        'perjadin_id',
        'utility_receipt_id',
        'year',
        'month',
        'transaction_date',
        'receipt_date',
        'volume',
        'unit',
        'price',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'receipt_date' => 'date',
        'volume' => 'float',
        'price' => 'float',
        'total_amount' => 'float',
    ];

    public function kertasKerjaItem()
    {
        return $this->belongsTo(BosKertasKerjaItem::class, 'bos_kertas_kerja_item_id');
    }

    public function rencanaBelanja()
    {
        return $this->belongsTo(BosRencanaBelanja::class, 'bos_rencana_belanja_id');
    }

    public function perjadin()
    {
        return $this->belongsTo(Perjadin::class, 'perjadin_id');
    }

    public function utilityReceipt()
    {
        return $this->belongsTo(UtilityReceipt::class, 'utility_receipt_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

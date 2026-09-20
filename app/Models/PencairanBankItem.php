<?php

namespace App\Models;

use App\Helpers\TerbilangHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PencairanBankItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pencairan_bank_items';

    protected $fillable = [
        'pencairan_bank_id',
        'type',
        'recipient_name',
        'bank_name',
        'account_number',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function pencairanBank()
    {
        return $this->belongsTo(PencairanBank::class, 'pencairan_bank_id');
    }

    public function perjadinItem()
    {
        return $this->hasOne(PerjadinItem::class, 'pencairan_bank_item_id');
    }

    public function rencanaBelanja()
    {
        return $this->hasOne(BosRencanaBelanja::class, 'pencairan_bank_item_id');
    }

    public function utilityReceipt()
    {
        return $this->hasOne(UtilityReceipt::class, 'pencairan_bank_item_id');
    }

    public function getTerbilangAttribute(): string
    {
        return TerbilangHelper::rupiah($this->amount);
    }
}

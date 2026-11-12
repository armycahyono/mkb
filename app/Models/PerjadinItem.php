<?php

namespace App\Models;

use App\Helpers\TerbilangHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerjadinItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'perjadin_items';

    protected $fillable = [
        'perjadin_id',
        'guru_id',
        'guru_bank_account_id',
        'pencairan_bank_item_id',
        'receipt_number',
        'amount',
        'ppn',
        'pph23',
        'net_amount',
        'is_paid',
    ];

    protected $casts = [
        'amount' => 'float',
        'ppn' => 'float',
        'pph23' => 'float',
        'net_amount' => 'float',
        'is_paid' => 'boolean',
    ];

    public function perjadin()
    {
        return $this->belongsTo(Perjadin::class, 'perjadin_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(GuruBankAccount::class, 'guru_bank_account_id');
    }

    public function pencairanBankItem()
    {
        return $this->belongsTo(PencairanBankItem::class, 'pencairan_bank_item_id');
    }

    public function getTerbilangAttribute(): string
    {
        return TerbilangHelper::rupiah($this->net_amount ?? 0);
    }
}

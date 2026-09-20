<?php

namespace App\Models;

use App\Helpers\TerbilangHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PencairanBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pencairan_banks';

    protected $fillable = [
        'funding_source',
        'letter_number',
        'letter_date',
        'bank_name',
        'bank_branch',
        'bank_address',
        'source_account_number',
        'source_account_name',
        'kepala_sekolah_id',
        'bendahara_id',
        'total_nominal',
        'total_accounts',
        'status',
        'cair_date',
        'notes',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'cair_date' => 'date',
        'total_nominal' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PencairanBankItem::class, 'pencairan_bank_id');
    }

    public function kepalaSekolah()
    {
        return $this->belongsTo(Guru::class, 'kepala_sekolah_id');
    }

    public function bendahara()
    {
        return $this->belongsTo(Guru::class, 'bendahara_id');
    }

    public function getTotalTerbilangAttribute(): string
    {
        return TerbilangHelper::rupiah($this->total_nominal);
    }

    public function getTotalAccountsWordsAttribute(): string
    {
        return TerbilangHelper::make($this->total_accounts);
    }

    public function scopeFunding($query, string $source)
    {
        return $query->where('funding_source', $source);
    }
}

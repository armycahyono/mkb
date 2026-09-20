<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruBankAccount extends Model
{
    use HasFactory;

    protected $table = 'guru_bank_accounts';

    protected $fillable = [
        'guru_id',
        'bank_name',
        'account_number',
        'account_holder',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
}

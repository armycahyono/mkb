<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UtilityProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'utility_profiles';

    protected $fillable = [
        'code',
        'name',
        'category',
        'customer_name',
        'customer_number',
        'meter_number',
        'idpel',
        'tariff_power',
        'provider',
        'payment_method',
        'description',
        'sort_order',
        'is_active',
        'is_budgeted',
        'default_reimburse_guru_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_budgeted' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(UtilityReceipt::class, 'utility_profile_id');
    }

    public function defaultReimburseGuru()
    {
        return $this->belongsTo(Guru::class, 'default_reimburse_guru_id');
    }

    public function kertasKerjaItems(): HasMany
    {
        return $this->hasMany(BosKertasKerjaItem::class, 'utility_profile_id');
    }

    public function pengajuanBarangs(): HasMany
    {
        return $this->hasMany(PengajuanBarang::class, 'utility_profile_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'listrik_pasca' => 'Listrik Pascaprabayar',
            'listrik_pra' => 'Listrik Prabayar (Token)',
            'telkom_speedy' => 'Telkom / Speedy',
            'internet' => 'Internet Dedicated',
            default => 'Lainnya',
        };
    }

    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->category) {
            'listrik_pasca' => '<span class="badge badge-success px-2 py-1"><i class="fas fa-plug mr-1"></i>Listrik Pasca</span>',
            'listrik_pra' => '<span class="badge badge-primary px-2 py-1" style="background-color: #0284c7 !important;"><i class="fas fa-bolt mr-1"></i>Listrik Token</span>',
            'telkom_speedy' => '<span class="badge badge-info px-2 py-1"><i class="fas fa-phone mr-1"></i>Telkom Speedy</span>',
            'internet' => '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-wifi mr-1"></i>Internet</span>',
            default => '<span class="badge badge-secondary px-2 py-1">Lainnya</span>',
        };
    }
}

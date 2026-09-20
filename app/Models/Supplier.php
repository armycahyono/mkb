<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'type', // offline / online
        'marketplace_name', // Toko Ladang, SIPLah Blibli, dll.
        'pic_name',
        'category',
        'npwp',
        'nik',
        'status_pkp',
        'bank_name',
        'account_number',
        'account_holder',
        'phone',
        'email',
        'address',
        'city',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rencanaBelanjas(): HasMany
    {
        return $this->hasMany(BosRencanaBelanja::class, 'supplier_id');
    }

    public function getTypeBadgeAttribute(): string
    {
        if ($this->type === 'online') {
            $mp = $this->marketplace_name ? ' ('.e($this->marketplace_name).')' : '';

            return '<span class="badge badge-info"><i class="fas fa-globe mr-1"></i>Online'.$mp.'</span>';
        }

        return '<span class="badge badge-light border"><i class="fas fa-store mr-1"></i>Offline</span>';
    }

    public static function categories(): array
    {
        try {
            return SupplierCategory::orderBy('sort_order')->orderBy('label')
                ->pluck('label', 'key')
                ->toArray();
        } catch (\Exception) {
            // Fallback when table does not exist yet (e.g., during migrations)
            return [
                'atk_fotokopi' => 'ATK & Fotokopi',
                'sarpras_bangunan' => 'Sarpras & Bangunan',
                'elektronik_it' => 'Elektronik & IT',
                'konsumsi_katering' => 'Konsumsi & Katering',
                'buku_pembelajaran' => 'Buku & Pembelajaran',
                'jasa_servis' => 'Jasa & Servis / Pemeliharaan',
                'lainnya' => 'Lain-lain',
            ];
        }
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category);
    }

    public function getCategoryBadgeAttribute(): string
    {
        $colors = [
            'atk_fotokopi' => 'info',
            'sarpras_bangunan' => 'warning',
            'elektronik_it' => 'primary',
            'konsumsi_katering' => 'success',
            'buku_pembelajaran' => 'indigo',
            'jasa_servis' => 'secondary',
            'lainnya' => 'dark',
        ];

        $color = $colors[$this->category] ?? 'secondary';

        return '<span class="badge badge-'.$color.'">'.e($this->category_label).'</span>';
    }

    public function getStatusPkpBadgeAttribute(): string
    {
        if ($this->status_pkp === 'pkp') {
            return '<span class="badge badge-success">PKP</span>';
        }

        return '<span class="badge badge-light border">Non-PKP</span>';
    }

    public function bankAccounts()
    {
        return $this->hasMany(SupplierBankAccount::class, 'supplier_id');
    }

    public function primaryBankAccount()
    {
        return $this->hasOne(SupplierBankAccount::class, 'supplier_id')->where('is_primary', true)->withDefault();
    }
}

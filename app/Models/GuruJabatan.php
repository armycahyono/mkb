<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruJabatan extends Model
{
    use HasFactory;

    protected $table = 'guru_jabatans';

    protected $fillable = [
        'guru_id',
        'jabatan_id',
        'sk_number',
        'sk_date',
        'sk_file',
        'period_year',
        'period_type',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'sk_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'sk_file_url',
        'expiration_status',
        'days_remaining',
    ];

    public function getSkFileUrlAttribute(): ?string
    {
        return $this->sk_file ? route('guru-jabatan.sk-file', $this->id) : null;
    }

    public function getExpirationStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'demisioner';
        }

        if (! $this->end_date) {
            return 'active';
        }

        $today = now()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        if ($end->isPast() && ! $end->isToday()) {
            return 'expired';
        }

        if ($end->lte($today->copy()->addDays(30))) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        $today = now()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        return (int) $today->diffInDays($end, false);
    }

    public function scopeActiveCurrently($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiredActive($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('is_active', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereDate('end_date', '<=', now()->addDays($days)->toDateString());
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    /**
     * Dapatkan pejabat yang aktif menjabat pada tanggal tertentu
     */
    public static function getPejabatByDate(string $role, mixed $date = null): ?self
    {
        $targetDate = $date ? date('Y-m-d', strtotime((string) $date)) : date('Y-m-d');

        return self::with('guru')
            ->whereHas('jabatan', function ($q) use ($role) {
                if ($role === 'kepala_sekolah') {
                    $q->where(function ($sub) {
                        $sub->where('code', 'KEPSEK')
                            ->orWhere('name', 'like', '%Kepala Sekolah%')
                            ->orWhere('name', 'like', '%Kepala SMKS%');
                    });
                } elseif ($role === 'bendahara_bosp') {
                    $q->where(function ($sub) {
                        $sub->where('code', 'BEND_BOSP')
                            ->orWhere('code', 'BEND_BOS')
                            ->orWhere('name', 'like', '%Bendahara BOSP%')
                            ->orWhere('name', 'like', '%Bendahara BOS%');
                    });
                } elseif ($role === 'bendahara_bpopp') {
                    $q->where(function ($sub) {
                        $sub->where('code', 'BEND_BPOPP')
                            ->orWhere('name', 'like', '%Bendahara BPOPP%');
                    });
                } else {
                    $q->where('code', $role)
                        ->orWhere('name', 'like', "%{$role}%");
                }
            })
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $targetDate);
            })
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $targetDate);
            })
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->first();
    }
}

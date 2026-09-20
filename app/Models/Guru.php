<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Guru extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gurus';

    protected $fillable = [
        'nip',
        'nik',
        'nuptk',
        'npwp',
        'name',
        'title_prefix',
        'title_suffix',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'employment_status',
        'pangkat_golongan',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name_with_title',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'guru_id');
    }

    public function bankAccounts()
    {
        return $this->hasMany(GuruBankAccount::class, 'guru_id')->orderByDesc('is_primary');
    }

    public function primaryBankAccount()
    {
        return $this->hasOne(GuruBankAccount::class, 'guru_id')->where('is_primary', true);
    }

    public function jabatans()
    {
        return $this->hasMany(GuruJabatan::class, 'guru_id')->orderByDesc('is_active')->orderByDesc('start_date');
    }

    public function activeJabatans()
    {
        return $this->hasMany(GuruJabatan::class, 'guru_id')->where('is_active', true)->with('jabatan');
    }

    /**
     * Format nama lengkap beserta gelar depan dan belakang.
     * Contoh: "Drs. Budi Santoso, M.Pd"
     */
    public function getFullNameWithTitleAttribute(): string
    {
        $prefix = trim((string) $this->title_prefix);
        $suffix = trim((string) $this->title_suffix);
        $name = trim((string) $this->name);

        $result = $prefix ? $prefix.' '.$name : $name;
        if ($suffix) {
            $result .= ', '.$suffix;
        }

        return $result;
    }

    /**
     * Rekomendasi role sistem berdasarkan penugasan jabatan aktif guru di SK.
     */
    public function getSuggestedRoleAttribute(): ?string
    {
        if (! Schema::hasTable('guru_jabatans')) {
            return null;
        }

        $activeJabatans = $this->relationLoaded('activeJabatans')
            ? $this->activeJabatans
            : $this->activeJabatans()->with('jabatan')->get();

        $roles = [];
        foreach ($activeJabatans as $gj) {
            $jabatan = $gj->jabatan;
            if (! $jabatan) {
                continue;
            }

            $code = strtoupper((string) $jabatan->code);
            $name = strtolower((string) $jabatan->name);

            if ($code === 'KS' || str_contains($name, 'kepala sekolah')) {
                $roles[] = User::ROLE_KEPALA_SEKOLAH;
            } elseif ($code === 'BEND_BOSP' || ($code === 'BENDAHARA' && str_contains($name, 'bos')) || (str_contains($name, 'bendahara') && (str_contains($name, 'bos') || str_contains($name, 'bosp')))) {
                $roles[] = User::ROLE_BENDAHARA_BOS;
            } elseif ($code === 'BEND_BPOPP' || (str_contains($name, 'bendahara') && str_contains($name, 'bpopp'))) {
                $roles[] = User::ROLE_BENDAHARA_BPOPP;
            } elseif ($code === 'WAKA_SARPRAS' || str_contains($name, 'sarpras')) {
                $roles[] = User::ROLE_WAKA_SARPRAS;
            } elseif ($code === 'TIM_BOSP' || ((str_contains($name, 'tim') || str_contains($name, 'team')) && (str_contains($name, 'bos') || str_contains($name, 'bosp')))) {
                $roles[] = User::ROLE_TEAM_BOSP;
            } elseif ($code === 'TIM_BPOPP' || ((str_contains($name, 'tim') || str_contains($name, 'team')) && str_contains($name, 'bpopp'))) {
                $roles[] = User::ROLE_TEAM_BPOPP;
            }
        }

        $priority = [
            User::ROLE_KEPALA_SEKOLAH,
            User::ROLE_BENDAHARA_BOS,
            User::ROLE_BENDAHARA_BPOPP,
            User::ROLE_WAKA_SARPRAS,
            User::ROLE_TEAM_BOSP,
            User::ROLE_TEAM_BPOPP,
        ];

        foreach ($priority as $pRole) {
            if (in_array($pRole, $roles, true)) {
                return $pRole;
            }
        }

        return null;
    }

    public function getSuggestedRoleLabelAttribute(): ?string
    {
        $role = $this->suggested_role;
        if (! $role) {
            return null;
        }

        return match ($role) {
            User::ROLE_KEPALA_SEKOLAH => 'Kepala Sekolah',
            User::ROLE_BENDAHARA_BOS => 'Bendahara BOS',
            User::ROLE_BENDAHARA_BPOPP => 'Bendahara BPOPP',
            User::ROLE_WAKA_SARPRAS => 'Waka Sarpras',
            User::ROLE_TEAM_BOSP => 'Tim BOSP',
            User::ROLE_TEAM_BPOPP => 'Tim BPOPP',
            default => null,
        };
    }

    /**
     * Mencocokkan input nama dari bot / pengguna dengan data guru yang ada di database.
     * Mendukung pembersihan sapaan (Pak, Bu, dll.) dan pencarian fleksibel.
     */
    public static function findByNameMatch(?string $query): ?self
    {
        if (empty($query)) {
            return null;
        }

        // 1. Bersihkan sapaan umum di awal nama
        $cleaned = trim((string) preg_replace('/^(pak|bpk|bapak|ibu|bu|ustadz|ust|mr|mrs)\.?\s+/i', '', trim($query)));
        if (mb_strlen($cleaned) < 2) {
            return null;
        }

        // 2. Kecocokan persis (case-insensitive)
        $exact = self::whereRaw('LOWER(name) = ?', [strtolower($cleaned)])->first();
        if ($exact) {
            return $exact;
        }

        // 3. Pencarian substring nama
        $candidates = self::where('name', 'like', "%{$cleaned}%")->get();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        if ($candidates->count() > 1) {
            // Prioritaskan yang namanya diawali kata pencarian (misal: "ARMY" pada "ARMY WIDHI CAHYONO")
            $startsWith = $candidates->first(fn ($g) => str_starts_with(strtolower($g->name), strtolower($cleaned)));
            if ($startsWith) {
                return $startsWith;
            }

            // Atau salah satu kata dalam namanya sama persis
            $wordMatch = $candidates->first(function ($g) use ($cleaned) {
                $words = preg_split('/\s+/', strtolower($g->name));

                return in_array(strtolower($cleaned), $words, true);
            });

            if ($wordMatch) {
                return $wordMatch;
            }

            return $candidates->first();
        }

        // 4. Jika query memiliki beberapa kata, cocokkan kata per kata
        $tokens = array_filter(preg_split('/\s+/', $cleaned), fn ($t) => mb_strlen($t) >= 3);
        if (! empty($tokens)) {
            $tokenQuery = self::query();
            foreach ($tokens as $token) {
                $tokenQuery->where('name', 'like', "%{$token}%");
            }
            $tokenMatch = $tokenQuery->first();
            if ($tokenMatch) {
                return $tokenMatch;
            }
        }

        return null;
    }
}

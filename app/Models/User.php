<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'telegram_chat_id', 'telegram_username', 'guru_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Sinkronisasi role user berdasarkan jabatan aktif guru yang tertaut.
     */
    public function syncRoleFromActiveJabatan(): bool
    {
        if (! $this->guru_id) {
            return false;
        }

        $suggestedRole = $this->guru?->suggested_role;
        if ($suggestedRole && $this->role !== $suggestedRole) {
            $this->role = $suggestedRole;
            $this->save();

            return true;
        }

        return false;
    }

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KEPALA_SEKOLAH = 'kepala_sekolah';

    public const ROLE_BENDAHARA_BOS = 'bendahara_bos';

    public const ROLE_BENDAHARA_BPOPP = 'bendahara_bpopp';

    public const ROLE_WAKA_SARPRAS = 'waka_sarpras';

    public const ROLE_TEAM_BOSP = 'team_bosp';

    public const ROLE_TEAM_BPOPP = 'team_bpopp';

    // Legacy aliases
    public const ROLE_BENDAHARA = 'bendahara';

    public const ROLE_TEAM_BOS = 'team_bos';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === 'admin';
    }

    public function isKepalaSekolah(): bool
    {
        return $this->role === self::ROLE_KEPALA_SEKOLAH;
    }

    public function isBendaharaBos(): bool
    {
        return empty($this->role)
            || $this->role === self::ROLE_BENDAHARA_BOS
            || $this->role === self::ROLE_BENDAHARA
            || $this->role === 'admin';
    }

    public function isBendaharaBpopp(): bool
    {
        return empty($this->role)
            || $this->role === self::ROLE_BENDAHARA_BPOPP
            || $this->role === self::ROLE_BENDAHARA
            || $this->role === 'admin';
    }

    public function isWakaSarpras(): bool
    {
        return $this->role === self::ROLE_WAKA_SARPRAS;
    }

    public function isTeamBosp(): bool
    {
        return $this->role === self::ROLE_TEAM_BOSP || $this->role === self::ROLE_TEAM_BOS;
    }

    public function isTeamBpopp(): bool
    {
        return $this->role === self::ROLE_TEAM_BPOPP;
    }

    public function isBendahara(): bool
    {
        return $this->isBendaharaBos() || $this->isBendaharaBpopp();
    }

    public function isTeamBos(): bool
    {
        return $this->isTeamBosp();
    }

    public function canAccessDataPokok(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBos()
            || $this->isBendaharaBpopp()
            || $this->isWakaSarpras()
            || $this->isTeamBosp()
            || $this->isTeamBpopp();
    }

    public function canAccessBosp(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBos()
            || $this->isWakaSarpras()
            || $this->isTeamBosp();
    }

    public function canAccessBospKas(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBos();
    }

    public function canAccessBpopp(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBpopp()
            || $this->isWakaSarpras()
            || $this->isTeamBpopp();
    }

    public function canAccessBpoppKas(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBpopp();
    }

    public function canManageSettings(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBos()
            || $this->isBendaharaBpopp();
    }

    public function canManageFinance(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isBendaharaBos()
            || $this->isBendaharaBpopp();
    }

    public function canBotFotoOnly(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return false;
        }

        return $this->isWakaSarpras() || $this->isTeamBosp() || $this->isTeamBpopp();
    }

    public function canBotViewSaldo(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isKepalaSekolah()
            || $this->isBendaharaBos()
            || $this->isBendaharaBpopp();
    }

    public function getRoleBadgeAttribute(): string
    {
        if ($this->isSuperAdmin()) {
            return '<span class="badge badge-danger px-2 py-1"><i class="fas fa-crown mr-1"></i>Super Admin</span>';
        }

        if ($this->isAdmin()) {
            return '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-user-gear mr-1"></i>Administrator</span>';
        }

        if ($this->isKepalaSekolah()) {
            return '<span class="badge badge-dark px-2 py-1"><i class="fas fa-user-shield mr-1"></i>Kepala Sekolah</span>';
        }

        if ($this->isBendaharaBpopp()) {
            return '<span class="badge badge-warning px-2 py-1"><i class="fas fa-wallet mr-1"></i>Bendahara BPOPP</span>';
        }

        if ($this->isWakaSarpras()) {
            return '<span class="badge px-2 py-1 text-white" style="background-color:#6f42c1;"><i class="fas fa-boxes-stacked mr-1"></i>Waka Sarpras</span>';
        }

        if ($this->isTeamBosp()) {
            return '<span class="badge badge-info px-2 py-1"><i class="fas fa-camera mr-1"></i>Tim BOSP</span>';
        }

        if ($this->isTeamBpopp()) {
            return '<span class="badge px-2 py-1 text-white" style="background-color:#20c997;"><i class="fas fa-camera mr-1"></i>Tim BPOPP</span>';
        }

        return '<span class="badge badge-primary px-2 py-1"><i class="fas fa-wallet mr-1"></i>Bendahara BOS</span>';
    }

    public function getRoleLabelAttribute(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }

        if ($this->isAdmin()) {
            return 'Administrator';
        }

        if ($this->isKepalaSekolah()) {
            return 'Kepala Sekolah';
        }

        if ($this->isBendaharaBpopp()) {
            return 'Bendahara BPOPP';
        }

        if ($this->isWakaSarpras()) {
            return 'Waka Sarpras';
        }

        if ($this->isTeamBosp()) {
            return 'Tim BOSP';
        }

        if ($this->isTeamBpopp()) {
            return 'Tim BPOPP';
        }

        return 'Bendahara BOS';
    }

    /**
     * Badge status keterhubungan akun Telegram.
     */
    public function getTelegramBadgeAttribute(): string
    {
        if ($this->telegram_chat_id) {
            $handle = $this->telegram_username ? '@'.$this->telegram_username : "ID: {$this->telegram_chat_id}";

            return '<span class="badge badge-success"><i class="fab fa-telegram-plane mr-1"></i> Terhubung ('.e($handle).')</span>';
        }

        return '<span class="badge badge-light border text-muted"><i class="fab fa-telegram-plane mr-1"></i> Belum Terhubung</span>';
    }
}

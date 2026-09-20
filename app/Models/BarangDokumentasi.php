<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BarangDokumentasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang_dokumentasis';

    protected $fillable = [
        'funding_source',
        'year',
        'no_nota',
        'tanggal_terima',
        'nama_toko',
        'uraian',
        'nominal',
        'kas_tunai_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal_terima' => 'date',
        'nominal' => 'decimal:2',
    ];

    protected $appends = [
        'sender_name',
        'sender_detail',
        'creator_role_label',
    ];

    public function fotos()
    {
        return $this->hasMany(BarangDokumentasiFoto::class, 'barang_dokumentasi_id')->orderBy('urutan', 'asc');
    }

    public function kasTunai()
    {
        return $this->belongsTo(KasTunai::class, 'kas_tunai_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSenderNameAttribute(): string
    {
        if (! $this->creator) {
            return 'Sistem / Tidak Diketahui';
        }

        if ($this->creator->guru?->name) {
            return $this->creator->guru->name;
        }

        return $this->creator->name ?: ($this->creator->username ?: 'Pengguna #'.$this->creator->id);
    }

    public function getSenderDetailAttribute(): string
    {
        if (! $this->creator) {
            return '-';
        }

        $parts = [];
        if ($this->creator->telegram_username) {
            $parts[] = '@'.$this->creator->telegram_username;
        } elseif ($this->creator->username && $this->creator->username !== $this->creator->name) {
            $parts[] = $this->creator->username;
        }

        if ($this->creator->role) {
            $parts[] = $this->creator_role_label;
        }

        return ! empty($parts) ? implode(' • ', $parts) : ($this->creator->email ?: '');
    }

    public function getCreatorRoleLabelAttribute(): string
    {
        if (! $this->creator || ! $this->creator->role) {
            return '';
        }

        return match ($this->creator->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'kepala_sekolah' => 'Kepala Sekolah',
            'bendahara_bos', 'bendahara' => 'Bendahara BOS',
            'bendahara_bpopp' => 'Bendahara BPOPP',
            'team_bosp', 'team_bos' => 'Tim BOS',
            'team_bpopp' => 'Tim BPOPP',
            'waka_sarpras' => 'Waka Sarpras',
            default => ucfirst(str_replace('_', ' ', (string) $this->creator->role)),
        };
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->fotos->count() >= 3;
    }

    public function getStatusBadgeAttribute(): array
    {
        $count = $this->fotos->count();
        if ($count >= 3) {
            return [
                'class' => 'badge-success',
                'label' => 'Lengkap ('.$count.' Foto)',
                'icon' => 'fas fa-check-circle',
            ];
        } elseif ($count > 0) {
            return [
                'class' => 'badge-warning',
                'label' => 'Kurang ('.$count.'/3 Foto)',
                'icon' => 'fas fa-exclamation-triangle',
            ];
        }

        return [
            'class' => 'badge-danger',
            'label' => 'Belum Ada Foto',
            'icon' => 'fas fa-times-circle',
        ];
    }

    public function getSafeNotaNameAttribute(): string
    {
        $nota = $this->no_nota ?: ('DOK-'.($this->id ?: 'BARANG'));
        $safe = preg_replace('/[^A-Za-z0-9\-_]/', '-', $nota);

        return trim(preg_replace('/-+/', '-', $safe), '-');
    }

    public function getSafeUraianNameAttribute(): string
    {
        $text = $this->uraian ?: 'Barang';
        $safe = preg_replace('/[^A-Za-z0-9\-_]/', '_', Str::title($text));

        return trim(preg_replace('/_+/', '_', $safe), '_');
    }
}

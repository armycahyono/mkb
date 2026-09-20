<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instansi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'instansis';

    protected $fillable = [
        'name',
        'category',
        'address',
        'city',
        'phone',
        'pic_name',
        'notes',
    ];

    public function perjadins(): HasMany
    {
        return $this->hasMany(Perjadin::class, 'instansi_id');
    }

    /**
     * Otomatis mendeteksi kategori instansi dari namanya (sekolah, dudi, dinas, lainnya).
     */
    public static function detectCategory(string $name): string
    {
        $upper = strtoupper(trim($name));

        // 1. Sekolah / Lembaga Pendidikan
        $sekolahKeywords = [
            'SMP', 'SMPN', 'SMA', 'SMAN', 'SMK', 'SMKN', 'SD', 'SDN', 'MI', 'MIN',
            'MTS', 'MTSN', 'MA', 'MAN', 'SLB', 'SEKOLAH', 'UNIVERSITAS', 'UNIV',
            'POLITEKNIK', 'POLTEK', 'INSTITUT', 'MADRASAH', 'PAUD', 'TK', 'KAMPUS', 'AKADEMI',
        ];
        foreach ($sekolahKeywords as $kw) {
            if (preg_match('/\b'.preg_quote($kw, '/').'\b/i', $upper)) {
                return 'sekolah';
            }
        }

        // 2. Dinas / Pemerintahan
        $dinasKeywords = [
            'DINAS', 'DISDIK', 'DISPORA', 'DISBUDPAR', 'DISNAKER', 'CABDIN', 'KACABDIN',
            'KECAMATAN', 'KELURAHAN', 'PEMERINTAH', 'PEMKAB', 'PEMKOT', 'PEMPROV',
            'KEMENAG', 'KEMENDIKBUD', 'KEMENKEU', 'BPOM', 'PUSKESMAS', 'RSUD', 'RUMAH SAKIT',
            'POLSEK', 'POLRES', 'POLDA', 'KORAMIL', 'KODIM', 'BAPPEDA', 'INSPEKTORAT',
            'BALAI', 'KANTOR', 'SEKRETARIAT', 'BPS', 'KUA',
        ];
        foreach ($dinasKeywords as $kw) {
            if (preg_match('/\b'.preg_quote($kw, '/').'\b/i', $upper)) {
                return 'dinas';
            }
        }

        // 3. Dunia Usaha & Industri (DUDI)
        $dudiKeywords = [
            'PT', 'CV', 'UD', 'TOKO', 'BENGKEL', 'INDUSTRI', 'PERUSAHAAN', 'HOTEL',
            'BANK', 'CORP', 'CORPORATION', 'TBK', 'FABRIK', 'PABRIK', 'STORE', 'DEALER',
            'PERCETAKAN', 'OFFSET', 'AGENCY', 'VENTURE', 'RESTO', 'RESTORAN', 'CAFE',
        ];
        foreach ($dudiKeywords as $kw) {
            if (preg_match('/\b'.preg_quote($kw, '/').'\b/i', $upper)) {
                return 'dudi';
            }
        }

        return 'lainnya';
    }

    /**
     * Label kategori berbahasa Indonesia.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'sekolah' => 'Sekolah / Pendidikan',
            'dudi' => 'DUDI / Perusahaan',
            'dinas' => 'Dinas / Pemerintahan',
            default => 'Lainnya',
        };
    }

    /**
     * Badge class bootstrap untuk kategori.
     */
    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->category) {
            'sekolah' => '<span class="badge badge-primary"><i class="fas fa-graduation-cap mr-1"></i>Sekolah</span>',
            'dudi' => '<span class="badge badge-warning text-dark"><i class="fas fa-industry mr-1"></i>DUDI</span>',
            'dinas' => '<span class="badge badge-info"><i class="fas fa-landmark mr-1"></i>Dinas</span>',
            default => '<span class="badge badge-secondary"><i class="fas fa-building mr-1"></i>Lainnya</span>',
        };
    }
}

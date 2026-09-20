<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArkasKegiatan extends Model
{
    use HasFactory;

    protected $table = 'arkas_kegiatans';

    protected $fillable = [
        'snp',
        'komponen',
        'kode_kegiatan',
        'kegiatan',
        'definisi_operasional',
    ];
}

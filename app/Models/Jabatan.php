<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jabatan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jabatans';

    protected $fillable = [
        'name',
        'code',
        'is_signatory',
        'description',
    ];

    protected $casts = [
        'is_signatory' => 'boolean',
    ];

    protected $appends = [
        'is_team',
    ];

    public function getIsTeamAttribute(): bool
    {
        $code = strtoupper($this->code ?? '');
        $name = strtolower($this->name ?? '');

        return str_starts_with($code, 'TIM_')
            || str_starts_with($code, 'PANITIA_')
            || str_starts_with($code, 'POKJA_')
            || str_starts_with($code, 'KOMITE_')
            || str_contains($name, 'tim')
            || str_contains($name, 'panitia')
            || str_contains($name, 'pokja')
            || str_contains($name, 'komite')
            || str_contains($name, 'anggota');
    }

    public function guruJabatans()
    {
        return $this->hasMany(GuruJabatan::class, 'jabatan_id');
    }

    public function activeHolders()
    {
        return $this->hasMany(GuruJabatan::class, 'jabatan_id')->where('is_active', true)->with('guru');
    }
}

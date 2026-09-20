<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BosKertasKerjaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bos_kertas_kerja_files';

    protected $fillable = [
        'year',
        'month',
        'filename',
        'file_path',
        'version',
        'description',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function makeActive(): void
    {
        self::where('year', $this->year)
            ->where('month', $this->month)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function items()
    {
        return $this->hasMany(BosKertasKerjaItem::class, 'bos_kertas_kerja_file_id');
    }
}

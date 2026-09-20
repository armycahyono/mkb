<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kas_categories';

    protected $fillable = [
        'code',
        'name',
        'type',
        'icon',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMasuk($query)
    {
        return $query->where('type', 'masuk');
    }

    public function scopeKeluar($query)
    {
        return $query->where('type', 'keluar');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function kasTunais(): HasMany
    {
        return $this->hasMany(KasTunai::class, 'kas_category_id');
    }

    public function getFormattedNameAttribute(): string
    {
        return ($this->icon ? $this->icon.' ' : '').$this->name;
    }
}

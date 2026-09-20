<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasTunaiItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kas_tunai_items';

    protected $fillable = [
        'kas_tunai_id',
        'item_name',
        'quantity',
        'unit',
        'price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function kasTunai()
    {
        return $this->belongsTo(KasTunai::class, 'kas_tunai_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArkasBarang extends Model
{
    use HasFactory;

    protected $table = 'arkas_barangs';

    protected $fillable = [
        'jenis_barang',
        'kode_rekening',
        'nama_barang',
        'satuan',
        'harga_min',
        'harga_max',
        'varian_count',
        'kode_belanja',
    ];

    protected $casts = [
        'harga_min' => 'decimal:2',
        'harga_max' => 'decimal:2',
        'varian_count' => 'integer',
    ];

    /**
     * Tampilan rentang harga terformat (misal: "Rp 163.000 - Rp 187.000")
     */
    public function getHargaRangeDisplayAttribute(): string
    {
        $min = number_format((float) $this->harga_min, 0, ',', '.');
        $max = number_format((float) $this->harga_max, 0, ',', '.');

        if ((float) $this->harga_min == (float) $this->harga_max) {
            return 'Rp '.$min;
        }

        return 'Rp '.$min.' - Rp '.$max;
    }
}

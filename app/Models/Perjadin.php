<?php

namespace App\Models;

use App\Helpers\TerbilangHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Perjadin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'perjadins';

    protected $fillable = [
        'funding_source',
        'bos_kertas_kerja_item_id',
        'bku_number',
        'account_code',
        'activity_purpose',
        'destination_name',
        'destination_address',
        'instansi_id',
        'departure_date',
        'return_date',
        'payment_date',
        'city',
        'kepala_sekolah_id',
        'bendahara_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'payment_date' => 'date',
    ];

    public function kertasKerjaItem()
    {
        return $this->belongsTo(BosKertasKerjaItem::class, 'bos_kertas_kerja_item_id');
    }

    public function realisasi()
    {
        return $this->hasOne(BosRealisasi::class, 'perjadin_id');
    }

    public function instansi()
    {
        return $this->belongsTo(Instansi::class, 'instansi_id');
    }

    public function items()
    {
        return $this->hasMany(PerjadinItem::class, 'perjadin_id');
    }

    public function kepalaSekolah()
    {
        return $this->belongsTo(Guru::class, 'kepala_sekolah_id');
    }

    public function bendahara()
    {
        return $this->belongsTo(Guru::class, 'bendahara_id');
    }

    public function scopeFunding($query, string $source)
    {
        return $query->where('funding_source', $source);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum('amount');
    }

    public function getTotalNetAmountAttribute(): float
    {
        return (float) $this->items->sum('net_amount');
    }

    public function getTotalTerbilangAttribute(): string
    {
        return TerbilangHelper::rupiah($this->total_net_amount);
    }

    /**
     * Dapatkan nomor BKU perorangan untuk kwitansi ke-$index (0-indexed).
     * Contoh: No BKU master "BPU070 - BPU074 / 2026"
     * Item index 0 -> "BPU070 / 2026", Item index 1 -> "BPU071 / 2026", dst.
     */
    public function getBkuNumberForItem(int $index): string
    {
        if (empty($this->bku_number)) {
            return '-';
        }

        $bku = trim($this->bku_number);

        // Ekstrak tahun anggaran jika ada di belakang (misal: " / 2026" atau "/2026")
        $yearSuffix = '';
        if (preg_match('/^(.*?)\s*\/\s*(\d{4})$/', $bku, $matches)) {
            $mainBku = trim($matches[1]);
            $yearSuffix = ' / '.$matches[2];
        } else {
            $mainBku = $bku;
            if ($this->departure_date) {
                $yearSuffix = ' / '.$this->departure_date->format('Y');
            }
        }

        // Cek jika format rentang: "BPU070 - BPU074" atau "BPU070 - 074"
        if (preg_match('/^([A-Za-z\s]*?)(\d+)\s*[-–—]\s*(?:[A-Za-z\s]*?)(\d+)$/', $mainBku, $rangeMatches)) {
            $prefix = $rangeMatches[1];
            $startNumStr = $rangeMatches[2];
            $padLength = strlen($startNumStr);
            $startNum = (int) $startNumStr;

            $currentNum = $startNum + $index;
            $formattedNum = str_pad((string) $currentNum, $padLength, '0', STR_PAD_LEFT);

            return $prefix.$formattedNum.$yearSuffix;
        }

        // Cek jika format nomor tunggal dengan angka: "BPU070"
        if (preg_match('/^([A-Za-z\s]*?)(\d+)$/', $mainBku, $singleMatches)) {
            $prefix = $singleMatches[1];
            $startNumStr = $singleMatches[2];
            $padLength = strlen($startNumStr);
            $startNum = (int) $startNumStr;

            $currentNum = $startNum + $index;
            $formattedNum = str_pad((string) $currentNum, $padLength, '0', STR_PAD_LEFT);

            return $prefix.$formattedNum.$yearSuffix;
        }

        return $this->bku_number;
    }

    /**
     * Dapatkan daftar nomor surat perintah pencairan bank (TNT) yang terhubung.
     *
     * @return Collection<int, string>
     */
    public function getTntLettersAttribute()
    {
        return $this->items
            ->map(fn ($item) => $item->pencairanBankItem?->pencairanBank?->letter_number)
            ->filter()
            ->unique()
            ->values();
    }
}

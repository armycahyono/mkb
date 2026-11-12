<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BosRencanaBelanja extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bos_rencana_belanjas';

    protected $fillable = [
        'supplier_id',
        'guru_id',
        'no_nota',
        'year',
        'month',
        'title',
        'type',
        'supplier_name',
        'planned_date',
        'status',
        'pencairan_bank_item_id',
        'bank_name',
        'account_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'planned_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function utilityReceipts()
    {
        return $this->hasMany(UtilityReceipt::class, 'bos_rencana_belanja_id');
    }

    public function items()
    {
        return $this->hasMany(BosRencanaBelanjaItem::class, 'bos_rencana_belanja_id');
    }

    public function realisasis()
    {
        return $this->hasMany(BosRealisasi::class, 'bos_rencana_belanja_id');
    }

    public function pencairanBankItem()
    {
        return $this->belongsTo(PencairanBankItem::class, 'pencairan_bank_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalAmountAttribute()
    {
        return (float) $this->items->sum('total_amount');
    }

    /**
     * Generate the next available No. Rencana Belanja
     * Format: RB{YY}-{SEQ} (e.g. RB26-001)
     * Sequence resets every year.
     */
    public static function generateNoNota($year = null, $month = null): string
    {
        $year = $year ? (int) $year : (int) date('Y');
        $shortYear = sprintf('%02d', $year % 100);

        $prefix = "RB{$shortYear}-";
        $oldPrefix = "N{$shortYear}-";
        $existingNotas = static::where(function ($q) use ($year, $prefix, $oldPrefix) {
            $q->where('year', $year)
                ->orWhere('no_nota', 'LIKE', $prefix.'%')
                ->orWhere('no_nota', 'LIKE', $oldPrefix.'%');
        })->pluck('no_nota');

        $maxSeq = 0;
        foreach ($existingNotas as $nota) {
            if ($nota && preg_match('/^(?:RB|N)\d{2}-(\d+)$/', $nota, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        $nextSeq = $maxSeq + 1;

        return sprintf('RB%s-%03d', $shortYear, $nextSeq);
    }
}

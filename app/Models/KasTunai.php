<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasTunai extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kas_tunais';

    protected $fillable = [
        'funding_source',
        'transaction_date',
        'transaction_type',
        'category',
        'kas_category_id',
        'amount',
        'description',
        'person_name',
        'guru_id',
        'pencairan_bank_id',
        'bos_rencana_belanja_id',
        'perjadin_item_id',
        'parent_kas_id',
        'panjar_status',
        'year',
        'sequence',
        'evidence_number',
        'receipt_file',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'year' => 'integer',
        'sequence' => 'integer',
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'id_display',
        'category_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (KasTunai $kas) {
            if ($kas->category === 'panjar_kerja' && (empty($kas->panjar_status) || $kas->panjar_status === 'none')) {
                $kas->panjar_status = 'pending';
            }

            if (empty($kas->year)) {
                $kas->year = (int) date('Y', strtotime($kas->transaction_date ?? 'now'));
            }

            if (empty($kas->sequence)) {
                $maxSeq = static::withTrashed()
                    ->where('year', $kas->year)
                    ->max('sequence');
                $kas->sequence = ($maxSeq ? (int) $maxSeq : 0) + 1;
            }

            if (! empty($kas->category) && empty($kas->kas_category_id)) {
                try {
                    $kas->kas_category_id = KasCategory::where('code', $kas->category)->value('id');
                } catch (\Throwable) {
                }
            }
        });

        static::updating(function (KasTunai $kas) {
            if ($kas->isDirty('category') || (empty($kas->kas_category_id) && ! empty($kas->category))) {
                try {
                    $kas->kas_category_id = KasCategory::where('code', $kas->category)->value('id');
                } catch (\Throwable) {
                }
            }
        });
    }

    public static function categories(): array
    {
        try {
            $categories = KasCategory::active()->ordered()->get();
            if ($categories->isNotEmpty()) {
                $result = [];
                foreach ($categories as $cat) {
                    $result[$cat->code] = [
                        'type' => $cat->type,
                        'label' => $cat->name,
                        'icon' => $cat->icon,
                        'is_system' => $cat->is_system,
                        'id' => $cat->id,
                    ];
                }

                return $result;
            }
        } catch (\Exception) {
            // Fallback during migrations or if table is unavailable
        }

        return [
            // Penerimaan
            'droping_sekolah' => ['type' => 'masuk', 'label' => 'Droping / Alokasi Dana Sekolah'],
            'tarik_bank' => ['type' => 'masuk', 'label' => 'Tarik Bank (Droping Kas)'],
            'sisa_panjar' => ['type' => 'masuk', 'label' => 'Kembalian Sisa Kasbon / Panjar'],
            'pengembalian_talangan' => ['type' => 'masuk', 'label' => 'Pengembalian Dana Talangan'],
            'sisa_perjadin' => ['type' => 'masuk', 'label' => 'Kembalian Sisa Perjadin'],
            'dana_talangan_masuk' => ['type' => 'masuk', 'label' => 'Penerimaan Dana Talangan'],
            'lainnya_masuk' => ['type' => 'masuk', 'label' => 'Penerimaan Lainnya'],

            // Pengeluaran
            'talangan_belanja' => ['type' => 'keluar', 'label' => 'Talangan Belanja Barang (Toko/SIPLah)'],
            'belanja_tunai' => ['type' => 'keluar', 'label' => 'Belanja Barang / Nota Langsung'],
            'panjar_kerja' => ['type' => 'keluar', 'label' => 'Kasbon / Panjar Belanja (Uang Muka)'],
            'operasional_laporan' => ['type' => 'keluar', 'label' => 'Operasional & Penunjang Laporan (Transport/Konsumsi/SPJ)'],
            'honor_tim' => ['type' => 'keluar', 'label' => 'Honor / Insentif Tim Pengelola'],
            'perjadin_tunai' => ['type' => 'keluar', 'label' => 'Pembayaran Perjadin Tunai'],
            'reimburse' => ['type' => 'keluar', 'label' => 'Reimburse / Ganti Uang Guru'],
            'setor_bank' => ['type' => 'keluar', 'label' => 'Setor Tunai ke Bank'],
            'bayar_talangan' => ['type' => 'keluar', 'label' => 'Pengembalian Dana Talangan'],
            'lainnya_keluar' => ['type' => 'keluar', 'label' => 'Pengeluaran Lainnya'],
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        if ($this->kasCategory) {
            return $this->kasCategory->name;
        }

        return self::categories()[$this->category]['label'] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }

    public function kasCategory()
    {
        return $this->belongsTo(KasCategory::class, 'kas_category_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function pencairanBank()
    {
        return $this->belongsTo(PencairanBank::class, 'pencairan_bank_id');
    }

    public function rencanaBelanja()
    {
        return $this->belongsTo(BosRencanaBelanja::class, 'bos_rencana_belanja_id');
    }

    public function perjadinItem()
    {
        return $this->belongsTo(PerjadinItem::class, 'perjadin_item_id');
    }

    public function parentKas()
    {
        return $this->belongsTo(KasTunai::class, 'parent_kas_id');
    }

    public function childKass()
    {
        return $this->hasMany(KasTunai::class, 'parent_kas_id');
    }

    public function items()
    {
        return $this->hasMany(KasTunaiItem::class, 'kas_tunai_id');
    }

    public function barangDokumentasi()
    {
        return $this->hasOne(BarangDokumentasi::class, 'kas_tunai_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIdDisplayAttribute(): string
    {
        // Format: KTYY-#### where YY is the two‑digit year of the transaction and the sequence resets each year.
        $year = $this->year ?? (int) date('Y', strtotime($this->transaction_date ?? 'now'));
        $seq = $this->sequence ?? $this->id;
        $yearCode = substr((string) $year, -2);

        return "KT{$yearCode}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function getSettledPanjarAmountAttribute(): float
    {
        if ($this->relationLoaded('childKass')) {
            return (float) $this->childKass
                ->whereIn('category', ['belanja_tunai', 'sisa_panjar', 'operasional_laporan', 'lainnya_keluar', 'reimburse'])
                ->sum('amount');
        }

        return (float) $this->childKass()
            ->whereIn('category', ['belanja_tunai', 'sisa_panjar', 'operasional_laporan', 'lainnya_keluar', 'reimburse'])
            ->sum('amount');
    }

    public function getRemainingPanjarAmountAttribute(): float
    {
        if ($this->panjar_status === 'settled') {
            return 0.0;
        }

        $settled = $this->settled_panjar_amount;
        $remaining = (float) $this->amount - $settled;

        return $remaining > 0 ? $remaining : 0.0;
    }

    public function isPanjarOpen(): bool
    {
        return in_array($this->panjar_status, ['pending', 'partial'], true);
    }

    public function scopeFunding($query, string $source)
    {
        return $query->where('funding_source', $source);
    }

    public function scopePendingPanjar($query)
    {
        return $query->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial']);
    }

    /**
     * Scope pengeluaran kas yang merupakan aliran keluar fisik riil dari brankas kas tunai bendahara.
     * Realisasi belanja dari kasbon (parent_kas_id != null) yang bukan reimburse tidak mengurangi kas brankas lagi,
     * karena dananya sudah dikeluarkan pada saat kasbon awal dicairkan.
     */
    public function scopeActualCashOutflow($query)
    {
        return $query->where('transaction_type', 'keluar')
            ->where(function ($q) {
                $q->whereNull('parent_kas_id')
                    ->orWhereIn('category', ['reimburse', 'bayar_talangan']);
            });
    }

    /**
     * Scope penerimaan kas yang merupakan aliran masuk fisik riil ke brankas kas tunai.
     */
    public function scopeActualCashInflow($query)
    {
        return $query->where('transaction_type', 'masuk');
    }

    /**
     * Hitung saldo kas tunai fisik riil yang tersedia di brankas bendahara.
     */
    public static function getAvailableCashBalance(string $fundingSource, ?string $asOfDate = null, ?int $excludeKasId = null): float
    {
        $inQuery = self::funding($fundingSource)->actualCashInflow();
        $outQuery = self::funding($fundingSource)->actualCashOutflow();

        if ($asOfDate) {
            $inQuery->whereDate('transaction_date', '<=', $asOfDate);
            $outQuery->whereDate('transaction_date', '<=', $asOfDate);
        }

        if ($excludeKasId) {
            $inQuery->where('id', '!=', $excludeKasId);
            $outQuery->where('id', '!=', $excludeKasId);
        }

        return (float) ($inQuery->sum('amount') - $outQuery->sum('amount'));
    }
}

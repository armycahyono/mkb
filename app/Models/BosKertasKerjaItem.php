<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BosKertasKerjaItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bos_kertas_kerja_items';

    protected $fillable = [
        'bos_kertas_kerja_file_id',
        'arkas_id',
        'year',
        'month',
        'account_code',
        'program_code',
        'kegiatan',
        'description',
        'volume',
        'unit',
        'price',
        'total_amount',
        'utility_profile_id',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function utilityProfile()
    {
        return $this->belongsTo(UtilityProfile::class, 'utility_profile_id');
    }

    public function file()
    {
        return $this->belongsTo(BosKertasKerjaFile::class, 'bos_kertas_kerja_file_id');
    }

    public function realisasis()
    {
        return $this->hasMany(BosRealisasi::class, 'bos_kertas_kerja_item_id');
    }

    public function rencanaBelanjaItems()
    {
        return $this->hasMany(BosRencanaBelanjaItem::class, 'bos_kertas_kerja_item_id');
    }

    public function pengajuanBarangs()
    {
        return $this->belongsToMany(PengajuanBarang::class, 'bos_kertas_kerja_item_pengajuans', 'bos_kertas_kerja_item_id', 'pengajuan_barang_id')
            ->withTimestamps()
            ->withPivot('notes');
    }

    public function perjadins()
    {
        return $this->hasMany(Perjadin::class, 'bos_kertas_kerja_item_id');
    }

    public function getRealizedVolumeAttribute()
    {
        return (float) $this->realisasis()->sum('volume');
    }

    public function getRemainingVolumeAttribute()
    {
        return max(0, (float) $this->volume - $this->realized_volume);
    }

    /**
     * Hitung sisa volume alokasi yang masih tersedia untuk Perjadin.
     * Memperhitungkan realisasi BKU dan kegiatan Perjadin aktif yang belum dicairkan ke BKU.
     */
    public function getAvailableVolumeForPerjadin(?int $excludePerjadinId = null): float
    {
        if (! $this->exists) {
            $realized = $this->relationLoaded('realisasis') ? (float) $this->realisasis->sum('volume') : 0;

            return max(0, (float) $this->volume - $realized);
        }

        $realized = (float) $this->realisasis()->sum('volume');

        $pendingPerjadinQuery = Perjadin::where('bos_kertas_kerja_item_id', $this->id)
            ->whereDoesntHave('realisasi');

        if ($excludePerjadinId) {
            $pendingPerjadinQuery->where('id', '!=', $excludePerjadinId);
        }

        $pendingPerjadinIds = $pendingPerjadinQuery->pluck('id');
        $pendingVol = $pendingPerjadinIds->isNotEmpty()
            ? (float) PerjadinItem::whereIn('perjadin_id', $pendingPerjadinIds)->count()
            : 0;

        return max(0, (float) $this->volume - ($realized + $pendingVol));
    }

    /**
     * Runtime in-memory caches to eliminate N+1 queries during DataTables rendering
     */
    protected ?string $memoizedRealizationStatus = null;

    protected static array $activeFileIdsCache = [];

    protected static array $groupedItemsByYear = [];

    public static function flushRealizationCaches(): void
    {
        self::$activeFileIdsCache = [];
        self::$groupedItemsByYear = [];
    }

    public static function makeGroupKey(?string $accountCode, ?string $kegiatan, ?string $description, ?string $unit, float $price): string
    {
        return trim((string) $accountCode).'|'.
            mb_strtolower(trim((string) $kegiatan)).'|'.
            mb_strtolower(trim((string) $description)).'|'.
            mb_strtolower(trim((string) $unit)).'|'.
            number_format($price, 2, '.', '');
    }

    /**
     * Load and cache all items and aggregated realisasi volumes for a year grouped by signature in memory.
     * Takes only 2-3 fast queries for the entire year!
     */
    public static function getGroupedItemsForYear(string|int $year): array
    {
        $yearStr = (string) $year;
        if (! isset(self::$groupedItemsByYear[$yearStr])) {
            $realizedVolByItemId = DB::table('bos_realisasies')
                ->whereNull('deleted_at')
                ->where('year', $year)
                ->groupBy('bos_kertas_kerja_item_id')
                ->select('bos_kertas_kerja_item_id', DB::raw('SUM(volume) as total_volume'))
                ->pluck('total_volume', 'bos_kertas_kerja_item_id')
                ->all();

            $items = self::where('year', $year)->get();

            foreach ($items as $item) {
                $item->cached_realized_vol = (float) ($realizedVolByItemId[$item->id] ?? 0);
            }

            self::$groupedItemsByYear[$yearStr] = [
                'grouped' => $items->groupBy(function ($item) {
                    return self::makeGroupKey($item->account_code, $item->kegiatan, $item->description, $item->unit, (float) $item->price);
                }),
                'volumes' => $realizedVolByItemId,
            ];
        }

        return self::$groupedItemsByYear[$yearStr];
    }

    /**
     * Determine realization status of this item for its budget month:
     * - 'exhausted': sudah dibelanjakan & alokasi anggaran bulan itu terserap habis (vol habis, jumlah habis) -> baris gray
     * - 'partial': sudah dibelanjakan tetapi masih ada sisa volume dan/atau anggaran -> hanya gembok
     * - 'normal': belum dibelanjakan sama sekali -> normal
     */
    public function getRealizationStatusAttribute(): string
    {
        if (! $this->exists) {
            return 'normal';
        }

        if ($this->memoizedRealizationStatus !== null) {
            return $this->memoizedRealizationStatus;
        }

        $year = (string) $this->year;
        $context = self::getGroupedItemsForYear($this->year);
        $grouped = $context['grouped'];
        $realizedVolMap = $context['volumes'];

        $directRealizedVol = (float) ($realizedVolMap[$this->id] ?? 0);
        $itemVol = (float) $this->volume;

        $months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $targetIndex = array_search($this->month, $months);

        // Jika bulan tidak valid, gunakan evaluasi direct
        if ($targetIndex === false) {
            if ($directRealizedVol <= 0.001) {
                return $this->memoizedRealizationStatus = 'normal';
            }

            return $this->memoizedRealizationStatus = ($directRealizedVol >= ($itemVol - 0.001)) ? 'exhausted' : 'partial';
        }

        $groupKey = self::makeGroupKey($this->account_code, $this->kegiatan, $this->description, $this->unit, (float) $this->price);
        $matchingItems = $grouped->get($groupKey) ?? collect([$this]);

        // Total akumulasi belanja di seluruh kelompok item ini
        $totalRealizedVol = (float) $matchingItems->sum('cached_realized_vol');

        // Jika belum pernah dibelanjakan sama sekali
        if ($totalRealizedVol <= 0.001 && $directRealizedVol <= 0.001) {
            return $this->memoizedRealizationStatus = 'normal';
        }

        // Ambil active file IDs (di-cache per tahun)
        if (! isset(self::$activeFileIdsCache[$year])) {
            self::$activeFileIdsCache[$year] = BosKertasKerjaFile::where('year', $this->year)
                ->where('is_active', true)
                ->pluck('id')
                ->all();
        }
        $activeFileIds = self::$activeFileIdsCache[$year];

        $budgetItems = ! empty($activeFileIds)
            ? $matchingItems->whereIn('bos_kertas_kerja_file_id', $activeFileIds)
            : collect();

        if ($budgetItems->isEmpty()) {
            $budgetItems = $matchingItems->where('bos_kertas_kerja_file_id', $this->bos_kertas_kerja_file_id);
        }

        // Hitung total alokasi sebelum bulan ini dan alokasi bulan ini
        $priorMonths = array_slice($months, 0, $targetIndex);
        $priorBudgetVol = (float) $budgetItems->whereIn('month', $priorMonths)->sum('volume');
        $currentMonthBudgetVol = (float) $budgetItems->where('month', $this->month)->sum('volume');

        if ($currentMonthBudgetVol <= 0.001) {
            $currentMonthBudgetVol = $itemVol;
        }

        // Cek apakah realisasi sudah menyentuh bulan ini (atau ada direct realisasi pada item ini)
        $isAbsorbingThisMonth = ($totalRealizedVol > ($priorBudgetVol + 0.001)) || ($directRealizedVol > 0.001);

        if (! $isAbsorbingThisMonth) {
            return $this->memoizedRealizationStatus = 'normal';
        }

        // Cek apakah alokasi bulan ini (dan bulan sebelumnya) sudah terserap habis
        $isFullyExhausted = ($totalRealizedVol >= ($priorBudgetVol + $currentMonthBudgetVol - 0.001))
            || ($directRealizedVol >= ($itemVol - 0.001) && $itemVol > 0);

        return $this->memoizedRealizationStatus = ($isFullyExhausted ? 'exhausted' : 'partial');
    }

    /**
     * Check if item is locked (either exhausted or partially absorbed).
     */
    public function getIsLockedAttribute(): bool
    {
        return $this->realization_status !== 'normal';
    }

    /**
     * Check if budget volume and total amount are completely absorbed.
     */
    public function getIsExhaustedAttribute(): bool
    {
        return $this->realization_status === 'exhausted';
    }
}

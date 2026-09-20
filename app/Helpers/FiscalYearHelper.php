<?php

namespace App\Helpers;

use App\Models\BosKertasKerjaFile;
use App\Models\KasTunai;
use App\Models\PencairanBank;
use App\Models\Perjadin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FiscalYearHelper
{
    /**
     * Dapatkan tahun anggaran yang sedang aktif dalam sesi
     */
    public static function getActiveYear(): int
    {
        return (int) session('fiscal_year', (int) date('Y'));
    }

    /**
     * Simpan tahun anggaran aktif ke dalam sesi
     */
    public static function setActiveYear(int $year): void
    {
        session(['fiscal_year' => $year]);
    }

    /**
     * Periksa apakah tahun anggaran yang sedang aktif adalah tahun kalender berjalan
     */
    public static function isCurrentYear(?int $year = null): bool
    {
        $checkYear = $year ?? self::getActiveYear();

        return $checkYear === (int) date('Y');
    }

    /**
     * Dapatkan daftar seluruh tahun anggaran yang tersedia di sistem
     *
     * @return Collection<int, int>
     */
    public static function getAvailableYears(): Collection
    {
        $current = (int) date('Y');
        $years = collect([$current + 1, $current, $current - 1, $current - 2]);

        try {
            if (Schema::hasTable('bos_kertas_kerja_files')) {
                $kkYears = BosKertasKerjaFile::whereNotNull('year')->pluck('year');
                $years = $years->concat($kkYears);
            }

            if (Schema::hasTable('pencairan_banks')) {
                $pbYears = PencairanBank::whereNotNull('letter_date')
                    ->pluck('letter_date')
                    ->map(fn ($d) => (int) date('Y', strtotime($d)));
                $years = $years->concat($pbYears);
            }

            if (Schema::hasTable('perjadins')) {
                $pjYears = Perjadin::whereNotNull('departure_date')
                    ->pluck('departure_date')
                    ->map(fn ($d) => (int) date('Y', strtotime($d)));
                $years = $years->concat($pjYears);
            }

            if (Schema::hasTable('kas_tunais')) {
                $ktYears = KasTunai::whereNotNull('transaction_date')
                    ->pluck('transaction_date')
                    ->map(fn ($d) => (int) date('Y', strtotime($d)));
                $years = $years->concat($ktYears);
            }
        } catch (\Throwable) {
            // Gunakan tahun default jika query gagal
        }

        return $years->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y >= 2020 && $y <= ($current + 3))
            ->unique()
            ->sortDesc()
            ->values();
    }
}

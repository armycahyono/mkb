<?php

namespace App\Helpers;

class TerbilangHelper
{
    private static array $bilangan = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
        'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
    ];

    /**
     * Konversi angka bulat ke kata terbilang
     */
    public static function make(float|int|string $angka): string
    {
        $angka = abs((float) $angka);
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = ' '.self::$bilangan[(int) $angka];
        } elseif ($angka < 20) {
            $terbilang = self::make($angka - 10).' Belas';
        } elseif ($angka < 100) {
            $terbilang = self::make($angka / 10).' Puluh '.self::make(fmod($angka, 10));
        } elseif ($angka < 200) {
            $terbilang = ' Seratus '.self::make($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = self::make($angka / 100).' Ratus '.self::make(fmod($angka, 100));
        } elseif ($angka < 2000) {
            $terbilang = ' Seribu '.self::make($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = self::make($angka / 1000).' Ribu '.self::make(fmod($angka, 1000));
        } elseif ($angka < 1000000000) {
            $terbilang = self::make($angka / 1000000).' Juta '.self::make(fmod($angka, 1000000));
        } elseif ($angka < 1000000000000) {
            $terbilang = self::make($angka / 1000000000).' Miliar '.self::make(fmod($angka, 1000000000));
        } elseif ($angka < 1000000000000000) {
            $terbilang = self::make($angka / 1000000000000).' Triliun '.self::make(fmod($angka, 1000000000000));
        }

        return trim(preg_replace('/\s+/', ' ', $terbilang));
    }

    /**
     * Konversi ke format mata uang Rupiah
     * Contoh: "Dua Puluh Delapan Juta Seratus Tujuh Puluh Empat Ribu Rupiah"
     */
    public static function rupiah(float|int|string $angka, string $suffix = 'Rupiah'): string
    {
        $hasil = self::make($angka);
        if (empty($hasil) || (float) $angka === 0.0) {
            $hasil = 'Nol';
        }

        return ucwords(strtolower($hasil)).($suffix ? ' '.$suffix : '');
    }
}

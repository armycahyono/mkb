<?php

namespace Database\Seeders;

use App\Models\KegiatanSekolah;
use Illuminate\Database\Seeder;

class KegiatanSekolahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presets = [
            [
                'nama' => 'Pramuka',
                'kode' => 'PRA',
                'arkas_kegiatan_kode' => '03.03.06',
                'color' => '#d97706', // Amber / Coklat
                'urutan' => 1,
                'keterangan' => 'Ekstrakurikuler Pramuka wajib dan kegiatan perkemahan',
            ],
            [
                'nama' => 'PMR',
                'kode' => 'PMR',
                'arkas_kegiatan_kode' => '03.03.07',
                'color' => '#dc2626', // Merah Palang Merah
                'urutan' => 2,
                'keterangan' => 'Palang Merah Remaja dan pertolongan pertama',
            ],
            [
                'nama' => 'OSIS',
                'kode' => 'OSIS',
                'arkas_kegiatan_kode' => '03.03.07',
                'color' => '#0284c7', // Biru Muda
                'urutan' => 3,
                'keterangan' => 'Kegiatan kesiswaan, kepengurusan OSIS, dan event siswa',
            ],
            [
                'nama' => 'UKS',
                'kode' => 'UKS',
                'arkas_kegiatan_kode' => '03.05.02',
                'color' => '#059669', // Hijau Kesehatan
                'urutan' => 4,
                'keterangan' => 'Unit Kesehatan Sekolah, obat-obatan dan perawatan medis siswa',
            ],
            [
                'nama' => 'TU',
                'kode' => 'TU',
                'arkas_kegiatan_kode' => '06.05.08',
                'color' => '#475569', // Slate Abu-abu
                'urutan' => 5,
                'keterangan' => 'Tata Usaha / Kantor / Pengadaan ATK dan perlengkapan administrasi',
            ],
            [
                'nama' => 'PKL',
                'kode' => 'PKL',
                'arkas_kegiatan_kode' => '07.10.02',
                'color' => '#7c3aed', // Ungu
                'urutan' => 6,
                'keterangan' => 'Praktik Kerja Lapangan siswa ke dunia industri / institusi pasangan',
            ],
            [
                'nama' => 'Prakerin',
                'kode' => 'PRAKERIN',
                'arkas_kegiatan_kode' => '07.10.02',
                'color' => '#6d28d9', // Violet Gelap
                'urutan' => 7,
                'keterangan' => 'Praktik Kerja Industri siswa SMK',
            ],
            [
                'nama' => 'KI',
                'kode' => 'KI',
                'arkas_kegiatan_kode' => '07.10.02',
                'color' => '#4f46e5', // Indigo
                'urutan' => 8,
                'keterangan' => 'Kunjungan Industri siswa ke pabrik dan perusahaan mitra',
            ],
            [
                'nama' => 'Kurikulum',
                'kode' => 'KUR',
                'arkas_kegiatan_kode' => '08.04.08',
                'color' => '#1d4ed8', // Biru Navy
                'urutan' => 9,
                'keterangan' => 'Asesmen, ujian sekolah, modul ajar, dan perangkat pembelajaran',
            ],
            [
                'nama' => 'Sarpras',
                'kode' => 'SAR',
                'arkas_kegiatan_kode' => '05.08.01',
                'color' => '#ea580c', // Oranye
                'urutan' => 10,
                'keterangan' => 'Sarana & Prasarana, perbaikan gedung, pengecatan dan peralatan sekolah',
            ],
            [
                'nama' => 'Perpustakaan',
                'kode' => 'PERPUS',
                'arkas_kegiatan_kode' => '02.02.01',
                'color' => '#0d9488', // Teal
                'urutan' => 11,
                'keterangan' => 'Pemberdayaan perpustakaan, pengadaan buku bacaan dan literasi',
            ],
            [
                'nama' => 'BKK',
                'kode' => 'BKK',
                'arkas_kegiatan_kode' => '03.11.02',
                'color' => '#db2777', // Pink Fusia
                'urutan' => 12,
                'keterangan' => 'Bursa Kerja Khusus, penelusuran tamatan (tracer study) dan rekrutmen kerja',
            ],
        ];

        foreach ($presets as $p) {
            KegiatanSekolah::updateOrCreate(
                ['nama' => $p['nama']],
                $p
            );
        }
    }
}

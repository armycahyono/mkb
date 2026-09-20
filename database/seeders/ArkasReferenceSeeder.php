<?php

namespace Database\Seeders;

use App\Models\ArkasBarang;
use App\Models\ArkasKegiatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArkasReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedKegiatans();
        $this->seedBarangs();
    }

    /**
     * Seed arkas_kegiatans dari TSV
     */
    protected function seedKegiatans(): void
    {
        $filePath = database_path('data/arkas_kegiatan.tsv');
        if (! file_exists($filePath)) {
            $this->command->error("File kegiatan tidak ditemukan di: {$filePath}");

            return;
        }

        DB::table('arkas_kegiatans')->truncate();

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 0, "\t");

        $rows = [];
        $count = 0;

        while (($data = fgetcsv($handle, 0, "\t")) !== false) {
            if (empty(array_filter($data, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $snp = trim($data[1] ?? '');
            $komponen = trim($data[2] ?? '');
            $kodeKegiatan = trim($data[3] ?? '');
            $kegiatan = trim($data[4] ?? '');
            $definisi = isset($data[5]) ? trim($data[5]) : null;

            if ($kodeKegiatan === '' && $kegiatan === '') {
                continue;
            }

            $rows[] = [
                'snp' => $snp,
                'komponen' => $komponen,
                'kode_kegiatan' => $kodeKegiatan,
                'kegiatan' => $kegiatan,
                'definisi_operasional' => $definisi !== '' ? $definisi : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= 50) {
                ArkasKegiatan::insert($rows);
                $count += count($rows);
                $rows = [];
            }
        }
        fclose($handle);

        if (! empty($rows)) {
            ArkasKegiatan::insert($rows);
            $count += count($rows);
        }

        $this->command->info("Berhasil mengimpor {$count} data kegiatan ARKAS.");
    }

    /**
     * Seed arkas_barangs dari TSV dengan pengelompokan (grouping rentang harga)
     */
    protected function seedBarangs(): void
    {
        $filePath = database_path('data/arkas_barang.tsv');
        if (! file_exists($filePath)) {
            $this->command->error("File barang tidak ditemukan di: {$filePath}");

            return;
        }

        DB::table('arkas_barangs')->truncate();

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 0, "\t");

        $grouped = [];

        while (($data = fgetcsv($handle, 0, "\t")) !== false) {
            if (empty(array_filter($data, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $jenis = trim($data[1] ?? '');
            $rekening = trim($data[2] ?? '');
            $nama = trim($data[3] ?? '');
            $satuan = trim($data[4] ?? '');
            $rawHarga = trim($data[5] ?? '0');
            $kodeBelanja = trim($data[6] ?? '');

            if ($nama === '' || $rekening === '') {
                continue;
            }

            // Bersihkan format harga (misal: " 186,000 " -> 186000)
            $cleanHarga = (float) str_replace([',', ' ', '.'], ['', '', ''], $rawHarga);

            $key = strtolower($rekening.'|'.$nama.'|'.$satuan);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'jenis_barang' => $jenis,
                    'kode_rekening' => $rekening,
                    'nama_barang' => $nama,
                    'satuan' => $satuan,
                    'prices' => [],
                    'kode_belanja' => $kodeBelanja,
                ];
            }

            $grouped[$key]['prices'][] = $cleanHarga;

            if (empty($grouped[$key]['kode_belanja']) && ! empty($kodeBelanja)) {
                $grouped[$key]['kode_belanja'] = $kodeBelanja;
            }
            if (empty($grouped[$key]['jenis_barang']) && ! empty($jenis)) {
                $grouped[$key]['jenis_barang'] = $jenis;
            }
        }
        fclose($handle);

        $rows = [];
        $count = 0;

        foreach ($grouped as $item) {
            $prices = $item['prices'];
            $hargaMin = ! empty($prices) ? min($prices) : 0;
            $hargaMax = ! empty($prices) ? max($prices) : 0;

            $rows[] = [
                'jenis_barang' => $item['jenis_barang'],
                'kode_rekening' => $item['kode_rekening'],
                'nama_barang' => $item['nama_barang'],
                'satuan' => $item['satuan'],
                'harga_min' => $hargaMin,
                'harga_max' => $hargaMax,
                'varian_count' => count($prices),
                'kode_belanja' => $item['kode_belanja'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= 50) {
                ArkasBarang::insert($rows);
                $count += count($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            ArkasBarang::insert($rows);
            $count += count($rows);
        }

        $this->command->info("Berhasil mengimpor {$count} item barang referensi ARKAS unik (dirangkum dari varian harga).");
    }
}

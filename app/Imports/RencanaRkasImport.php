<?php

namespace App\Imports;

use App\Models\ArkasBarang;
use App\Models\ArkasKegiatan;
use App\Models\Guru;
use App\Models\KegiatanSekolah;
use App\Models\PengajuanBarang;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RencanaRkasImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithMultipleSheets
{
    private int $created = 0;

    private int $skipped = 0;

    private array $warnings = [];

    private static array $bulanMap = [
        'jan' => 'Januari', 'januari' => 'Januari', '1' => 'Januari', '01' => 'Januari',
        'feb' => 'Februari', 'februari' => 'Februari', '2' => 'Februari', '02' => 'Februari',
        'mar' => 'Maret', 'maret' => 'Maret', '3' => 'Maret', '03' => 'Maret',
        'apr' => 'April', 'april' => 'April', '4' => 'April', '04' => 'April',
        'mei' => 'Mei', 'may' => 'Mei', '5' => 'Mei', '05' => 'Mei',
        'jun' => 'Juni', 'juni' => 'Juni', '6' => 'Juni', '06' => 'Juni',
        'jul' => 'Juli', 'juli' => 'Juli', '7' => 'Juli', '07' => 'Juli',
        'ags' => 'Agustus', 'agt' => 'Agustus', 'agustus' => 'Agustus', 'aug' => 'Agustus', '8' => 'Agustus', '08' => 'Agustus',
        'sep' => 'September', 'september' => 'September', '9' => 'September', '09' => 'September',
        'okt' => 'Oktober', 'oktober' => 'Oktober', 'oct' => 'Oktober', '10' => 'Oktober',
        'nov' => 'November', 'november' => 'November', '11' => 'November',
        'des' => 'Desember', 'desember' => 'Desember', 'dec' => 'Desember', '12' => 'Desember',
    ];

    public function __construct(
        public int $tahunAnggaran,
        public int $pemohonId,
        public ?int $utilityProfileId = null
    ) {}

    public function sheets(): array
    {
        // Hanya memproses sheet pertama (Rencana RKAS)
        return [
            0 => $this,
        ];
    }

    /**
     * @return array{created: int, skipped: int, warnings: array<string>}
     */
    public function getResults(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'warnings' => $this->warnings,
        ];
    }

    public function collection(Collection $rows): void
    {
        $preparedItems = [];
        $groupCounts = [];
        $rowNumber = 1; // baris 1 adalah header

        foreach ($rows as $row) {
            $rowNumber++;

            $namaBarang = trim((string) ($row['nama_barang'] ?? $row['barang'] ?? $row['uraian'] ?? ''));
            if ($namaBarang === '') {
                $this->skipped++;

                continue;
            }

            // 1. Standarisasi Bulan
            $bulanRaw = trim((string) ($row['bulan'] ?? $row['bulan_pakai'] ?? 'Januari'));
            $cleanBulanKey = strtolower($bulanRaw);
            $bulan = self::$bulanMap[$cleanBulanKey] ?? (ucfirst($cleanBulanKey) ?: 'Januari');

            // 2. Pembersihan Volume
            $rawVol = (string) ($row['volume'] ?? 1);
            $volume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $rawVol));
            if ($volume <= 0) {
                $volume = 1.0;
            }

            // 3. Satuan
            $satuan = trim((string) ($row['satuan'] ?? 'Unit'));
            if ($satuan === '') {
                $satuan = 'Unit';
            }

            // 4. Spesifikasi & Keterangan
            $spesifikasi = trim((string) ($row['spesifikasi'] ?? ''));
            $keterangan = trim((string) ($row['keterangan'] ?? ''));

            // 5. Pembersihan Harga Satuan
            $rawPrice = (string) ($row['harga'] ?? $row['harga_satuan'] ?? 0);
            $harga = $this->parseMoney($rawPrice);

            // 6. Smart Matching Pemohon -> Guru
            $pemohonRaw = trim((string) ($row['pemohon'] ?? ''));
            $guruId = $this->matchGuru($pemohonRaw);

            if ($pemohonRaw !== '' && $guruId === null && ! in_array(strtolower($pemohonRaw), ['sekolah', 'bendahara', 'umum', '-'])) {
                $prefixNote = "Pemohon: {$pemohonRaw}";
                $keterangan = $keterangan !== '' ? "{$prefixNote}. {$keterangan}" : $prefixNote;
            }

            // 7. Smart Matching Kegiatan Sekolah & Kegiatan ARKAS
            $kegiatanRaw = trim((string) ($row['kegiatan'] ?? $row['kode_kegiatan'] ?? ''));
            $kegiatanSekolahExplicit = trim((string) ($row['kegiatan_sekolah'] ?? $row['program_sekolah'] ?? ''));
            [$kegiatanSekolahId, $kegiatanSekolahNama, $kegiatan] = $this->matchKegiatanAndSekolah($kegiatanRaw, $kegiatanSekolahExplicit);

            // 8. Smart Matching Barang ARKAS (Kode Rekening & Validasi Harga Max)
            $kodeRekening = null;
            $refBarang = ArkasBarang::whereRaw('LOWER(nama_barang) = ?', [strtolower($namaBarang)])->first();
            if (! $refBarang) {
                $refBarang = ArkasBarang::whereRaw('LOWER(nama_barang) LIKE ?', ['%'.strtolower($namaBarang).'%'])->first();
            }

            if ($refBarang) {
                $kodeRekening = $refBarang->kode_rekening;
                if ($refBarang->harga_max > 0 && $harga > $refBarang->harga_max) {
                    $this->warnings[] = "Baris {$rowNumber} ('{$namaBarang}'): Harga Rp ".number_format($harga, 0, ',', '.').
                        ' melebihi batas ARKAS (Rp '.number_format($refBarang->harga_max, 0, ',', '.').')';
                }
            }

            $jumlah = $volume * $harga;

            // Generate Group Key untuk mendeteksi alokasi multi-bulan (pengajuan berulang)
            $groupKey = md5(strtolower($kegiatan).'|'.strtolower($kegiatanSekolahNama ?? '').'|'.strtolower($namaBarang).'|'.strtolower($spesifikasi).'|'.$harga.'|'.($guruId ?? '0'));

            if (! isset($groupCounts[$groupKey])) {
                $groupCounts[$groupKey] = [
                    'count' => 0,
                    'months' => [],
                    'uuid' => Str::uuid()->toString(),
                ];
            }
            $groupCounts[$groupKey]['count']++;
            $groupCounts[$groupKey]['months'][] = $bulan;

            $preparedItems[] = [
                'tahun_anggaran' => $this->tahunAnggaran,
                'guru_id' => $guruId,
                'pemohon_id' => $this->pemohonId,
                'utility_profile_id' => $this->utilityProfileId,
                'nama_barang' => $namaBarang,
                'spesifikasi' => $spesifikasi !== '' ? $spesifikasi : null,
                'kegiatan' => $kegiatan,
                'kegiatan_sekolah' => $kegiatanSekolahNama,
                'kegiatan_sekolah_id' => $kegiatanSekolahId,
                'kode_rekening' => $kodeRekening,
                'volume' => $volume,
                'satuan' => $satuan,
                'harga' => $harga,
                'jumlah' => $jumlah,
                'bulan_pakai' => $bulan,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
                'group_key' => $groupKey,
            ];
        }

        // Simpan seluruh entri yang valid
        foreach ($preparedItems as $item) {
            $groupKey = $item['group_key'];
            unset($item['group_key']);

            $isMulti = ($groupCounts[$groupKey]['count'] ?? 1) > 1;
            $item['is_recurring'] = $isMulti;
            $item['recurring_group_id'] = $isMulti ? $groupCounts[$groupKey]['uuid'] : null;

            PengajuanBarang::create($item);
            $this->created++;
        }
    }

    /**
     * Parse string angka/rupiah menjadi float murni.
     */
    private function parseMoney(string $rawPrice): float
    {
        $clean = trim($rawPrice);
        if ($clean === '') {
            return 0.0;
        }

        // Hapus simbol Rp dan spasi
        $clean = preg_replace('/[^0-9,.]/', '', $clean);

        // Jika ada titik ribuan dan koma desimal (contoh: 60.000,00)
        if (substr_count($clean, '.') > 0 && substr_count($clean, ',') > 0) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (substr_count($clean, '.') > 0 && substr_count($clean, ',') === 0) {
            // Jika ada titik tapi tidak ada koma (contoh: 60.000 atau 60000.50)
            if (preg_match('/\.\d{3}$/', $clean) || preg_match('/\.\d{3}\./', $clean)) {
                $clean = str_replace('.', '', $clean);
            }
        } elseif (substr_count($clean, ',') > 0 && substr_count($clean, '.') === 0) {
            // Jika hanya koma (contoh: 60000,00)
            if (preg_match('/,\d{3}$/', $clean)) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }
        }

        return (float) $clean;
    }

    /**
     * Pencocokan cerdas pemohon terhadap tabel guru (Exact -> Case Insensitive -> Partial Word).
     */
    private function matchGuru(string $pemohonRaw): ?int
    {
        if ($pemohonRaw === '' || in_array(strtolower($pemohonRaw), ['sekolah', 'bendahara', 'umum', '-'])) {
            return null;
        }

        $clean = strtolower($pemohonRaw);

        // 1. Exact match (case insensitive)
        $guru = Guru::whereRaw('LOWER(name) = ?', [$clean])->first();
        if ($guru) {
            return $guru->id;
        }

        // 2. Partial match kata kunci (misal: "army" cocok dengan "ARMY WIDHI CAHYONO")
        $guru = Guru::whereRaw('LOWER(name) LIKE ?', ["%{$clean}%"])->first();
        if ($guru) {
            return $guru->id;
        }

        // 3. Word-by-word match jika input terdiri dari beberapa kata (misal "pak army")
        $words = array_filter(explode(' ', $clean));
        foreach ($words as $w) {
            if (strlen($w) >= 3 && ! in_array($w, ['pak', 'ibu', 'bpk', 'ibu.', 'bapak', 'guru'])) {
                $guru = Guru::whereRaw('LOWER(name) LIKE ?', ["%{$w}%"])->first();
                if ($guru) {
                    return $guru->id;
                }
            }
        }

        return null;
    }

    /**
     * Pencocokan cerdas program/kegiatan sekolah dan mapping otomatis ke referensi ARKAS.
     *
     * @return array{0: ?int, 1: ?string, 2: string} [kegiatanSekolahId, kegiatanSekolahNama, arkasKegiatan]
     */
    private function matchKegiatanAndSekolah(string $kegiatanRaw, string $kegiatanSekolahExplicit = ''): array
    {
        $kegiatanSekolah = null;

        // 1. Jika ada kolom eksplisit kegiatan/program sekolah
        if ($kegiatanSekolahExplicit !== '') {
            $cleanExplicit = strtolower($kegiatanSekolahExplicit);
            $kegiatanSekolah = KegiatanSekolah::with('arkasKegiatan')
                ->whereRaw('LOWER(nama) = ?', [$cleanExplicit])
                ->orWhereRaw('LOWER(kode) = ?', [$cleanExplicit])
                ->orWhereRaw('LOWER(nama) LIKE ?', ["%{$cleanExplicit}%"])
                ->first();
        }

        // 2. Jika belum ketemu dan ada input $kegiatanRaw
        if (! $kegiatanSekolah && $kegiatanRaw !== '') {
            $cleanRaw = strtolower($kegiatanRaw);

            // Cek apakah $kegiatanRaw adalah nama atau kode KegiatanSekolah langsung (misal: "Pramuka", "OSIS", "TU", "PKL", dsb)
            $kegiatanSekolah = KegiatanSekolah::with('arkasKegiatan')
                ->whereRaw('LOWER(nama) = ?', [$cleanRaw])
                ->orWhereRaw('LOWER(kode) = ?', [$cleanRaw])
                ->first();

            // Jika belum ketemu, cek apakah ada kata kunci kegiatan sekolah di dalam $kegiatanRaw
            if (! $kegiatanSekolah) {
                $allSekolah = KegiatanSekolah::with('arkasKegiatan')->get();
                foreach ($allSekolah as $ks) {
                    if (str_contains($cleanRaw, strtolower($ks->nama)) || ($ks->kode && str_contains($cleanRaw, strtolower($ks->kode)))) {
                        $kegiatanSekolah = $ks;
                        break;
                    }
                }
            }
        }

        $kegiatanSekolahId = $kegiatanSekolah?->id;
        $kegiatanSekolahNama = $kegiatanSekolah?->nama;

        // 3. Tentukan nama/kode ARKAS
        // Jika $kegiatanRaw kosong atau sama persis dengan nama KegiatanSekolah, gunakan auto-map ARKAS dari KegiatanSekolah
        if ($kegiatanRaw === '' || ($kegiatanSekolah && (strtolower($kegiatanRaw) === strtolower($kegiatanSekolah->nama) || strtolower($kegiatanRaw) === strtolower((string) $kegiatanSekolah->kode)))) {
            if ($kegiatanSekolah && $kegiatanSekolah->arkasKegiatan) {
                $arkasKegiatan = "{$kegiatanSekolah->arkasKegiatan->kode_kegiatan} - {$kegiatanSekolah->arkasKegiatan->kegiatan}";
            } elseif ($kegiatanSekolah && $kegiatanSekolah->arkas_kegiatan_kode) {
                $arkasKegiatan = (string) $kegiatanSekolah->arkas_kegiatan_kode;
            } else {
                $arkasKegiatan = 'Kegiatan Pengadaan Sekolah';
            }
        } else {
            // Gunakan matcher ARKAS standar untuk $kegiatanRaw
            $arkasKegiatan = $this->matchKegiatan($kegiatanRaw);
        }

        return [$kegiatanSekolahId, $kegiatanSekolahNama, $arkasKegiatan];
    }

    /**
     * Pencocokan cerdas kegiatan terhadap referensi ARKAS.
     */
    private function matchKegiatan(string $kegiatanRaw): string
    {
        if ($kegiatanRaw === '') {
            return 'Kegiatan Pengadaan Sekolah';
        }

        // Jika diawali kode kegiatan (contoh: 02.02.01)
        if (preg_match('/^(\d{2}\.\d{2}\.\d{2})/', $kegiatanRaw, $m)) {
            $code = $m[1];
            $ref = ArkasKegiatan::where('kode_kegiatan', $code)->first();
            if ($ref) {
                return "{$ref->kode_kegiatan} - {$ref->kegiatan}";
            }
        }

        // Cek nama kegiatan di tabel referensi ARKAS
        $ref = ArkasKegiatan::whereRaw('LOWER(kegiatan) LIKE ?', ['%'.strtolower($kegiatanRaw).'%'])->first();
        if ($ref) {
            return "{$ref->kode_kegiatan} - {$ref->kegiatan}";
        }

        // Fallback: gunakan teks asli apa adanya
        return $kegiatanRaw;
    }
}

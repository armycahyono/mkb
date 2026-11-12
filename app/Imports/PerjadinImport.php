<?php

namespace App\Imports;

use App\Models\BosKertasKerjaItem;
use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\Instansi;
use App\Models\Perjadin;
use App\Models\PerjadinItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PerjadinImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithMultipleSheets
{
    private int $createdPerjadins = 0;

    private int $createdItems = 0;

    private int $skippedRows = 0;

    private array $warnings = [];

    private array $importedGroupsSummary = [];

    public function __construct(public string $fundingSource = 'BOSP', public ?int $bosKertasKerjaItemId = null) {}

    /**
     * @return array{perjadins: int, items: int, skipped: int, warnings: array, summary: array}
     */
    public function getResults(): array
    {
        return [
            'perjadins' => $this->createdPerjadins,
            'items' => $this->createdItems,
            'skipped' => $this->skippedRows,
            'warnings' => $this->warnings,
            'summary' => $this->importedGroupsSummary,
        ];
    }

    public function addPerjadinCount(int $count = 1): void
    {
        $this->createdPerjadins += $count;
    }

    public function addItemCount(int $count = 1): void
    {
        $this->createdItems += $count;
    }

    public function addSkipped(int $count = 1): void
    {
        $this->skippedRows += $count;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function addSummary(array $summary): void
    {
        $this->importedGroupsSummary[] = $summary;
    }

    /**
     * Hanya baca sheet pertama (Form Input Perjadin) dan abaikan sheet referensi lainnya
     */
    public function sheets(): array
    {
        return [
            0 => new PerjadinDataSheetImport($this),
        ];
    }

    public function collection(Collection $rows): void
    {
        $dataSheet = new PerjadinDataSheetImport($this);
        $dataSheet->collection($rows);
    }

    public static function cleanFormulaOrText(mixed $value, int $maxLength = 255): string
    {
        return PerjadinDataSheetImport::cleanFormulaOrText($value, $maxLength);
    }

    public static function calculateBkuRange(string $startBku, int $count, string $year): string
    {
        return PerjadinDataSheetImport::calculateBkuRange($startBku, $count, $year);
    }

    public static function parseExcelDate(mixed $rawDate): ?string
    {
        return PerjadinDataSheetImport::parseExcelDate($rawDate);
    }

    public static function cleanNumeric(mixed $value): float
    {
        return PerjadinDataSheetImport::cleanNumeric($value);
    }

    public static function findGuru(mixed $rawName, Collection $gurus): ?Guru
    {
        return PerjadinDataSheetImport::findGuru($rawName, $gurus);
    }

    public static function findInstansi(string $name, ?Collection $allInstansis = null): ?Instansi
    {
        return PerjadinDataSheetImport::findInstansi($name, $allInstansis);
    }
}

class PerjadinDataSheetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function __construct(public PerjadinImport $parent) {}

    public function collection(Collection $rows): void
    {
        $allGurus = Guru::with('primaryBankAccount')->where('is_active', true)->get();
        $allGurusFallback = Guru::with('primaryBankAccount')->get();

        $kkItem = null;
        if ($this->parent->bosKertasKerjaItemId) {
            $kkItem = BosKertasKerjaItem::find($this->parent->bosKertasKerjaItemId);
        }

        $groups = [];
        $rowIndex = 1; // Row 1 is header, data starts at row 2

        $lastDate = null;
        $lastLetterNumber = null;
        $lastDestination = null;
        $lastAddress = null;
        $lastPurpose = null;

        foreach ($rows as $row) {
            $rowIndex++;

            // 1. Ambil data mentah baris & bersihkan dari formula tak terevaluasi
            $rawDate = $row['tanggal_yyyy_mm_dd'] ?? $row['tanggal'] ?? $row['tgl'] ?? $row['date'] ?? null;
            $rawLetterNumber = self::cleanFormulaOrText($row['no_surat_opsional'] ?? $row['no_surat'] ?? $row['nomor_surat'] ?? $row['nomor_surat_tugas'] ?? $row['no_st'] ?? $row['surat_tugas'] ?? '', 255);
            $rawDestination = self::cleanFormulaOrText($row['tujuan_dinas'] ?? $row['tujuan'] ?? $row['nama_instansi'] ?? $row['instansi'] ?? '', 255);
            $rawAddress = self::cleanFormulaOrText($row['alamat_tujuan_opsional'] ?? $row['alamat_tujuan'] ?? $row['alamat'] ?? '', 500);
            $rawPurpose = self::cleanFormulaOrText($row['maksud_perjalanan'] ?? $row['maksud'] ?? $row['kegiatan'] ?? '', 255);
            $rawGuru = self::cleanFormulaOrText($row['nama_nip_guru'] ?? $row['nama_guru'] ?? $row['guru'] ?? $row['nip'] ?? '', 255);
            $rawNominal = $row['uang_diterima_rp'] ?? $row['uang_diterima'] ?? $row['nominal'] ?? $row['jumlah'] ?? 0;
            $rawBku = self::cleanFormulaOrText($row['no_bku_awal_opsional'] ?? $row['no_bku_awal'] ?? $row['no_bku'] ?? $row['bku'] ?? '', 100);
            $rawPpn = $row['ppn_opsional'] ?? $row['ppn'] ?? 0;
            $rawPph23 = $row['pph_2123_opsional'] ?? $row['pph23'] ?? $row['pph'] ?? $row['pph_21'] ?? 0;
            $rawNotes = self::cleanFormulaOrText($row['catatan_opsional'] ?? $row['catatan'] ?? $row['notes'] ?? '', 500);

            // Abaikan jika baris benar-benar kosong
            if (empty($rawDate) && $rawDestination === '' && $rawGuru === '') {
                $this->parent->addSkipped();

                continue;
            }

            // 2. Terapkan logika autofill dari baris sebelumnya (Merge Cells support)
            if (empty($rawDate)) {
                $rawDate = $lastDate;
            } else {
                $lastDate = $rawDate;
            }

            if (empty($rawLetterNumber)) {
                $rawLetterNumber = $lastLetterNumber;
            } else {
                $lastLetterNumber = $rawLetterNumber;
            }

            if ($rawDestination === '') {
                $rawDestination = $lastDestination;
            } else {
                $lastDestination = $rawDestination;
            }

            if ($rawAddress === '') {
                $rawAddress = $lastAddress;
            } else {
                $lastAddress = $rawAddress;
            }

            if ($rawPurpose === '') {
                $rawPurpose = $lastPurpose;
            } else {
                $lastPurpose = $rawPurpose;
            }

            // 3. Validasi & Parsing Tanggal
            $parsedDate = PerjadinImport::parseExcelDate($rawDate);
            if (! $parsedDate) {
                $this->parent->addWarning("Baris {$rowIndex}: Tanggal '{$rawDate}' tidak valid atau kosong. Baris dilewati.");
                $this->parent->addSkipped();

                continue;
            }

            // 4. Validasi Tujuan Dinas & Maksud
            if (empty($rawDestination)) {
                $this->parent->addWarning("Baris {$rowIndex}: Tujuan dinas kosong. Baris dilewati.");
                $this->parent->addSkipped();

                continue;
            }

            if (empty($rawPurpose)) {
                $rawPurpose = 'Perjalanan Dinas ke '.$rawDestination;
            }

            // 5. Cari Guru (Prioritaskan Guru Aktif, Fallback ke Semua Guru)
            $guru = PerjadinImport::findGuru($rawGuru, $allGurus) ?? PerjadinImport::findGuru($rawGuru, $allGurusFallback);
            if (! $guru) {
                $this->parent->addWarning("Baris {$rowIndex}: Guru '{$rawGuru}' tidak ditemukan dalam data guru aktif/terdaftar. Baris dilewati.");
                $this->parent->addSkipped();

                continue;
            }

            // 6. Parse Nominal Uang Diterima & Pajak
            $amount = PerjadinImport::cleanNumeric($rawNominal);
            if ($amount <= 0 && $kkItem && (float) $kkItem->price > 0) {
                $amount = (float) $kkItem->price;
            }
            $ppn = PerjadinImport::cleanNumeric($rawPpn);
            $pph23 = PerjadinImport::cleanNumeric($rawPph23);
            $netAmount = max(0, $amount - ($ppn + $pph23));

            // 7. Buat Kunci Pengelompokan (Group Key)
            // Mengelompokkan berdasarkan Tanggal + Tujuan Dinas + Maksud
            $groupKey = $parsedDate.'|'.mb_strtolower(trim($rawDestination)).'|'.mb_strtolower(trim($rawPurpose));

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'departure_date' => $parsedDate,
                    'destination_name' => $rawDestination,
                    'destination_address' => $rawAddress,
                    'activity_purpose' => $rawPurpose,
                    'letter_number' => $rawLetterNumber !== '' ? $rawLetterNumber : null,
                    'bku_initial' => $rawBku !== '' ? $rawBku : null,
                    'notes' => $rawNotes !== '' ? $rawNotes : null,
                    'items' => [],
                ];
            } else {
                if (empty($groups[$groupKey]['letter_number']) && $rawLetterNumber !== '') {
                    $groups[$groupKey]['letter_number'] = $rawLetterNumber;
                }
                if (empty($groups[$groupKey]['bku_initial']) && $rawBku !== '') {
                    $groups[$groupKey]['bku_initial'] = $rawBku;
                }
                if (empty($groups[$groupKey]['destination_address']) && $rawAddress !== '') {
                    $groups[$groupKey]['destination_address'] = $rawAddress;
                }
                if (empty($groups[$groupKey]['notes']) && $rawNotes !== '') {
                    $groups[$groupKey]['notes'] = $rawNotes;
                }
            }

            $groups[$groupKey]['items'][] = [
                'guru' => $guru,
                'amount' => $amount,
                'ppn' => $ppn,
                'pph23' => $pph23,
                'net_amount' => $netAmount,
                'notes' => $rawNotes,
            ];
        }

        if (empty($groups)) {
            return;
        }

        // 8. Simpan ke database dalam Transaction
        $allInstansis = Instansi::all();

        DB::transaction(function () use ($groups, $allInstansis, $kkItem) {
            foreach ($groups as $group) {
                $destName = trim(self::cleanFormulaOrText($group['destination_name'], 255));
                $destAddress = ! empty($group['destination_address']) ? trim(self::cleanFormulaOrText($group['destination_address'], 500)) : null;
                $depDate = $group['departure_date'];
                $itemCount = count($group['items']);

                // Auto create/find Instansi dengan pencocokan cerdas singkatan & nama
                $instansi = PerjadinDataSheetImport::findInstansi($destName, $allInstansis);

                if (! $instansi) {
                    $instansi = Instansi::create([
                        'name' => mb_substr($destName, 0, 255),
                        'category' => Instansi::detectCategory($destName),
                        'address' => $destAddress,
                        'city' => 'Kediri',
                    ]);
                    $allInstansis->push($instansi);
                }

                // Otomatis isi alamat tujuan dari data instansi jika di Excel kosong
                if (! empty($destAddress) && empty($instansi->address)) {
                    $instansi->update(['address' => $destAddress]);
                } elseif (empty($destAddress) && ! empty($instansi->address)) {
                    $destAddress = $instansi->address;
                }

                $city = ! empty($instansi->city) ? $instansi->city : 'Kediri';

                // Resolve Kepsek & Bendahara
                $kepsekJabatan = GuruJabatan::getPejabatByDate('kepala_sekolah', $depDate);
                $bendaharaJabatan = GuruJabatan::getPejabatByDate('bendahara_'.strtolower($this->parent->fundingSource), $depDate);

                $kepsekId = $kepsekJabatan ? $kepsekJabatan->guru_id : (Guru::where('is_active', true)->first()?->id);
                $bendaharaId = $bendaharaJabatan ? $bendaharaJabatan->guru_id : (Guru::where('is_active', true)->skip(1)->first()?->id ?? $kepsekId);

                // Format Range BKU jika ada
                $year = date('Y', strtotime($depDate));
                $bkuNumber = null;
                if (! empty($group['bku_initial'])) {
                    $bkuNumber = PerjadinImport::calculateBkuRange($group['bku_initial'], $itemCount, $year);
                }

                $defaultAccount = $this->parent->fundingSource === 'BOSP' ? '5.1.02.04.01.0003' : '5.1.02.04.01.0001';
                $accountCode = ($kkItem && ! empty($kkItem->account_code)) ? $kkItem->account_code : $defaultAccount;

                $perjadin = Perjadin::create([
                    'funding_source' => $this->parent->fundingSource,
                    'bos_kertas_kerja_item_id' => $kkItem?->id,
                    'bku_number' => $bkuNumber ? mb_substr($bkuNumber, 0, 100) : null,
                    'letter_number' => ! empty($group['letter_number']) ? mb_substr($group['letter_number'], 0, 255) : null,
                    'account_code' => $accountCode,
                    'activity_purpose' => mb_substr($group['activity_purpose'], 0, 65535),
                    'destination_name' => mb_substr($destName, 0, 255),
                    'destination_address' => $destAddress,
                    'instansi_id' => $instansi->id,
                    'departure_date' => $depDate,
                    'return_date' => $depDate,
                    'payment_date' => null,
                    'city' => mb_substr($city, 0, 100),
                    'kepala_sekolah_id' => $kepsekId,
                    'bendahara_id' => $bendaharaId,
                    'status' => 'draft',
                    'notes' => ! empty($group['notes']) ? mb_substr($group['notes'], 0, 65535) : 'Diimpor dari file Excel',
                ]);

                foreach ($group['items'] as $item) {
                    $guru = $item['guru'];
                    $bankAccount = $guru->primaryBankAccount;

                    $amount = (float) $item['amount'];
                    $ppn = (float) $item['ppn'];
                    $pph23 = (float) $item['pph23'];
                    if ($amount <= 0 && $kkItem && (float) $kkItem->price > 0) {
                        $amount = (float) $kkItem->price;
                    }
                    $netAmount = max(0, $amount - ($ppn + $pph23));

                    PerjadinItem::create([
                        'perjadin_id' => $perjadin->id,
                        'guru_id' => $guru->id,
                        'guru_bank_account_id' => $bankAccount ? $bankAccount->id : null,
                        'amount' => $amount,
                        'ppn' => $ppn,
                        'pph23' => $pph23,
                        'net_amount' => $netAmount,
                        'is_paid' => false,
                    ]);

                    $this->parent->addItemCount();
                }

                $this->parent->addPerjadinCount();

                $this->parent->addSummary([
                    'id' => $perjadin->id,
                    'destination' => $perjadin->destination_name,
                    'date' => $perjadin->departure_date->format('d/m/Y'),
                    'guru_count' => $itemCount,
                    'bku' => $perjadin->bku_number ?? '-',
                ]);
            }
        });
    }

    /**
     * Hitung rentang nomor BKU otomatis.
     * Contoh: BPU070 dengan count 5 -> BPU070 - BPU074 / 2026
    /**
     * Bersihkan input teks dari formula Excel / Google Sheets yang tidak terevaluasi,
     * ambil nilai fallback atau teks literalnya, dan pastikan tidak melebihi panjang kolom database.
     */
    public static function cleanFormulaOrText(mixed $value, int $maxLength = 255): string
    {
        if ($value === null) {
            return '';
        }

        $str = trim((string) $value);
        if ($str === '') {
            return '';
        }

        // Jika berupa formula Excel/Google Sheets (diawali tanda '=')
        if (str_starts_with($str, '=')) {
            // Pola 1: IFERROR(..., "Fallback Value") atau IF(..., ..., "Fallback Value")
            // Contoh: =IFERROR(__xludf.DUMMYFUNCTION(...),"Bengkel Mandala Auto")
            if (preg_match('/,\s*"((?:[^"]|"")*)"\s*\)+[\s;]*$/u', $str, $matches)) {
                $extracted = str_replace('""', '"', $matches[1]);

                return mb_substr(trim($extracted), 0, $maxLength);
            }

            // Pola 2: Fallback dengan petik tunggal: =IFERROR(..., 'Fallback Value')
            if (preg_match('/,\s*\'((?:[^\']|\'\')*)\'\s*\)+[\s;]*$/u', $str, $matches)) {
                $extracted = str_replace("''", "'", $matches[1]);

                return mb_substr(trim($extracted), 0, $maxLength);
            }

            // Pola 3: Formula string literal sederhana: ="Nama Teks" atau ='Nama Teks'
            if (preg_match('/^=\s*"((?:[^"]|"")*)"\s*$/u', $str, $matches)) {
                $extracted = str_replace('""', '"', $matches[1]);

                return mb_substr(trim($extracted), 0, $maxLength);
            }
            if (preg_match('/^=\s*\'((?:[^\']|\'\')*)\'\s*$/u', $str, $matches)) {
                $extracted = str_replace("''", "'", $matches[1]);

                return mb_substr(trim($extracted), 0, $maxLength);
            }

            // Pola 4: HYPERLINK("url", "Nama Tampilan")
            if (preg_match('/HYPERLINK\s*\([^,]+,\s*"((?:[^"]|"")*)"\s*\)/ui', $str, $matches)) {
                $extracted = str_replace('""', '"', $matches[1]);

                return mb_substr(trim($extracted), 0, $maxLength);
            }

            // Pola 5: Cari string literal terakhir yang ada di dalam tanda petik dua
            if (preg_match_all('/"((?:[^"]|"")*)"/u', $str, $allMatches)) {
                $candidates = array_filter($allMatches[1], function ($cand) {
                    $c = trim($cand);

                    return $c !== ''
                        && ! str_starts_with($c, 'http://')
                        && ! str_starts_with($c, 'https://')
                        && ! str_contains($c, '!')
                        && ! str_contains($c, 'IMPORTRANGE')
                        && ! str_contains($c, 'XLOOKUP');
                });

                if (! empty($candidates)) {
                    $lastCand = end($candidates);
                    $extracted = str_replace('""', '"', $lastCand);

                    return mb_substr(trim($extracted), 0, $maxLength);
                }
            }

            // Fallback: Jika tidak ada string literal yang cocok atau rumus tidak dapat diekstrak,
            // hapus tanda '=' di awal dan potong string agar tidak error SQL truncation
            $cleaned = ltrim($str, '=');

            return mb_substr(trim($cleaned), 0, $maxLength);
        }

        return mb_substr($str, 0, $maxLength);
    }

    /**
     * Hitung rentang nomor BKU berdasarkan nomor awal dan jumlah anggota rombongan.
     * Contoh: BPU070 dengan 5 orang -> "BPU070 - BPU074 / 2026"
     */
    public static function calculateBkuRange(string $bkuInput, int $itemCount, string $year): string
    {
        $raw = trim($bkuInput);

        // Jika sudah berupa range lengkap dengan slash tahun, kembalikan apa adanya
        if (str_contains($raw, '-') && str_contains($raw, '/')) {
            return $raw;
        }

        // Hapus suffix tahun jika sudah diketik user di belakang (misal "BPU070 / 2026")
        $mainBku = preg_replace('/\s*\/\s*\d{4}$/', '', $raw);

        // Jika hanya 1 orang
        if ($itemCount <= 1) {
            return $mainBku.' / '.$year;
        }

        // Cari pola prefix huruf + nomor (misal "BPU070")
        if (preg_match('/^([A-Za-z\s]*?)(\d+)$/', $mainBku, $m)) {
            $prefix = $m[1];
            $startStr = $m[2];
            $padLen = strlen($startStr);
            $startVal = (int) $startStr;
            $endVal = $startVal + $itemCount - 1;

            $startPadded = str_pad((string) $startVal, $padLen, '0', STR_PAD_LEFT);
            $endPadded = str_pad((string) $endVal, $padLen, '0', STR_PAD_LEFT);

            return $prefix.$startPadded.' - '.$prefix.$endPadded.' / '.$year;
        }

        return $mainBku.' / '.$year;
    }

    /**
     * Parsing tanggal Excel dengan dukungan serial date maupun string.
     */
    public static function parseExcelDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Jika string berupa formula Excel (diawali '=')
        if (is_string($value) && str_starts_with(trim($value), '=')) {
            $cleanedDate = self::cleanFormulaOrText($value);
            if ($cleanedDate !== '' && ! str_starts_with($cleanedDate, '=')) {
                $value = $cleanedDate;
            }
        }

        // Jika numeric serial date dari Excel (misal 45415)
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 65000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((float) $value);

                return $dateTime->format('Y-m-d');
            } catch (\Throwable $e) {
                // Lanjut ke string fallback
            }
        }

        $str = trim((string) $value);

        // Format YYYY-MM-DD
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // Format DD/MM/YYYY atau DD-MM-YYYY atau DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Format Teks Bulan Indonesia, misal: "03 Mei 2026", "3 Agustus 2026", "15-Nov-2026"
        $indoMonths = [
            'januari' => '01', 'jan' => '01',
            'februari' => '02', 'pebruari' => '02', 'feb' => '02', 'peb' => '02',
            'maret' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'mei' => '05', 'may' => '05',
            'juni' => '06', 'jun' => '06',
            'juli' => '07', 'jul' => '07',
            'agustus' => '08', 'agt' => '08', 'agu' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09',
            'oktober' => '10', 'okt' => '10', 'oct' => '10',
            'november' => '11', 'nopember' => '11', 'nov' => '11', 'nop' => '11',
            'desember' => '12', 'des' => '12', 'dec' => '12',
        ];

        if (preg_match('/^(\d{1,2})[\s\-]+([a-zA-Z]+)[\s\-]+(\d{4})$/', $str, $m)) {
            $monthKey = strtolower($m[2]);
            if (isset($indoMonths[$monthKey])) {
                return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $indoMonths[$monthKey], (int) $m[1]);
            }
        }

        $ts = strtotime($str);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Cari guru berdasarkan NIP, NIK, atau kecocokan nama.
     */
    public static function findGuru(string $rawInput, Collection $gurus): ?Guru
    {
        $input = trim($rawInput);
        if ($input === '') {
            return null;
        }

        // 1. Ekstrak deretan angka NIP atau NIK (8 s/d 18 digit)
        if (preg_match('/(\d{8,18})/', $input, $matches)) {
            $num = $matches[1];
            $found = $gurus->first(function ($g) use ($num) {
                $cleanNip = $g->nip ? preg_replace('/[^0-9]/', '', (string) $g->nip) : '';
                $cleanNik = $g->nik ? preg_replace('/[^0-9]/', '', (string) $g->nik) : '';

                return ($cleanNip && str_contains($cleanNip, $num))
                    || ($cleanNik && str_contains($cleanNik, $num));
            });
            if ($found) {
                return $found;
            }
        }

        // 2. Bersihkan input dari tanda kurung NIP/NIK
        $cleanName = trim(preg_replace('/[\(\[\-]?\s*\d{8,18}\s*[\)\]]?/', '', $input));
        $cleanLower = mb_strtolower($cleanName);

        // Exact match pada name atau full_name_with_title
        $found = $gurus->first(function ($g) use ($cleanLower) {
            return mb_strtolower(trim($g->name)) === $cleanLower
                || mb_strtolower(trim($g->full_name_with_title)) === $cleanLower;
        });
        if ($found) {
            return $found;
        }

        // Hilangkan gelar umum untuk pencocokan lebih fleksibel
        $stripTitles = function (string $name) {
            return preg_replace('/,?\s*(s\.pd\.?i?|m\.pd\.?|s\.kom\.?|s\.t\.?|st|s\.si\.?|s\.fil\.?|s\.pt\.?|a\.md\.?(?:t\.?)?|drs\.?|dra\.?|m\.m\.?|gr\.|s\.ag\.?|m\.ag\.?)\.?/i', '', $name);
        };

        $strippedInput = mb_strtolower(trim($stripTitles($cleanName)));
        if ($strippedInput !== '') {
            $found = $gurus->first(function ($g) use ($strippedInput, $stripTitles) {
                $strippedGName = mb_strtolower(trim($stripTitles($g->name)));
                $strippedGTitle = mb_strtolower(trim($stripTitles($g->full_name_with_title)));

                return $strippedGName === $strippedInput
                    || $strippedGTitle === $strippedInput
                    || str_contains($strippedGName, $strippedInput)
                    || str_contains($strippedInput, $strippedGName)
                    || str_contains($strippedGTitle, $strippedInput)
                    || str_contains($strippedInput, $strippedGTitle);
            });
            if ($found) {
                return $found;
            }
        }

        // 4. Fallback Substring
        $substringMatch = $gurus->first(function ($g) use ($cleanLower) {
            $gName = mb_strtolower(trim($g->name));
            $gTitle = mb_strtolower(trim($g->full_name_with_title));

            return str_contains($gName, $cleanLower)
                || str_contains($cleanLower, $gName)
                || str_contains($gTitle, $cleanLower)
                || str_contains($cleanLower, $gTitle);
        });
        if ($substringMatch) {
            return $substringMatch;
        }

        // 5. Fuzzy Matching (Toleransi Typo / Kesalahan Ketik Otomatis)
        $targetText = $strippedInput !== '' ? $strippedInput : $cleanLower;
        $targetLen = strlen($targetText);

        $bestGuru = null;
        $bestScore = 0.0;
        $minDistance = 999;

        foreach ($gurus as $g) {
            $candidates = [
                mb_strtolower(trim($stripTitles($g->name))),
                mb_strtolower(trim($stripTitles($g->full_name_with_title))),
            ];

            foreach ($candidates as $candidate) {
                if ($candidate === '') {
                    continue;
                }

                similar_text($targetText, $candidate, $percent);

                $dist = 999;
                if ($targetLen <= 255 && strlen($candidate) <= 255) {
                    $dist = levenshtein($targetText, $candidate);
                }

                if ($percent > $bestScore) {
                    $bestScore = $percent;
                    $minDistance = $dist;
                    $bestGuru = $g;
                }
            }
        }

        if ($bestGuru && ($bestScore >= 80.0 || ($minDistance <= 2 && $bestScore >= 75.0 && $targetLen >= 5))) {
            return $bestGuru;
        }

        return null;
    }

    /**
     * Bersihkan input string angka (contoh: "65.000", "Rp 65,000", "Rp. 65.000,-", 65000 -> 65000.0)
     */
    public static function cleanNumeric(mixed $value): float
    {
        if (empty($value)) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);

        // Jika string berupa formula Excel (diawali '=')
        if (str_starts_with($str, '=')) {
            if (preg_match('/,\s*([0-9\.,]+)\s*\)+[\s;]*$/u', $str, $m)) {
                $str = $m[1];
            } elseif (preg_match('/,\s*"([0-9\.,]+)"\s*\)+[\s;]*$/u', $str, $m)) {
                $str = $m[1];
            } elseif (preg_match('/^=\s*([0-9\.,]+)\s*$/u', $str, $m)) {
                $str = $m[1];
            } else {
                $str = self::cleanFormulaOrText($str);
            }
        }

        $str = preg_replace('/^rp\.?\s*/i', '', $str);
        $str = preg_replace('/,-\s*$/', '', $str);

        // Jika terdapat titik dan koma, format Indonesia misal: 1.250.000,50
        if (str_contains($str, ',') && str_contains($str, '.')) {
            $clean = str_replace('.', '', $str);
            $clean = str_replace(',', '.', $clean);

            return (float) preg_replace('/[^0-9\.]/', '', $clean);
        }

        // Format ribuan Indonesia dengan titik: "65.000" atau "1.500.000"
        if (preg_match('/^\D*\d{1,3}(\.\d{3})+(\D*)$/', $str)) {
            $clean = preg_replace('/[^0-9]/', '', $str);

            return (float) $clean;
        }

        // Format ribuan dengan koma: "65,000"
        if (preg_match('/^\D*\d{1,3}(,\d{3})+(\D*)$/', $str)) {
            $clean = preg_replace('/[^0-9]/', '', $str);

            return (float) $clean;
        }

        // Jika murni angka string atau desimal biasa
        if (is_numeric($str)) {
            return (float) $str;
        }

        $clean = preg_replace('/[^0-9]/', '', $str);

        return (float) ($clean ?: 0);
    }

    /**
     * Normalisasi nama instansi untuk memudahkan pencocokan (singkatan sekolah, kecamatan, dll.)
     */
    public static function normalizeInstansiName(string $name): string
    {
        $str = mb_strtoupper(trim($name));
        $str = preg_replace('/[.,\/\\\\]/', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);

        $replacements = [
            '/\bSMPN\b/' => 'SMP NEGERI',
            '/\bSMKN\b/' => 'SMK NEGERI',
            '/\bSMAN\b/' => 'SMA NEGERI',
            '/\bSDN\b/' => 'SD NEGERI',
            '/\bMTSN\b/' => 'MTS NEGERI',
            '/\bMAN\b/' => 'MA NEGERI',
            '/\bKEC\b/' => 'KECAMATAN',
            '/\bKAB\b/' => 'KABUPATEN',
        ];

        foreach ($replacements as $pattern => $replace) {
            $str = preg_replace($pattern, $replace, $str);
        }

        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * Cari instansi terdaftar berdasarkan nama atau singkatan
     */
    public static function findInstansi(string $name, ?Collection $allInstansis = null): ?Instansi
    {
        if (trim($name) === '') {
            return null;
        }

        if ($allInstansis === null) {
            $allInstansis = Instansi::all();
        }

        $trimmedLower = mb_strtolower(trim($name));

        // 1. Pencocokan Persis (Case-Insensitive)
        $exact = $allInstansis->first(fn ($i) => mb_strtolower(trim($i->name)) === $trimmedLower);
        if ($exact) {
            return $exact;
        }

        // 2. Pencocokan Singkatan Normal (misal: "SMPN 1 Kras" <-> "SMP Negeri 1 Kras")
        $normInput = self::normalizeInstansiName($name);
        $normMatch = $allInstansis->first(fn ($i) => self::normalizeInstansiName($i->name) === $normInput);
        if ($normMatch) {
            return $normMatch;
        }

        // 3. Pencocokan Substring (misal: "Kecamatan Ringinrejo" <-> "Kantor Kecamatan Ringinrejo")
        $subMatch = $allInstansis->first(function ($i) use ($normInput) {
            $normDb = self::normalizeInstansiName($i->name);

            return str_contains($normDb, $normInput) || str_contains($normInput, $normDb);
        });
        if ($subMatch) {
            return $subMatch;
        }

        // 4. Fuzzy Matching (Kemiripan Karakter >= 80%)
        $bestScore = 0;
        $bestInstansi = null;
        foreach ($allInstansis as $i) {
            $normDb = self::normalizeInstansiName($i->name);
            similar_text($normInput, $normDb, $percent);
            if ($percent > $bestScore && $percent >= 80.0) {
                $bestScore = $percent;
                $bestInstansi = $i;
            }
        }

        return $bestInstansi;
    }
}

<?php

namespace App\Services;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class BosKertasKerjaParserService
{
    public static function parsePdf(BosKertasKerjaFile $fileRecord, string $fullPdfPath): int
    {
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($fullPdfPath);
            $pages = $pdf->getPages();

            $currentKegiatan = null;
            $itemsToInsert = [];

            foreach ($pages as $page) {
                $textData = $page->getText();
                if (empty($textData)) {
                    continue;
                }

                $lines = explode("\n", $textData);

                // Menggabungkan baris yang terputus (multi-line description / item)
                $combinedLines = [];
                $buffer = '';
                foreach ($lines as $l) {
                    $l = trim($l);
                    if (empty($l)) {
                        continue;
                    }

                    // Hapus karakter bullet, middle dot, atau simbol pemisah aneh yang kadang terselip dari font PDF (misal: "box·")
                    $l = str_replace(["\xc2\xb7", '·', '•', '●', '▪', '–', '—'], [' ', ' ', ' ', ' ', ' ', '-', '-'], $l);

                    // Hapus footer halaman yang kadang menempel di akhir baris
                    $l = preg_replace('/\s*Halaman \d+ dari \d+Kertas Kerja.*$/u', '', $l);
                    $l = trim($l);
                    if (empty($l)) {
                        continue;
                    }

                    // Baris baru yang valid jika:
                    // 1) diawali angka urut item mandiri (misal "01.114.", "01.4.")
                    // 2) diawali angka urut item diikuti uraian teks (misal "07.106. BBM...", "01.111. Meterai...")
                    // 3) ATAU merupakan header kegiatan / standar (misal "1. Standar Proses", "112. Pembayaran...")
                    $isStandaloneItemNo = preg_match('/^\d{2}\.\d+\.\s*$/', $l);
                    $isItemWithText = preg_match('/^\d{2}\.\d+\.[\t\s]+[^\d\s]/', $l);
                    $isHeaderWithText = preg_match('/^\d{1,4}\.[\t\s]+[A-Za-z]/', $l);

                    $isItemStart = $isStandaloneItemNo || $isItemWithText;
                    $isNewHeader = (bool) $isHeaderWithText;

                    // Baris ekor numerik/data yang memuat kode rekening (misal "50.00012.07.5.1.02...", "5012.07.5.1.02...")
                    // atau angka berformat (misal "9.750.000", "42.000.000", "1.800.00036") BUKAN awal item baru
                    if (preg_match('/^[\d\.,\s]*5\.[12]\./', $l) || preg_match('/^\d{1,3}(\.\d{3})+[^.]/', $l)) {
                        $isItemStart = false;
                        $isNewHeader = false;
                    }

                    if (($isItemStart || $isNewHeader) && ! empty($buffer)) {
                        $combinedLines[] = $buffer;
                        $buffer = $l;
                    } else {
                        if (! empty($buffer)) {
                            $buffer .= ' '.$l;
                        } else {
                            $buffer = $l;
                        }
                    }
                }
                if (! empty($buffer)) {
                    $combinedLines[] = $buffer;
                }

                foreach ($combinedLines as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }

                    // 1. Cek apakah baris ini memiliki Kode Rekening Belanja (5.X.XX.XX.XX.XXXX) di bagian kanan
                    if (preg_match('/\b([5]\.[12]\.\d{2}\.\d{2}\.\d{2}\.\d{4})\b/', $line, $accMatches)) {
                        $accountCode = $accMatches[1];
                        $accPos = strpos($line, $accountCode);

                        // Ambil string sebelum Kode Rekening
                        $leftPart = trim(substr($line, 0, $accPos));

                        // 1. Ekstraksi kode program suffix tepat di sebelah kiri Kode Rekening (misal: "01." dari "...800.00001.03.5.1.02.01.01.0024" -> "01.03." disusul "01.")
                        $suffixProg = '';
                        if (preg_match('/(\d{2}\.\d{2}\.)$/', $leftPart, $progSuffixMatches2)) {
                            $suffixProg = $progSuffixMatches2[1];
                            $leftPart = trim(substr($leftPart, 0, -strlen($suffixProg)));
                        } elseif (preg_match('/(\d{2}\.)$/', $leftPart, $progSuffixMatches)) {
                            $suffixProg = $progSuffixMatches[1];
                            $leftPart = trim(substr($leftPart, 0, -strlen($suffixProg)));
                        }

                        // 2. Ekstraksi No Urut (ID ARKAS) & Kode Program Prefix dari depan string
                        // Contoh: "01.4." -> prefix = "01.", rawId = "4"
                        // "06.8." -> prefix = "06.", rawId = "8"
                        $arkasId = null;
                        $prefixProg = '';

                        if (preg_match('/^(\d{2}\.)(\d+)\.\s*/', $leftPart, $idMatches)) {
                            $prefixProg = $idMatches[1];
                            $arkasId = sprintf('%02d', (int) $idMatches[2]);
                            $leftPart = preg_replace('/^\d{2}\.\d+\.\s*/', '', $leftPart);
                        } elseif (preg_match('/^(\d+)\.\s*/', $leftPart, $idMatches2)) {
                            $arkasId = sprintf('%02d', (int) $idMatches2[1]);
                            $leftPart = preg_replace('/^\d+\.\s*/', '', $leftPart);
                        }

                        // Susun Kode Program LENGKAP:
                        // Jika suffix memuat 2 kelompok misal "01.03.", dan prefix = "01.", jadikan "01.03.01." (prefix urutan sub-kegiatan)
                        // Jika suffix memuat "01.03." dan prefix = "01.", gabungkan: "01.03.01."
                        // Jika suffix memuat "03." dan prefix = "06.", gabungkan "03.06." atau perbaiki urutan "03.06.01."
                        $programCode = null;
                        if (! empty($suffixProg) && ! empty($prefixProg)) {
                            if (strlen($suffixProg) == 6) { // "01.03."
                                $programCode = $suffixProg.trim($prefixProg, '.').'.';
                            } else { // suffixProg "03."
                                $programCode = trim($suffixProg, '.').'.'.$prefixProg;
                            }
                        } elseif (! empty($suffixProg)) {
                            $programCode = $suffixProg;
                        } elseif (! empty($prefixProg)) {
                            $programCode = $prefixProg;
                        }

                        $leftPart = trim($leftPart);

                        // Pemisahan Uraian | Jumlah | Volume + Satuan | Tarif Harga
                        // Format yang ditemukan di output PDF:
                        // A) 3+ tab: {description}\t{total}{volume}{unit}\t{price}
                        // B) 2 tab, uraian+data di part0: {desc} {total}{volume}{unit}\t{price}
                        // C) 2 tab, data lengkap di part1: {description}\t{total}{volume}{unit} {price}
                        // D) 1 tab/tanpa tab: semua data dalam 1 string
                        $description = $leftPart;
                        $volume = 1;
                        $unit = 'pcs';
                        $price = 0;
                        $totalAmount = 0;

                        $tabs = explode("\t", $leftPart);

                        // Pola satuan: WAJIB memuat minimal satu huruf (\p{L}) agar tidak salah mencocokkan angka seperti "65.000" atau "000"
                        $unitPattern = '[\p{L}\p{N}\/\s,\.·•\-]*\p{L}[\p{L}\p{N}\/\s,\.·•\-]*';

                        // Regex untuk memisah jumlah+volume+satuan: "4.800.0006rim", "150oh", "12m2", "1buah"
                        // Grup: (1)jumlah_formatted (2)volume_digits (3)unit_text
                        $midRegex = '/^([\d]{1,3}(?:\.[\d]{3})*)(\d+)\s*('.$unitPattern.')$/u';
                        // Regex untuk jumlah+volume+satuan diikuti spasi+harga: "1.800.0001buah 1.800.000"
                        $midPriceRegex = '/^([\d]{1,3}(?:\.[\d]{3})*)(\d+)\s*('.$unitPattern.'?)\s+([\d]{1,3}(?:\.[\d]{3})*)$/u';
                        // Regex untuk part0 yang memuat uraian disusul jumlah+volume+satuan: "Uang Transport... 7.020.000108oh"
                        $part0Regex = '/^(.*?)\s+([\d]{1,3}(?:\.[\d]{3})+)(\d+)\s*('.$unitPattern.')$/u';

                        if (count($tabs) >= 3) {
                            $description = trim($tabs[0]);
                            $mid = trim($tabs[1]);
                            $rightStr = trim($tabs[2]);

                            $price = parse_number($rightStr);

                            if (preg_match($midRegex, $mid, $sub)) {
                                $totalAmount = parse_number($sub[1]);
                                $volume = (int) $sub[2];
                                $unit = trim(preg_replace('/[^\p{L}\p{N}\/\s,-]/u', '', $sub[3]));
                            } else {
                                $totalAmount = parse_number($mid);
                            }
                        } elseif (count($tabs) == 2) {
                            $part0 = trim($tabs[0]);
                            $part1 = trim($tabs[1]);

                            // Cek apakah part1 berisi {total}{volume}{unit} {price}
                            // misal: "1.800.0001buah 1.800.000" atau "405.00090per bungkus 4.500"
                            if (preg_match($midPriceRegex, $part1, $mpMatch)) {
                                $description = $part0;
                                $totalAmount = parse_number($mpMatch[1]);
                                $volume = (int) $mpMatch[2];
                                $unit = trim(preg_replace('/[^\p{L}\p{N}\/\s,-]/u', '', $mpMatch[3]));
                                $price = parse_number($mpMatch[4]);
                            }
                            // Cek apakah part0 berisi {desc} {total}{volume}{unit} dan part1 = harga
                            // misal: "Uang Transport... 7.020.000108oh" TAB "65.000"
                            elseif (preg_match($part0Regex, $part0, $mSplit)) {
                                $description = trim($mSplit[1]);
                                $totalAmount = parse_number($mSplit[2]);
                                $volume = (int) $mSplit[3];
                                $unit = trim(preg_replace('/[^\p{L}\p{N}\/\s,-]/u', '', $mSplit[4]));
                                $price = parse_number($part1);
                            }
                            // Cek apakah part1 hanya {total}{volume}{unit} tanpa harga (unit WAJIB memuat huruf)
                            elseif (preg_match($midRegex, $part1, $midMatch)) {
                                $description = $part0;
                                $totalAmount = parse_number($midMatch[1]);
                                $volume = (int) $midMatch[2];
                                $unit = trim(preg_replace('/[^\p{L}\p{N}\/\s,-]/u', '', $midMatch[3]));
                            }
                            // Fallback: part1 = harga saja, part0 = uraian
                            else {
                                $description = $part0;
                                $price = parse_number($part1);
                            }
                        } else {
                            // Tanpa tab: coba parse semua dari 1 string
                            if (preg_match('/^(.*?)\s+([\d]{1,3}(?:\.[\d]{3})+)(\d+)\s*('.$unitPattern.'?)\s+([\d]{1,3}(?:\.[\d]{3})*)$/u', $leftPart, $m3)) {
                                $description = trim($m3[1]);
                                $totalAmount = parse_number($m3[2]);
                                $volume = (int) $m3[3];
                                $unit = trim(preg_replace('/[^\p{L}\p{N}\/\s,-]/u', '', $m3[4]));
                                $price = parse_number($m3[5]);
                            }
                        }

                        // Verifikasi & Koreksi Otomatis Angka Bulat
                        if ($price > 0 && $volume > 0) {
                            $totalAmount = $price * $volume;
                        } elseif ($totalAmount > 0 && $volume > 0 && $price == 0) {
                            $price = (int) round($totalAmount / $volume);
                        }

                        $itemsToInsert[] = [
                            'bos_kertas_kerja_file_id' => $fileRecord->id,
                            'arkas_id' => $arkasId,
                            'year' => $fileRecord->year,
                            'month' => $fileRecord->month,
                            'account_code' => $accountCode,
                            'program_code' => $programCode,
                            'kegiatan' => $currentKegiatan ?? 'Kegiatan Pengadaan Sekolah',
                            'description' => $description,
                            'volume' => $volume,
                            'unit' => $unit,
                            'price' => $price,
                            'total_amount' => $totalAmount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    // 2. Baris Header Kegiatan (Background Kuning)
                    elseif (preg_match('/^\d*(?:\.\d+)*\.\s+([A-Za-z\s\/,()\-]+)/', $line, $kegMatches)) {
                        $rawKeg = trim($kegMatches[1]);
                        if (! empty($rawKeg) && ! in_empty_header($rawKeg)) {
                            // Buang angka jumlah di belakang
                            $rawKeg = preg_replace('/\s+[\d\.,]+$/', '', $rawKeg);
                            $rawKeg = preg_replace('/\s+\d{2}\.\d{2}\.\s*$/', '', $rawKeg);
                            $rawKeg = trim($rawKeg);
                            // Jangan jadikan kegiatan jika berupa nama penerima/terputus di kurung buka: misal "ILHAM BUDI SANTOSO ("
                            if (strlen($rawKeg) > 3 && ! str_ends_with($rawKeg, '(') && ! preg_match('/\([0-9]{4,}/', $line)) {
                                $currentKegiatan = $rawKeg;
                            }
                        }
                    }
                }
            }

            if (! empty($itemsToInsert)) {
                $fileRecord->items()->forceDelete();
                BosKertasKerjaItem::insert($itemsToInsert);
            }

            return count($itemsToInsert);
        } catch (\Throwable $e) {
            Log::error('Error parsing PDF BosKertasKerja: '.$e->getMessage()."\n".$e->getTraceAsString());

            return 0;
        }
    }

    /**
     * Compare two Kertas Kerja files (e.g. V1 vs V2) and return structured differences.
     */
    public static function compareFiles(BosKertasKerjaFile $oldFile, BosKertasKerjaFile $newFile): array
    {
        $oldItems = BosKertasKerjaItem::where('bos_kertas_kerja_file_id', $oldFile->id)
            ->with('realisasis')
            ->get();
        $newItems = BosKertasKerjaItem::where('bos_kertas_kerja_file_id', $newFile->id)
            ->with('realisasis')
            ->get();

        $makeKey = function ($item) {
            return trim((string) $item->account_code).'|'.strtolower(trim((string) $item->kegiatan)).'|'.strtolower(trim((string) $item->description));
        };

        $oldMap = [];
        foreach ($oldItems as $it) {
            $oldMap[$makeKey($it)] = $it;
        }

        $newMap = [];
        foreach ($newItems as $it) {
            $newMap[$makeKey($it)] = $it;
        }

        $modified = [];
        $removed = [];
        $added = [];
        $unchangedCount = 0;
        $realisasiWarnings = [];

        // 1. Check items in oldFile
        foreach ($oldMap as $key => $oldIt) {
            if (isset($newMap[$key])) {
                $newIt = $newMap[$key];
                $volDiff = (float) $newIt->volume - (float) $oldIt->volume;
                $priceDiff = (float) $newIt->price - (float) $oldIt->price;
                $totalDiff = (float) $newIt->total_amount - (float) $oldIt->total_amount;

                if (abs($volDiff) > 0.001 || abs($priceDiff) > 0.001 || abs($totalDiff) > 0.001) {
                    $modEntry = [
                        'account_code' => $oldIt->account_code,
                        'kegiatan' => $oldIt->kegiatan,
                        'description' => $oldIt->description,
                        'unit' => $oldIt->unit ?: $newIt->unit,
                        'old_volume' => (float) $oldIt->volume,
                        'new_volume' => (float) $newIt->volume,
                        'diff_volume' => $volDiff,
                        'old_price' => (float) $oldIt->price,
                        'new_price' => (float) $newIt->price,
                        'diff_price' => $priceDiff,
                        'old_total' => (float) $oldIt->total_amount,
                        'new_total' => (float) $newIt->total_amount,
                        'diff_total' => $totalDiff,
                    ];
                    $modified[] = $modEntry;

                    // Warning check: if volume reduced below already realized volume
                    $realizedVol = (float) $oldIt->realisasis->sum('volume');
                    if ($volDiff < 0 && $realizedVol > (float) $newIt->volume) {
                        $realisasiWarnings[] = "Item '{$oldIt->description}' dipangkas menjadi {$newIt->volume} {$newIt->unit}, padahal realisasi transaksi sudah tercatat {$realizedVol} {$oldIt->unit}.";
                    }
                } else {
                    $unchangedCount++;
                }
            } else {
                // Removed in newFile
                $removed[] = [
                    'account_code' => $oldIt->account_code,
                    'kegiatan' => $oldIt->kegiatan,
                    'description' => $oldIt->description,
                    'volume' => (float) $oldIt->volume,
                    'unit' => $oldIt->unit,
                    'price' => (float) $oldIt->price,
                    'total_amount' => (float) $oldIt->total_amount,
                ];

                $realizedVol = (float) $oldIt->realisasis->sum('volume');
                if ($realizedVol > 0) {
                    $realisasiWarnings[] = "Item '{$oldIt->description}' dihapus pada revisi ini, padahal sudah memiliki realisasi transaksi sebanyak {$realizedVol} {$oldIt->unit}.";
                }
            }
        }

        // 2. Check items added in newFile
        foreach ($newMap as $key => $newIt) {
            if (! isset($oldMap[$key])) {
                $added[] = [
                    'account_code' => $newIt->account_code,
                    'kegiatan' => $newIt->kegiatan,
                    'description' => $newIt->description,
                    'volume' => (float) $newIt->volume,
                    'unit' => $newIt->unit,
                    'price' => (float) $newIt->price,
                    'total_amount' => (float) $newIt->total_amount,
                ];
            }
        }

        $oldPagu = (float) $oldItems->sum('total_amount');
        $newPagu = (float) $newItems->sum('total_amount');

        return [
            'old_file' => [
                'id' => $oldFile->id,
                'version' => $oldFile->version,
                'filename' => $oldFile->filename,
                'year' => $oldFile->year,
                'month' => $oldFile->month,
                'total_items' => $oldItems->count(),
                'total_pagu' => $oldPagu,
                'total_pagu_display' => 'Rp '.number_format($oldPagu, 0, ',', '.'),
                'is_active' => (bool) $oldFile->is_active,
            ],
            'new_file' => [
                'id' => $newFile->id,
                'version' => $newFile->version,
                'filename' => $newFile->filename,
                'year' => $newFile->year,
                'month' => $newFile->month,
                'total_items' => $newItems->count(),
                'total_pagu' => $newPagu,
                'total_pagu_display' => 'Rp '.number_format($newPagu, 0, ',', '.'),
                'is_active' => (bool) $newFile->is_active,
            ],
            'summary' => [
                'old_pagu' => $oldPagu,
                'new_pagu' => $newPagu,
                'diff_pagu' => $newPagu - $oldPagu,
                'diff_pagu_display' => ($newPagu - $oldPagu >= 0 ? '+Rp ' : '-Rp ').number_format(abs($newPagu - $oldPagu), 0, ',', '.'),
                'modified_count' => count($modified),
                'removed_count' => count($removed),
                'added_count' => count($added),
                'unchanged_count' => $unchangedCount,
            ],
            'modified' => $modified,
            'removed' => $removed,
            'added' => $added,
            'realisasi_warnings' => $realisasiWarnings,
        ];
    }
}

if (! function_exists('parse_number')) {
    function parse_number($str)
    {
        // Ambil hanya digit angka
        $digits = preg_replace('/[^\d]/', '', $str);
        if (empty($digits)) {
            return 0;
        }

        return (int) $digits;
    }
}

if (! function_exists('in_empty_header')) {
    function in_empty_header($text)
    {
        $ignored = ['No', 'Urut', 'Uraian', 'Jumlah', 'Volume', 'Satuan', 'Tarif Harga', 'Rincian Perhitungan', 'Kode Rekening', 'Kode Program', 'Standar', 'PENERIMAAN', 'BELANJA', 'Halaman', 'Kertas Kerja'];
        foreach ($ignored as $ig) {
            if (strcasecmp(trim($text), $ig) === 0) {
                return true;
            }
        }

        return false;
    }
}

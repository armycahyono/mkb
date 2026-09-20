<?php

namespace App\Imports;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BosKertasKerjaImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function getCounts(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
        ];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $description = trim((string) ($row['uraian_belanja'] ?? $row['uraian'] ?? $row['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $idSistem = $row['id_sistem'] ?? $row['id'] ?? null;
            $arkasId = ! empty($row['id_arkas']) ? trim((string) $row['id_arkas']) : (! empty($row['arkas_id']) ? trim((string) $row['arkas_id']) : null);
            $year = ! empty($row['tahun']) ? (int) $row['tahun'] : (! empty($row['year']) ? (int) $row['year'] : (int) date('Y'));
            $month = ! empty($row['bulan']) ? trim((string) $row['bulan']) : (! empty($row['month']) ? trim((string) $row['month']) : 'Januari');
            $accountCode = trim((string) ($row['kode_rekening'] ?? $row['account_code'] ?? ''));
            $programCode = trim((string) ($row['kode_program'] ?? $row['program_code'] ?? ''));
            $kegiatan = trim((string) ($row['kegiatan'] ?? 'Kegiatan Pengadaan Sekolah'));

            // Clean volume
            $rawVol = (string) ($row['volume'] ?? 1);
            $volume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $rawVol));
            if ($volume <= 0) {
                $volume = 1;
            }

            $unit = trim((string) ($row['satuan'] ?? $row['unit'] ?? 'Pcs'));

            // Clean price
            $rawPrice = (string) ($row['harga_satuan'] ?? $row['harga'] ?? $row['price'] ?? 0);
            $price = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $rawPrice)));

            // Clean total
            $rawTotal = (string) ($row['total_anggaran'] ?? $row['total_amount'] ?? 0);
            $totalAmount = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $rawTotal)));
            if ($totalAmount <= 0 && $price > 0 && $volume > 0) {
                $totalAmount = $price * $volume;
            } elseif ($price <= 0 && $totalAmount > 0 && $volume > 0) {
                $price = round($totalAmount / $volume);
            }

            // Find existing item
            $existingItem = null;
            if ($idSistem) {
                $existingItem = BosKertasKerjaItem::find($idSistem);
            }

            if (! $existingItem && $arkasId) {
                $existingItem = BosKertasKerjaItem::where('year', $year)
                    ->where('month', $month)
                    ->where('arkas_id', $arkasId)
                    ->first();
            }

            if (! $existingItem) {
                $existingItem = BosKertasKerjaItem::where('year', $year)
                    ->where('month', $month)
                    ->where('account_code', $accountCode)
                    ->where('description', $description)
                    ->first();
            }

            if ($existingItem) {
                // If item is locked by realisasi, skip it
                if ($existingItem->is_locked) {
                    $this->skipped++;

                    continue;
                }

                $existingItem->update([
                    'arkas_id' => $arkasId ?? $existingItem->arkas_id,
                    'account_code' => $accountCode !== '' ? $accountCode : $existingItem->account_code,
                    'program_code' => $programCode !== '' ? $programCode : $existingItem->program_code,
                    'kegiatan' => $kegiatan !== '' ? $kegiatan : $existingItem->kegiatan,
                    'description' => $description,
                    'volume' => $volume,
                    'unit' => $unit !== '' ? $unit : $existingItem->unit,
                    'price' => $price,
                    'total_amount' => $totalAmount,
                ]);

                $this->updated++;
            } else {
                // Find or create file record
                $file = BosKertasKerjaFile::where('year', $year)
                    ->where('month', $month)
                    ->where('is_active', true)
                    ->first();

                if (! $file) {
                    $file = BosKertasKerjaFile::where('year', $year)
                        ->where('month', $month)
                        ->latest('id')
                        ->first();
                }

                if (! $file) {
                    $file = BosKertasKerjaFile::create([
                        'year' => $year,
                        'month' => $month,
                        'filename' => "Import_Excel_{$year}_{$month}.xlsx",
                        'file_path' => 'bos/kertas_kerja/import.xlsx',
                        'version' => 'V1',
                        'description' => 'Dibuat otomatis dari Import Excel',
                        'is_active' => true,
                        'uploaded_by' => auth()->id() ?? 1,
                    ]);
                }

                BosKertasKerjaItem::create([
                    'bos_kertas_kerja_file_id' => $file->id,
                    'arkas_id' => $arkasId,
                    'year' => $year,
                    'month' => $month,
                    'account_code' => $accountCode,
                    'program_code' => $programCode ?: null,
                    'kegiatan' => $kegiatan,
                    'description' => $description,
                    'volume' => $volume,
                    'unit' => $unit,
                    'price' => $price,
                    'total_amount' => $totalAmount,
                ]);

                $this->created++;
            }
        }
    }
}

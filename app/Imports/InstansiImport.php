<?php

namespace App\Imports;

use App\Models\Instansi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InstansiImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
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
            $name = trim((string) ($row['nama_instansi'] ?? $row['nama'] ?? $row['name'] ?? $row['instansi'] ?? ''));
            if ($name === '') {
                $this->skipped++;

                continue;
            }

            // Normalisasi kategori
            $rawCat = strtolower(trim((string) ($row['kategori'] ?? $row['category'] ?? '')));
            $category = $this->resolveCategory($rawCat, $name);

            $address = trim((string) ($row['alamat'] ?? $row['address'] ?? '')) ?: null;
            $city = trim((string) ($row['kota'] ?? $row['city'] ?? '')) ?: 'Kediri';
            $phone = trim((string) ($row['no_telepon'] ?? $row['telepon'] ?? $row['phone'] ?? $row['telp'] ?? $row['no_hp'] ?? '')) ?: null;
            $picName = trim((string) ($row['nama_pic_kontak'] ?? $row['pic'] ?? $row['pic_name'] ?? $row['kontak'] ?? '')) ?: null;
            $notes = trim((string) ($row['catatan'] ?? $row['notes'] ?? $row['keterangan'] ?? '')) ?: null;

            // Cari instansi yang sudah ada (termasuk yang di-soft delete)
            $existing = Instansi::withTrashed()->where('name', $name)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $updates = [];
                if ($address && empty($existing->address)) {
                    $updates['address'] = $address;
                }
                if ($phone && empty($existing->phone)) {
                    $updates['phone'] = $phone;
                }
                if ($picName && empty($existing->pic_name)) {
                    $updates['pic_name'] = $picName;
                }
                if ($notes && empty($existing->notes)) {
                    $updates['notes'] = $notes;
                }
                if ($category !== 'lainnya' && $existing->category === 'lainnya') {
                    $updates['category'] = $category;
                }

                if (! empty($updates)) {
                    $existing->update($updates);
                    $this->updated++;
                } else {
                    $this->skipped++;
                }
            } else {
                Instansi::create([
                    'name' => $name,
                    'category' => $category,
                    'address' => $address,
                    'city' => $city,
                    'phone' => $phone,
                    'pic_name' => $picName,
                    'notes' => $notes,
                ]);
                $this->created++;
            }
        }
    }

    public function resolveCategory(string $rawCat, string $name): string
    {
        if (str_contains($rawCat, 'sekolah') || str_contains($rawCat, 'pendidikan') || str_contains($rawCat, 'school')) {
            return 'sekolah';
        }
        if (str_contains($rawCat, 'dudi') || str_contains($rawCat, 'industri') || str_contains($rawCat, 'perusahaan') || str_contains($rawCat, 'pt') || str_contains($rawCat, 'cv')) {
            return 'dudi';
        }
        if (str_contains($rawCat, 'dinas') || str_contains($rawCat, 'pemerintah') || str_contains($rawCat, 'kantor')) {
            return 'dinas';
        }
        if (str_contains($rawCat, 'lain')) {
            return 'lainnya';
        }

        // Auto detect dari nama jika kosong atau tidak sesuai
        return Instansi::detectCategory($name);
    }
}

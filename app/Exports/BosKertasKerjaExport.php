<?php

namespace App\Exports;

use App\Models\BosKertasKerjaItem;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BosKertasKerjaExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $rowNumber = 0;

    public function __construct(
        public ?string $year = null,
        public ?string $month = null,
        public ?string $versionFilter = null,
        public ?string $kegiatan = null
    ) {}

    public function query(): Builder|EloquentBuilder|Relation
    {
        $query = BosKertasKerjaItem::query()->with('file');

        if ($this->year) {
            $query->where('year', $this->year);
        }

        if ($this->month) {
            $query->where('month', $this->month);
        }

        if ($this->kegiatan) {
            $query->where('kegiatan', 'like', '%'.$this->kegiatan.'%');
        }

        if ($this->versionFilter !== 'all') {
            if (is_numeric($this->versionFilter)) {
                $query->where('bos_kertas_kerja_file_id', $this->versionFilter);
            } elseif ($this->versionFilter === 'archived') {
                $query->whereHas('file', fn ($f) => $f->where('is_active', false));
            } else {
                // Default: aktif
                $query->whereHas('file', fn ($f) => $f->where('is_active', true));
            }
        }

        return $query->orderBy('year', 'desc')
            ->orderByRaw("FIELD(month, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') ASC")
            ->orderBy('id', 'asc');
    }

    public function headings(): array
    {
        return [
            'NO',
            'ID ARKAS',
            'TAHUN',
            'BULAN',
            'KODE REKENING',
            'KODE PROGRAM',
            'KEGIATAN',
            'URAIAN BELANJA',
            'VOLUME',
            'SATUAN',
            'HARGA SATUAN',
            'TOTAL ANGGARAN',
            'VERSI',
            'STATUS VERSI',
            'ID SISTEM',
        ];
    }

    /**
     * @param  BosKertasKerjaItem  $row
     */
    public function map(mixed $row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->arkas_id ?? $row->id,
            $row->year,
            $row->month,
            $row->account_code,
            $row->program_code ?? '-',
            $row->kegiatan,
            $row->description,
            (float) $row->volume,
            $row->unit,
            (float) $row->price,
            (float) $row->total_amount,
            $row->file ? $row->file->version : '-',
            $row->file ? ($row->file->is_active ? 'Aktif' : 'Arsip') : '-',
            $row->id,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E78'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => '#,##0',
            'I' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
        ];
    }

    public function title(): string
    {
        $title = 'Kertas Kerja BOS';
        if ($this->year) {
            $title .= ' '.$this->year;
        }
        if ($this->month) {
            $title .= ' '.$this->month;
        }

        return mb_substr($title, 0, 31);
    }
}

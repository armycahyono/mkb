<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstansiTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function headings(): array
    {
        return [
            'Nama Instansi',
            'Kategori',
            'Alamat',
            'Kota',
            'No Telepon',
            'Nama PIC / Kontak',
            'Catatan',
        ];
    }

    public function array(): array
    {
        return [
            [
                'SMPN 1 RINGINREJO',
                'sekolah',
                'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo',
                'Kediri',
                '081234567890',
                'Bpk. Supriyadi',
                'Mitra sosialisasi PPDB',
            ],
            [
                'PT. TELKOM INDONESIA KEDIRI',
                'dudi',
                'Jl. Hayam Wuruk No. 10',
                'Kediri',
                '(0354) 681234',
                'Ibu Rina (HRD)',
                'DUDI mitra PKL siswa',
            ],
            [
                'DINAS PENDIDIKAN KABUPATEN KEDIRI',
                'dinas',
                'Jl. Soekarno Hatta No. 1, Doko, Kec. Ngasem',
                'Kediri',
                '(0354) 682222',
                'Bagian Pembinaan SMK',
                'Instansi pembina',
            ],
            [
                'HOTEL GRAND SURYA',
                'dudi',
                'Jl. Dhoho No. 95',
                'Kediri',
                '(0354) 686000',
                'Front Office Manager',
                'Tempat kegiatan workshop',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Template Instansi';
    }
}

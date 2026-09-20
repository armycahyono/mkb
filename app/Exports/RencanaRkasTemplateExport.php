<?php

namespace App\Exports;

use App\Models\Guru;
use App\Models\KegiatanSekolah;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RencanaRkasTemplateExport implements Export, WithMultipleSheets
{
    public function __construct(public int $year = 2026) {}

    public function sheets(): array
    {
        return [
            // Sheet 1: Template Utama Rencana RKAS
            new class($this->year) implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
            {
                public function __construct(private int $year) {}

                public function title(): string
                {
                    return 'Rencana RKAS';
                }

                public function headings(): array
                {
                    return [
                        'No',
                        'Kegiatan',
                        'Bulan',
                        'Nama Barang',
                        'Spesifikasi',
                        'Volume',
                        'Satuan',
                        'Harga',
                        'Total',
                        'Pemohon',
                        'Keterangan',
                    ];
                }

                public function array(): array
                {
                    return [
                        [
                            1,
                            'Pramuka',
                            'Januari',
                            'Kertas HVS F4 75gr',
                            'Merk Sidu / SiDU 75 GSM',
                            10,
                            'Rim',
                            60000,
                            '=F2*H2',
                            'Army',
                            'Cetak administrasi regu dan materi kepramukaan',
                        ],
                        [
                            2,
                            'OSIS',
                            'Februari',
                            'Spidol Boardmarker Hitam',
                            'Snowman Boardmarker WB-820',
                            12,
                            'Buah',
                            10500,
                            '=F3*H3',
                            'Army',
                            'Kegiatan LDKS dan rapat kerja OSIS',
                        ],
                        [
                            3,
                            'TU',
                            'Maret',
                            'Map Folio Kertas Snelhecter',
                            'Isi 50 Lembar / Pack warna merah',
                            4,
                            'Pak',
                            45000,
                            '=F4*H4',
                            'Endang',
                            'Arsip surat dinas dan kepegawaian',
                        ],
                        [
                            4,
                            '06.07.01 - Pemeliharaan Sarana dan Prasarana Sekolah',
                            'April',
                            'Cat Tembok Putih Interior',
                            'Ukuran Pail 25 Kg',
                            2,
                            'Pail',
                            650000,
                            '=F5*H5',
                            'Sekolah',
                            'Pengecatan ruang kelas dan lab',
                        ],
                    ];
                }

                public function styles(Worksheet $sheet): array
                {
                    // Alignment kolom
                    $sheet->getStyle('A2:A200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C2:C200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F2:F200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('G2:G200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H2:I200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Number Format
                    $sheet->getStyle('F2:F200')->getNumberFormat()->setFormatCode('#,##0.##');
                    $sheet->getStyle('H2:I200')->getNumberFormat()->setFormatCode('#,##0');

                    return [
                        1 => [
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                                'size' => 11,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF1E293B'], // Dark slate
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ],
                    ];
                }
            },

            // Sheet 2: Petunjuk & Daftar Guru
            new class implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
            {
                public function title(): string
                {
                    return 'Petunjuk & Guru';
                }

                public function headings(): array
                {
                    return ['No', 'Nama Guru Terdaftar', 'NIP', 'Petunjuk Pengisian'];
                }

                public function array(): array
                {
                    $gurus = Guru::where('is_active', true)->orderBy('name')->get();
                    $rows = [];
                    $i = 1;

                    $guidance = [
                        '1. Kolom No: Diisi angka urut (1, 2, 3, dst).',
                        '2. Kolom Kegiatan: Anda bisa menuliskan nama Program Sekolah (contoh: Pramuka, OSIS, PMR, TU, PKL, UKS, Sarpras) dan sistem otomatis memetakan ke kode ARKAS! Atau bisa juga kode ARKAS langsung (contoh: 03.03.06).',
                        '3. Kolom Bulan: Diisi nama bulan (Januari s/d Desember). Gunakan 1 baris per bulan.',
                        '4. Kolom Nama Barang: Disarankan mengacu ke katalog ARKAS agar otomatis melengkapi rekening belanja.',
                        '5. Kolom Total: Berisi rumus Excel (=Volume x Harga). Dihitung otomatis oleh Excel.',
                        '6. Kolom Pemohon: Cukup tulis kata kunci/panggilan guru (contoh: "Army" otomatis cocok ke "ARMY WIDHI CAHYONO").',
                        '7. Untuk pemohon umum sekolah, Anda dapat menuliskan "Sekolah" atau "Bendahara".',
                    ];

                    foreach ($gurus as $g) {
                        $guideNote = $guidance[$i - 1] ?? '';
                        $rows[] = [
                            $i++,
                            $g->name,
                            $g->nip ?: '-',
                            $guideNote,
                        ];
                    }

                    if (empty($rows)) {
                        $rows[] = [1, 'ARMY WIDHI CAHYONO', '-', 'Contoh nama guru'];
                    }

                    return $rows;
                }

                public function styles(Worksheet $sheet): array
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
                                'startColor' => ['argb' => 'FF065F46'], // Emerald green
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ],
                    ];
                }
            },

            // Sheet 3: Daftar Kegiatan / Program Sekolah & Mapping ARKAS
            new class implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
            {
                public function title(): string
                {
                    return 'Program Kegiatan Sekolah';
                }

                public function headings(): array
                {
                    return [
                        'No',
                        'Program / Kegiatan Sekolah',
                        'Kode Singkat',
                        'Otomatis Terhubung ke Kegiatan ARKAS',
                        'Keterangan',
                    ];
                }

                public function array(): array
                {
                    $kegiatans = KegiatanSekolah::where('is_active', true)
                        ->with('arkasKegiatan')
                        ->orderBy('urutan')
                        ->orderBy('nama')
                        ->get();

                    $rows = [];
                    $i = 1;
                    foreach ($kegiatans as $k) {
                        $rows[] = [
                            $i++,
                            $k->nama,
                            $k->kode ?: '-',
                            $k->full_arkas_kegiatan ?: '-',
                            $k->keterangan ?: '-',
                        ];
                    }

                    return $rows;
                }

                public function styles(Worksheet $sheet): array
                {
                    $sheet->getStyle('A2:A100')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C2:C100')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    return [
                        1 => [
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                                'size' => 11,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF4F46E5'], // Indigo
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ],
                    ];
                }
            },
        ];
    }
}

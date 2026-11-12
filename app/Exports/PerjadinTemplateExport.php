<?php

namespace App\Exports;

use App\Models\Guru;
use App\Models\Instansi;
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

class PerjadinTemplateExport implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            // Sheet 1: Form Input Perjadin (Template pengisian utama)
            new class implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
            {
                public function headings(): array
                {
                    return [
                        'No',
                        'Tanggal',
                        'No Surat',
                        'Tujuan Dinas',
                        'Alamat Tujuan (Opsional)',
                        'Maksud Perjalanan',
                        'Nama / NIP Guru',
                        'Uang Diterima (Rp)',
                        'No BKU Awal (Opsional)',
                        'PPN (Opsional)',
                        'PPh 21/23 (Opsional)',
                        'Catatan (Opsional)',
                    ];
                }

                public function array(): array
                {
                    // Ambil nama guru dari database jika ada untuk contoh yang realistis
                    try {
                        $existingGurus = Guru::where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->map(fn ($g) => $g->full_name_with_title ?: $g->name)
                            ->toArray();
                    } catch (\Throwable $e) {
                        $existingGurus = [];
                    }

                    $g1 = $existingGurus[0] ?? 'Budi Santoso, S.Pd.';
                    $g2 = $existingGurus[1] ?? 'Siti Aminah, M.Pd.';
                    $g3 = $existingGurus[2] ?? 'Hendra Wijaya, S.Kom.';
                    $g4 = $existingGurus[3] ?? 'Dewi Sartika, S.Pd.';
                    $g5 = $existingGurus[4] ?? 'Ahmad Fauzi, S.T.';

                    return [
                        // Kelompok 1: 5 orang ke SMPN 1 Ringinrejo tgl 2026-05-03 (BKU Awal BPU070 -> BPU070-074)
                        [1, '2026-05-03', '094/082/101.6.21/2026', 'SMPN 1 Ringinrejo', 'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo', 'Sosialisasi Asesmen Nasional', $g1, 65000, 'BPU070', 0, 0, 'Koordinasi Teknis'],
                        [2, '2026-05-03', '', 'SMPN 1 Ringinrejo', 'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo', 'Sosialisasi Asesmen Nasional', $g2, 65000, '', 0, 0, ''],
                        [3, '2026-05-03', '', 'SMPN 1 Ringinrejo', 'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo', 'Sosialisasi Asesmen Nasional', $g3, 65000, '', 0, 0, ''],
                        [4, '2026-05-03', '', 'SMPN 1 Ringinrejo', 'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo', 'Sosialisasi Asesmen Nasional', $g4, 65000, '', 0, 0, ''],
                        [5, '2026-05-03', '', 'SMPN 1 Ringinrejo', 'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo', 'Sosialisasi Asesmen Nasional', $g5, 65000, '', 0, 0, ''],

                        // Kelompok 2: 3 orang ke SMPN 2 Kras tgl 2026-05-03 (BKU Awal BPU075 -> BPU075-077)
                        [6, '2026-05-03', '094/083/101.6.21/2026', 'SMPN 2 Kras', 'Jl. Raya Kras No. 45, Kras', 'Monitoring dan Evaluasi Mutu', $g1, 65000, 'BPU075', 0, 0, 'Monev Standar'],
                        [7, '2026-05-03', '', 'SMPN 2 Kras', 'Jl. Raya Kras No. 45, Kras', 'Monitoring dan Evaluasi Mutu', $g2, 65000, '', 0, 0, ''],
                        [8, '2026-05-03', '', 'SMPN 2 Kras', 'Jl. Raya Kras No. 45, Kras', 'Monitoring dan Evaluasi Mutu', $g3, 65000, '', 0, 0, ''],

                        // Kelompok 3: 5 orang ke SMPN 3 Kediri tgl 2026-05-04 (BKU Awal BPU078 -> BPU078-082)
                        [9, '2026-05-04', '094/084/101.6.21/2026', 'SMPN 3 Kediri', 'Jl. Hasanudin No. 2, Kediri', 'Workshop Kurikulum Merdeka', $g1, 75000, 'BPU078', 0, 0, 'Pelatihan IKM'],
                        [10, '2026-05-04', '', 'SMPN 3 Kediri', 'Jl. Hasanudin No. 2, Kediri', 'Workshop Kurikulum Merdeka', $g2, 75000, '', 0, 0, ''],
                        [11, '2026-05-04', '', 'SMPN 3 Kediri', 'Jl. Hasanudin No. 2, Kediri', 'Workshop Kurikulum Merdeka', $g3, 75000, '', 0, 0, ''],
                        [12, '2026-05-04', '', 'SMPN 3 Kediri', 'Jl. Hasanudin No. 2, Kediri', 'Workshop Kurikulum Merdeka', $g4, 75000, '', 0, 0, ''],
                        [13, '2026-05-04', '', 'SMPN 3 Kediri', 'Jl. Hasanudin No. 2, Kediri', 'Workshop Kurikulum Merdeka', $g5, 75000, '', 0, 0, ''],
                    ];
                }

                public function styles(Worksheet $sheet): array
                {
                    // Alignment Kolom No (A) ke tengah
                    $sheet->getStyle('A2:A200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Format kolom nominal uang (H, J, K) ke format ribuan
                    $sheet->getStyle('H2:H200')->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('J2:K200')->getNumberFormat()->setFormatCode('#,##0');

                    // Format kolom tanggal (B), No Surat (C), dan BKU (I) sebagai text
                    $sheet->getStyle('B2:C200')->getNumberFormat()->setFormatCode('@');
                    $sheet->getStyle('I2:I200')->getNumberFormat()->setFormatCode('@');

                    return [
                        1 => [
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF0F766E'], // Teal header
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
                    return 'Form Input Perjadin';
                }
            },

            // Sheet 2: Referensi Guru Terdaftar (Dapat di-copy ke Sheet 1)
            new class implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
            {
                public function headings(): array
                {
                    return [
                        'NIP',
                        'Nama Lengkap (+ Gelar)',
                        'Nama Dasar',
                        'Status GTK',
                        'Bank Penerima',
                        'Nomor Rekening',
                        'Nama Pemilik Rekening',
                    ];
                }

                public function array(): array
                {
                    try {
                        $gurus = Guru::with('primaryBankAccount')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get();
                    } catch (\Throwable $e) {
                        $gurus = collect([]);
                    }

                    $rows = [];
                    foreach ($gurus as $guru) {
                        $bank = $guru->primaryBankAccount;
                        $rows[] = [
                            $guru->nip ?: '-',
                            $guru->full_name_with_title ?: $guru->name,
                            $guru->name,
                            $guru->employment_status ?: '-',
                            $bank ? ($bank->bank_name ?: '-') : '-',
                            $bank ? $bank->account_number : 'Belum Ada',
                            $bank ? ($bank->account_holder ?: $guru->name) : '-',
                        ];
                    }

                    return $rows;
                }

                public function styles(Worksheet $sheet): array
                {
                    // Pastikan NIP dan Nomor Rekening diformat sebagai text agar tidak jadi eksponen
                    $sheet->getStyle('A2:A300')->getNumberFormat()->setFormatCode('@');
                    $sheet->getStyle('F2:F300')->getNumberFormat()->setFormatCode('@');

                    return [
                        1 => [
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF1E293B'], // Slate header
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
                    return 'Referensi Guru Terdaftar';
                }
            },

            // Sheet 3: Referensi Instansi Terdaftar (Sekolah, DUDI, Dinas)
            new class implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
            {
                public function headings(): array
                {
                    return [
                        'Nama Instansi / Sekolah / DUDI',
                        'Kategori',
                        'Alamat Lengkap',
                        'Kota / Kabupaten',
                    ];
                }

                public function array(): array
                {
                    try {
                        $instansis = Instansi::orderBy('category')->orderBy('name')->get();
                    } catch (\Throwable $e) {
                        $instansis = collect([]);
                    }

                    $rows = [];
                    foreach ($instansis as $instansi) {
                        $rows[] = [
                            $instansi->name,
                            $instansi->category_label ?? ucfirst((string) $instansi->category),
                            $instansi->address ?: '-',
                            $instansi->city ?: 'Kediri',
                        ];
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
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF1E3A8A'], // Blue header
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
                    return 'Referensi Instansi Terdaftar';
                }
            },
        ];
    }
}

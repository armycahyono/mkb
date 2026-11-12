<?php

namespace App\Exports;

use App\Models\BosRencanaBelanja;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BosDaftarRencanaBelanjaExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    use Exportable;

    private int $startDataRow = 7;

    private array $groupHeaderRows = [];

    private array $subtotalRows = [];

    private int $grandTotalRow = 0;

    public function __construct(
        public int|string $year,
        public ?string $statusFilter = 'draft'
    ) {}

    public function array(): array
    {
        $query = BosRencanaBelanja::with([
            'items.kertasKerjaItem',
            'supplier',
        ])
            ->when($this->year && $this->year !== 'all', function ($q) {
                $q->where('year', $this->year);
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('id', 'asc');

        $groups = $query->get();

        $rows = [];
        $statusLabel = strtoupper($this->statusFilter ?: 'SEMUA STATUS');
        $rows[] = ['REKAP RINCIAN DAFTAR RENCANA BELANJA BOSP'];
        $rows[] = ['TAHUN ANGGARAN '.$this->year.' - STATUS: '.$statusLabel];
        $rows[] = ['Pengecekan Barang Sebelum Didaftarkan ke TNT Bank & Sebelum Dibelanjakan'];
        $rows[] = ['Tanggal Unduh: '.date('d/m/Y H:i')];
        $rows[] = [''];
        $rows[] = [
            'NO',
            'NO NOTA / RB',
            'BULAN',
            'KODE PROGRAM',
            'KODE REKENING',
            'KEGIATAN',
            'URAIAN BARANG (ARKAS)',
            'NAMA DI SIPLAH / PASAR',
            'SPESIFIKASI',
            'VOL RENCANA',
            'SATUAN',
            'HARGA SATUAN (RP)',
            'TOTAL (RP)',
            'SUPPLIER / REKENING PEMBAYARAN',
        ];

        $currentRow = 7;
        $globalNo = 1;
        $grandTotalAmount = 0;

        foreach ($groups as $grp) {
            $supText = $grp->supplier_name ?: ($grp->supplier ? $grp->supplier->name : '-');
            if ($grp->account_number) {
                $supText .= ' ('.($grp->bank_name ?: 'Bank').': '.$grp->account_number.')';
            }
            $tglText = $grp->planned_date ? $grp->planned_date->format('d/m/Y') : '-';

            // Baris header grup Rencana Belanja
            $headerText = 'RENCANA: '.($grp->no_nota ?: 'RB #'.$grp->id).' - '.$grp->title.' | Toko: '.$supText.' | Tgl: '.$tglText.' | Status: '.strtoupper($grp->status);
            $rows[] = [$headerText, '', '', '', '', '', '', '', '', '', '', '', '', ''];
            $this->groupHeaderRows[] = $currentRow;
            $currentRow++;

            $groupTotal = 0;
            $items = $grp->items;

            if ($items->isEmpty()) {
                $rows[] = ['(Tidak ada rincian barang)', '', '', '', '', '', '', '', '', '', '', '', 0, ''];
                $currentRow++;
            } else {
                foreach ($items as $idx => $it) {
                    $kk = $it->kertasKerjaItem;
                    $itemTotal = (float) ($it->total_amount ?: ($it->volume_plan * $it->price));
                    $groupTotal += $itemTotal;
                    $grandTotalAmount += $itemTotal;

                    $rows[] = [
                        $globalNo++,
                        $grp->no_nota ?: ('RB #'.$grp->id),
                        $grp->month ?: ($kk?->month ?: '-'),
                        $kk?->program_code ?: '-',
                        $kk?->account_code ?: '-',
                        $kk?->kegiatan ?: '-',
                        $kk?->description ?: '-',
                        $it->nama_pasar ?: ($kk?->description ?: '-'),
                        $it->spesifikasi ?: '-',
                        (float) $it->volume_plan,
                        $it->unit ?: ($kk?->unit ?: '-'),
                        (float) $it->price,
                        $itemTotal,
                        $supText,
                    ];
                    $currentRow++;
                }
            }

            // Subtotal baris per Nota Rencana
            $rows[] = [
                'SUBTOTAL '.($grp->no_nota ?: 'RB #'.$grp->id),
                '', '', '', '', '', '', '', '', '', '', '',
                $groupTotal,
                '',
            ];
            $this->subtotalRows[] = $currentRow;
            $currentRow++;
        }

        // Baris Grand Total
        $rows[] = [
            'TOTAL KESELURUHAN RENCANA BELANJA',
            '', '', '', '', '', '', '', '', '', '', '',
            $grandTotalAmount,
            '',
        ];
        $this->grandTotalRow = $currentRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');
        $sheet->mergeCells('A4:N4');

        $sheet->getStyle('A1:N2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:N4')->getFont()->setItalic(true)->setSize(9);

        // Header Table Style
        $sheet->getStyle('A6:N6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0369A1'], // Sky 700 / Ocean Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(28);

        // Group Header Rows
        foreach ($this->groupHeaderRows as $row) {
            $sheet->mergeCells("A{$row}:N{$row}");
            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FF0F172A'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0F2FE'], // Sky 100
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(22);
        }

        // Subtotal rows
        foreach ($this->subtotalRows as $row) {
            $sheet->mergeCells("A{$row}:L{$row}");
            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 9,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF1F5F9'], // Slate 100
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Grand Total row
        if ($this->grandTotalRow > 0) {
            $gtr = $this->grandTotalRow;
            $sheet->mergeCells("A{$gtr}:L{$gtr}");
            $sheet->getStyle("A{$gtr}:N{$gtr}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF047857'], // Emerald 700
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getStyle("A{$gtr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($gtr)->setRowHeight(24);
        }

        // All table borders
        if ($this->grandTotalRow >= 6) {
            $sheet->getStyle("A6:N{$this->grandTotalRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFCBD5E1'],
                    ],
                ],
            ]);
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'A' => '@',
            'B' => '@',
            'D' => '@',
            'E' => '@',
            'J' => '#,##0.##',
            'L' => '#,##0',
            'M' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Rencana Belanja';
    }
}

<?php

namespace App\Exports;

use App\Models\BosKertasKerjaItem;
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

class BosBarangBelumBelanjaExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    use Exportable;

    private array $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    private int $startDataRow = 7;

    private int $totalDataRows = 0;

    private array $programHeaderRows = [];

    private array $subtotalRows = [];

    private int $grandTotalRow = 0;

    public function __construct(
        public int|string $year,
        public ?string $targetMonth = null,
        public bool $hidePlanned = true
    ) {
        if (! $this->targetMonth) {
            $currentMonthIndex = (int) date('n') - 1;
            $this->targetMonth = $this->months[$currentMonthIndex] ?? 'Desember';
        }
    }

    public function array(): array
    {
        $targetIndex = array_search($this->targetMonth, $this->months);
        $allowedMonths = ($targetIndex === false) ? $this->months : array_slice($this->months, 0, $targetIndex + 1);

        $items = BosKertasKerjaItem::where('year', $this->year)
            ->whereIn('month', $allowedMonths)
            ->whereHas('file', function ($f) {
                $f->where('is_active', true);
            })
            ->with([
                'realisasis',
                'rencanaBelanjaItems.rencanaBelanja',
            ])
            ->orderByRaw("CASE month WHEN 'Januari' THEN 1 WHEN 'Februari' THEN 2 WHEN 'Maret' THEN 3 WHEN 'April' THEN 4 WHEN 'Mei' THEN 5 WHEN 'Juni' THEN 6 WHEN 'Juli' THEN 7 WHEN 'Agustus' THEN 8 WHEN 'September' THEN 9 WHEN 'Oktober' THEN 10 WHEN 'November' THEN 11 WHEN 'Desember' THEN 12 ELSE 99 END ASC")
            ->get();

        // Kelompokkan data sama seperti di unrealizedData controller
        $grouped = [];
        foreach ($items as $item) {
            $groupKey = md5(
                trim($item->account_code).'_'.
                strtolower(trim((string) $item->kegiatan)).'_'.
                strtolower(trim((string) $item->description)).'_'.
                strtolower(trim((string) $item->unit)).'_'.
                number_format((float) $item->price, 2, '.', '')
            );

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'id' => $item->id,
                    'account_code' => $item->account_code,
                    'program_code' => $item->program_code ?: '-',
                    'kegiatan' => $item->kegiatan ?: '-',
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'price' => (float) $item->price,
                    'months_included' => [],
                    'total_budget_vol' => 0,
                    'total_realized_vol' => 0,
                    'total_planned_vol' => 0,
                    'active_plans' => [],
                ];
            }

            $grouped[$groupKey]['months_included'][] = $item->month;
            $grouped[$groupKey]['total_budget_vol'] += (float) $item->volume;
            $grouped[$groupKey]['total_realized_vol'] += (float) $item->realisasis->sum('volume');

            if ($item->relationLoaded('rencanaBelanjaItems')) {
                foreach ($item->rencanaBelanjaItems as $rItem) {
                    $rb = $rItem->rencanaBelanja;
                    if ($rb && ! $rb->trashed() && $rb->status !== 'realized') {
                        $grouped[$groupKey]['total_planned_vol'] += (float) $rItem->volume_plan;
                        $notaLabel = $rb->no_nota ?: ('RB #'.$rb->id);
                        if (! in_array($notaLabel, $grouped[$groupKey]['active_plans'])) {
                            $grouped[$groupKey]['active_plans'][] = $notaLabel;
                        }
                    }
                }
            }
        }

        // Filter barang yang belum terealisasi / belum habis
        $filtered = [];
        foreach ($grouped as $g) {
            $unrealizedVol = max(0, $g['total_budget_vol'] - $g['total_realized_vol']);
            if ($unrealizedVol <= 0.001) {
                continue;
            }

            $availableVol = max(0, $unrealizedVol - $g['total_planned_vol']);
            $isFullyPlanned = ($availableVol <= 0.001);

            if ($this->hidePlanned && $isFullyPlanned) {
                continue;
            }

            $monthsStr = implode(', ', array_unique($g['months_included']));
            $remainingAmount = $availableVol * $g['price'];

            $filtered[] = [
                'program_code' => $g['program_code'],
                'kegiatan' => $g['kegiatan'],
                'account_code' => $g['account_code'],
                'description' => $g['description'],
                'unit' => $g['unit'],
                'price' => $g['price'],
                'total_budget_vol' => $g['total_budget_vol'],
                'total_realized_vol' => $g['total_realized_vol'],
                'total_planned_vol' => $g['total_planned_vol'],
                'available_volume' => $availableVol,
                'remaining_amount' => $remainingAmount,
                'months_included' => $monthsStr,
                'active_plans' => implode(', ', $g['active_plans']),
            ];
        }

        // Kelompokkan per Program Code
        $byProgram = collect($filtered)->groupBy('program_code')->sortKeys();

        $rows = [];
        $rows[] = ['DAFTAR BARANG BOSP BELUM DIBELANJAKAN'];
        $rows[] = ['TAHUN ANGGARAN '.$this->year.' (ALOKASI S/D BULAN: '.strtoupper($this->targetMonth).')'];
        $rows[] = ['Kondisi: '.($this->hidePlanned ? 'Hanya barang dengan sisa kuota yang belum dialokasikan ke Rencana Belanja' : 'Termasuk barang yang sudah masuk ke draf Rencana Belanja')];
        $rows[] = ['Tanggal Unduh: '.date('d/m/Y H:i')];
        $rows[] = [''];
        $rows[] = [
            'NO',
            'KODE PROGRAM',
            'KEGIATAN / KODE REKENING',
            'URAIAN BARANG / SPESIFIKASI',
            'ALOKASI BULAN',
            'VOL ANGGARAN',
            'VOL REALISASI',
            'VOL RENCANA',
            'SISA VOL',
            'SATUAN',
            'HARGA SATUAN (RP)',
            'TOTAL SISA (RP)',
            'RENCANA AKTIF',
        ];

        $currentRow = 7;
        $globalNo = 1;
        $grandTotalAmount = 0;

        foreach ($byProgram as $progCode => $progItems) {
            $progHeader = 'PROGRAM: '.$progCode.' - '.(count($progItems).' Item Barang');
            $rows[] = [$progHeader, '', '', '', '', '', '', '', '', '', '', '', ''];
            $this->programHeaderRows[] = $currentRow;
            $currentRow++;

            $subtotalProgramAmount = 0;

            foreach ($progItems as $it) {
                $rows[] = [
                    $globalNo++,
                    $it['program_code'],
                    $it['kegiatan'].' ('.$it['account_code'].')',
                    $it['description'],
                    $it['months_included'],
                    $it['total_budget_vol'],
                    $it['total_realized_vol'],
                    $it['total_planned_vol'],
                    $it['available_volume'],
                    $it['unit'],
                    $it['price'],
                    $it['remaining_amount'],
                    $it['active_plans'],
                ];
                $subtotalProgramAmount += $it['remaining_amount'];
                $grandTotalAmount += $it['remaining_amount'];
                $currentRow++;
            }

            // Subtotal per Program
            $rows[] = [
                'SUBTOTAL PROGRAM '.$progCode,
                '', '', '', '', '', '', '', '', '', '',
                $subtotalProgramAmount,
                '',
            ];
            $this->subtotalRows[] = $currentRow;
            $currentRow++;
        }

        // Baris Grand Total
        $rows[] = [
            'TOTAL KESELURUHAN',
            '', '', '', '', '', '', '', '', '', '',
            $grandTotalAmount,
            '',
        ];
        $this->grandTotalRow = $currentRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->mergeCells('A3:M3');
        $sheet->mergeCells('A4:M4');

        $sheet->getStyle('A1:M2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:M4')->getFont()->setItalic(true)->setSize(9);

        // Header Table Style
        $sheet->getStyle('A6:M6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E40AF'], // Indigo / Navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(28);

        // Program Group Headers
        foreach ($this->programHeaderRows as $row) {
            $sheet->mergeCells("A{$row}:M{$row}");
            $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FF1E3A8A'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFDBEAFE'], // Light Blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Subtotal rows
        foreach ($this->subtotalRows as $row) {
            $sheet->mergeCells("A{$row}:K{$row}");
            $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
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
            $sheet->mergeCells("A{$gtr}:K{$gtr}");
            $sheet->getStyle("A{$gtr}:M{$gtr}")->applyFromArray([
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
            $sheet->getStyle("A6:M{$this->grandTotalRow}")->applyFromArray([
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
            'F' => '#,##0.##',
            'G' => '#,##0.##',
            'H' => '#,##0.##',
            'I' => '#,##0.##',
            'K' => '#,##0',
            'L' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Barang Belum Belanja';
    }
}

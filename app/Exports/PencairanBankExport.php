<?php

namespace App\Exports;

use App\Models\PencairanBank;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PencairanBankExport extends DefaultValueBinder implements Export, WithCustomValueBinder, WithMultipleSheets
{
    use Exportable;

    public function __construct(public PencairanBank $pencairanBank)
    {
        if ($this->pencairanBank->exists) {
            $this->pencairanBank->loadMissing([
                'items.rencanaBelanja.supplier.bankAccounts',
                'items.perjadinItem.bankAccount',
                'items.perjadinItem.guru.primaryBankAccount',
                'kepalaSekolah',
                'bendahara',
            ]);
        }
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && preg_match('/^[0-9]+$/', $value) && strlen($value) > 7) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function sheets(): array
    {
        return [
            new PencairanBankBatchUploadSheet($this->pencairanBank),
            new PencairanBankFormalSheet($this->pencairanBank),
        ];
    }

    /**
     * Parse raw account string into [bankName, accountNumber].
     * Handles formats like:
     * - "VA. Bank Jatim 25558856875055" -> ["VA, BANK JATIM", "25558856875055"]
     * - "VA. 25558856875055" -> ["VA, BANK JATIM", "25558856875055"]
     * - "Bank Jatim. 1006640440" -> ["BANK JATIM", "1006640440"]
     * - "1006640440" -> ["BANK JATIM", "1006640440"]
     *
     * @return array{0: string, 1: string}
     */
    public static function parseAccountInfo(string $rawAcc, ?string $defaultBank = 'Bank Jatim', bool $isOnline = false): array
    {
        $bankName = $defaultBank ?: 'Bank Jatim';
        $accNum = trim($rawAcc);
        $isVa = $isOnline;

        if (preg_match('/^VA[\.,]?\s*(.*)$/i', $rawAcc, $vaMatches)) {
            $isVa = true;
            $rest = trim($vaMatches[1]);
            if (preg_match('/^(.*?)\s*([0-9][0-9\s\-]*)$/', $rest, $m)) {
                $extractedBank = trim($m[1]);
                $extractedAcc = trim(str_replace([' ', '-'], '', $m[2]));
                if ($extractedBank !== '') {
                    $bankName = $extractedBank;
                }
                $accNum = $extractedAcc;
            } else {
                if ($rest !== '') {
                    $bankName = $rest;
                }
                $accNum = '';
            }
        } elseif (str_contains($rawAcc, '.')) {
            $parts = explode('.', $rawAcc, 2);
            $bankName = trim($parts[0]);
            $accNum = trim($parts[1]);
        }

        if ($isVa) {
            $cleanBank = trim(preg_replace('/^VA[\.,]?\s*/i', '', $bankName));
            $bankName = 'VA, '.strtoupper($cleanBank ?: 'BANK JATIM');
        } else {
            $bankName = strtoupper($bankName);
        }

        return [$bankName, $accNum];
    }
}

class PencairanBankFormalSheet extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithStyles, WithTitle
{
    private int $startRow = 9;

    private int $totalRows = 0;

    public function __construct(public PencairanBank $pencairanBank) {}

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && preg_match('/^[0-9]+$/', $value) && strlen($value) > 7) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function array(): array
    {
        $letterDate = $this->pencairanBank->letter_date ? $this->pencairanBank->letter_date->translatedFormat('d F Y') : '-';
        $rows = [
            ['DAFTAR SURAT PERINTAH PENCAIRAN DANA'],
            ['SMKS PGRI KRAS KEDIRI'],
            ['YANG TIDAK TERPISAHKAN DARI SURAT KUASA'],
            ['Nomor: '.$this->pencairanBank->letter_number],
            [''],
            ['Rekening Sumber', ': '.$this->pencairanBank->source_account_number.' a.n. '.$this->pencairanBank->source_account_name],
            ['Bank Sumber', ': '.$this->pencairanBank->bank_name.' '.$this->pencairanBank->bank_branch],
            ['Tanggal', ': '.$letterDate],
            ['NO', 'NAMA PEMILIK REKENING', 'BANK TUJUAN', 'NO REKENING TUJUAN', 'NILAI TRANSFER (RP)', 'KETERANGAN'],
        ];

        $total = 0;
        $itemsCount = 0;

        foreach ($this->pencairanBank->items as $idx => $item) {
            $itemsCount++;
            $desc = $item->description ?: '-';
            if ($item->type === 'perjadin' || str_starts_with($desc, 'Perjadin:')) {
                $desc = 'Perjadin';
            }
            if (str_starts_with($desc, 'Belanja: ')) {
                $desc = substr($desc, 9);
            }

            // Resolusi nama pemilik rekening
            $recipientName = $item->recipient_name;
            if ($item->type === 'perjadin' && $item->perjadinItem) {
                $pItem = $item->perjadinItem;
                $bank = $pItem->bankAccount ?? ($pItem->guru ? $pItem->guru->primaryBankAccount : null);
                if ($bank && ! empty($bank->account_holder)) {
                    $recipientName = $bank->account_holder;
                } elseif ($pItem->guru) {
                    $recipientName = $pItem->guru->name;
                }
            } elseif ($item->rencanaBelanja && $item->rencanaBelanja->supplier) {
                $supplier = $item->rencanaBelanja->supplier;
                if ($supplier->type === 'online') {
                    $recipientName = $supplier->account_holder ?: 'PT. LADANG KARYA HUSADA';
                } else {
                    $cleanAcc = preg_replace('/[^0-9]/', '', (string) $item->account_number);
                    $accHolder = null;
                    if ($cleanAcc && $supplier->relationLoaded('bankAccounts')) {
                        $matchedAcc = $supplier->bankAccounts->first(function ($ba) use ($cleanAcc) {
                            return preg_replace('/[^0-9]/', '', (string) $ba->account_number) === $cleanAcc;
                        });
                        $accHolder = $matchedAcc?->account_holder;
                    }
                    if (! $accHolder) {
                        $accHolder = $supplier->account_holder;
                    }
                    if (! $accHolder && $supplier->relationLoaded('bankAccounts')) {
                        $accHolder = $supplier->bankAccounts->firstWhere('is_primary', true)?->account_holder
                            ?? $supplier->bankAccounts->first()?->account_holder;
                    }
                    if ($accHolder) {
                        $recipientName = $accHolder;
                    }
                }
            }

            $isOnline = false;
            if ($item->rencanaBelanja) {
                $isOnline = (bool) ($item->rencanaBelanja->is_online
                    || ($item->rencanaBelanja->supplier && $item->rencanaBelanja->supplier->type === 'online'));
            }
            if (! $isOnline && str_starts_with(strtoupper((string) $item->account_number), 'VA')) {
                $isOnline = true;
            }

            $rawAcc = (string) $item->account_number;
            [$bankName, $accNum] = PencairanBankExport::parseAccountInfo($rawAcc, $item->bank_name, $isOnline);

            $amount = (float) $item->amount;
            $total += $amount;

            $rows[] = [
                $idx + 1,
                strtoupper($recipientName),
                strtoupper($bankName),
                $accNum,
                $amount,
                $desc,
            ];
        }

        $this->totalRows = $itemsCount;

        // Total baris
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            $total,
            '',
        ];

        // Tanda tangan
        $bendaharaName = $this->pencairanBank->bendahara ? $this->pencairanBank->bendahara->name : 'Bendahara';
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [
            '',
            'Yang diberi kuasa,',
            '',
            '',
            'Kediri, '.$letterDate,
            '',
        ];
        $rows[] = [
            '',
            $this->pencairanBank->bank_name,
            '',
            '',
            'Yang memberi kuasa,',
            '',
        ];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [
            '',
            '( ...................................................... )',
            '',
            '',
            $bendaharaName,
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');
        $sheet->mergeCells('A4:F4');

        $headerRow = $this->startRow;
        $lastDataRow = $headerRow + $this->totalRows;
        $totalRow = $lastDataRow + 1;

        $sheet->mergeCells("A{$totalRow}:D{$totalRow}");

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $sheet->getStyle("A{$headerRow}:F{$totalRow}")->applyFromArray($borderStyle);

        // Header style
        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Total row style
        $sheet->getStyle("A{$totalRow}:F{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2F2F2'],
            ],
        ]);

        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Alignments for data rows
        for ($r = $headerRow + 1; $r <= $lastDataRow; $r++) {
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('@');
        }

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            6 => ['font' => ['bold' => true, 'size' => 10]],
            7 => ['font' => ['bold' => true, 'size' => 10]],
            8 => ['font' => ['bold' => true, 'size' => 10]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '@',
            'E' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Lampiran Surat Perintah';
    }
}

class PencairanBankBatchUploadSheet extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(public PencairanBank $pencairanBank) {}

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && preg_match('/^[0-9]+$/', $value) && strlen($value) > 7) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'NO',
            'NO_REKENING_TUJUAN',
            'NAMA_PEMILIK_REKENING',
            'BANK_TUJUAN',
            'NOMINAL_TRANSFER',
            'BERITA_TRANSFER',
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->pencairanBank->items as $idx => $item) {
            $desc = $item->description ?: '-';
            if ($item->type === 'perjadin' || str_starts_with($desc, 'Perjadin:')) {
                $desc = 'Perjadin';
            }
            if (str_starts_with($desc, 'Belanja: ')) {
                $desc = substr($desc, 9);
            }

            $recipientName = $item->recipient_name;
            if ($item->type === 'perjadin' && $item->perjadinItem) {
                $pItem = $item->perjadinItem;
                $bank = $pItem->bankAccount ?? ($pItem->guru ? $pItem->guru->primaryBankAccount : null);
                if ($bank && ! empty($bank->account_holder)) {
                    $recipientName = $bank->account_holder;
                } elseif ($pItem->guru) {
                    $recipientName = $pItem->guru->name;
                }
            } elseif ($item->rencanaBelanja && $item->rencanaBelanja->supplier) {
                $supplier = $item->rencanaBelanja->supplier;
                if ($supplier->type === 'online') {
                    $recipientName = $supplier->account_holder ?: 'PT. LADANG KARYA HUSADA';
                } else {
                    $cleanAcc = preg_replace('/[^0-9]/', '', (string) $item->account_number);
                    $accHolder = null;
                    if ($cleanAcc && $supplier->relationLoaded('bankAccounts')) {
                        $matchedAcc = $supplier->bankAccounts->first(function ($ba) use ($cleanAcc) {
                            return preg_replace('/[^0-9]/', '', (string) $ba->account_number) === $cleanAcc;
                        });
                        $accHolder = $matchedAcc?->account_holder;
                    }
                    if (! $accHolder) {
                        $accHolder = $supplier->account_holder;
                    }
                    if (! $accHolder && $supplier->relationLoaded('bankAccounts')) {
                        $accHolder = $supplier->bankAccounts->firstWhere('is_primary', true)?->account_holder
                            ?? $supplier->bankAccounts->first()?->account_holder;
                    }
                    if ($accHolder) {
                        $recipientName = $accHolder;
                    }
                }
            }

            $isOnline = false;
            if ($item->rencanaBelanja) {
                $isOnline = (bool) ($item->rencanaBelanja->is_online
                    || ($item->rencanaBelanja->supplier && $item->rencanaBelanja->supplier->type === 'online'));
            }
            if (! $isOnline && str_starts_with(strtoupper((string) $item->account_number), 'VA')) {
                $isOnline = true;
            }

            $rawAcc = (string) $item->account_number;
            [$bankName, $accNum] = PencairanBankExport::parseAccountInfo($rawAcc, $item->bank_name, $isOnline);

            $rows[] = [
                $idx + 1,
                $accNum,
                strtoupper($recipientName),
                strtoupper($bankName),
                (float) $item->amount,
                $desc,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->pencairanBank->items) + 1;

        $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
        ]);

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2E75B6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        for ($r = 2; $r <= $lastRow; $r++) {
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('@');
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'B' => '@',
            'E' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Format Upload Bank (CMS)';
    }
}

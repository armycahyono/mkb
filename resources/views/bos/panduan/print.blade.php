<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERENCANAAN BELANJA BOSP - {{ $group->title }} ({{ $group->no_nota }})</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            background-color: #e2e8f0;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 20px 0;
        }

        /* Top Action Bar (No Print) */
        .no-print-bar {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto 16px auto;
            background: #1e293b;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .no-print-bar .bar-title {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .no-print-bar .bar-subtitle {
            font-size: 11px;
            color: #94a3b8;
            margin-left: 6px;
            font-weight: normal;
        }

        .no-print-bar .btn-action {
            font-size: 12px;
            padding: 6px 14px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s;
        }

        .btn-print-doc {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-print-doc:hover {
            background-color: #059669;
            color: #ffffff;
        }

        .btn-close-doc {
            background-color: #475569;
            color: #f8fafc;
            margin-left: 8px;
        }
        .btn-close-doc:hover {
            background-color: #334155;
            color: #ffffff;
        }

        /* A4 Sheet Container */
        .page-sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            padding: 15mm 16mm 20mm 16mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12), 0 0 1px rgba(0, 0, 0, 0.1);
            position: relative;
            border-radius: 2px;
        }

        /* Document Typography & Elements */
        .doc-title {
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .doc-subtitle {
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .doc-desc {
            font-size: 9pt;
            font-style: italic;
            color: #4b5563;
        }

        .header-divider {
            border-bottom: 2px solid #000;
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .info-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 9.5pt;
        }

        /* Table Styles */
        .table-print {
            border-collapse: collapse !important;
            margin-bottom: 12px;
        }

        .table-print th, .table-print td {
            border: 1px solid #1f2937 !important;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .table-print thead th {
            background-color: #f1f5f9 !important;
            color: #0f172a;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.3px;
        }

        .group-kegiatan-header {
            background-color: #e2e8f0 !important;
            color: #0f172a;
            font-weight: bold;
            font-size: 9.5pt;
            text-transform: uppercase;
        }

        .group-rekening-header {
            background-color: #f8fafc !important;
            color: #334155;
            font-weight: bold;
            font-size: 9pt;
        }

        .subtotal-row {
            background-color: #f8fafc !important;
            font-weight: bold;
            font-size: 9pt;
        }

        .grand-total-row {
            background-color: #e2e8f0 !important;
        }

        .notes-box {
            border: 1px solid #94a3b8;
            background-color: #fcfcfc;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 9pt;
        }

        .signature-section {
            page-break-inside: avoid;
            margin-top: 15px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print, .no-print-bar {
                display: none !important;
            }

            .page-sheet {
                width: 100% !important;
                max-width: none !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }

            .table-print {
                page-break-inside: auto;
            }

            .table-print thead {
                display: table-header-group;
            }

            .table-print tfoot {
                display: table-footer-group;
            }

            .table-print tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .signature-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- Top Action Bar on Screen --}}
    <div class="no-print-bar">
        <div class="bar-title">
            <i class="fas fa-file-invoice"></i>
            <span>PANDUAN BELANJA &amp; CHECKOUT SIPLah</span>
            <span class="bar-subtitle">({{ $group->no_nota ?? 'Draf Rencana' }})</span>
        </div>
        <div>
            <button type="button" onclick="window.print()" class="btn-action btn-print-doc">
                <i class="fas fa-print"></i> Cetak / Simpan PDF
            </button>
            <button type="button" onclick="window.close()" class="btn-action btn-close-doc">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>

    {{-- A4 Paper Sheet Wrapper --}}
    <div class="page-sheet">

        {{-- Header Judul Center --}}
        <div class="text-center">
            <h4 class="doc-title">PERENCANAAN BELANJA &amp; PANDUAN CHECKOUT SIPLah</h4>
            <h5 class="doc-subtitle">BOSP SMK PGRI KRAS</h5>
            <div class="doc-desc">Dokumen Referensi Negosiasi Mitra &amp; Panduan Transaksi PO SIPLah</div>
        </div>
        <div class="header-divider"></div>

        {{-- Info Grid (2 Kolom) --}}
        <div class="row mb-2">
            <div class="col-6">
                <table class="info-table w-100">
                    <tr>
                        <td width="130">Sumber Dana</td>
                        <td width="15">:</td>
                        <td><strong>BOSP (BOS Reguler)</strong></td>
                    </tr>
                    <tr>
                        <td>Tahun Anggaran</td>
                        <td>:</td>
                        <td>{{ $group->year }}</td>
                    </tr>
                    <tr>
                        <td>Nama Penyedia / Mitra</td>
                        <td>:</td>
                        <td><strong>{{ $group->supplier_name ?: '-' }}</strong></td>
                    </tr>
                    @if($group->bank_name || $group->account_number)
                    <tr>
                        <td>Rekening Mitra</td>
                        <td>:</td>
                        <td>{{ $group->bank_name ?: 'Bank' }} - {{ $group->account_number ?: '-' }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="col-6">
                <table class="info-table w-100">
                    <tr>
                        <td width="145">No. Rencana / PO</td>
                        <td width="15">:</td>
                        <td><strong style="font-size: 10pt;">{{ $group->no_nota ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Judul Belanja</td>
                        <td>:</td>
                        <td>{{ $group->title }}</td>
                    </tr>
                    <tr>
                        <td>Tgl Rencana Belanja</td>
                        <td>:</td>
                        <td>{{ $group->planned_date ? $group->planned_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td>Alokasi s/d Bulan</td>
                        <td>:</td>
                        <td>{{ $group->month }} {{ $group->year }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @php
            $targetDate = $group->planned_date ?: now();
            $resolvedKepsekName = $kepsekName ?? (\App\Models\GuruJabatan::getPejabatByDate('kepala_sekolah', $targetDate)?->guru?->full_name_with_title ?? 'ENDANG PATMOWATI, S.Pd');

            // Susun pengelompokan jika belum dikirim dari controller
            $groupsData = isset($groupedItems) ? $groupedItems : $group->items->groupBy(function ($item) {
                $kk = $item->kertasKerjaItem;
                $code = $kk?->program_code ?: '-';
                $keg = $kk?->kegiatan ?: 'Tanpa Kegiatan';
                return $code !== '-' ? "{$code} - {$keg}" : $keg;
            })->map(function ($itemsInKeg) {
                return $itemsInKeg->groupBy(function ($item) {
                    return $item->kertasKerjaItem?->account_code ?: 'Tanpa Kode Rekening';
                });
            });

            $globalCounter = 0;
            $grandTotal = 0;
        @endphp

        {{-- Tabel Rincian Belanja (Dikelompokkan Berdasarkan Kegiatan & Kode Rekening) --}}
        <table class="table table-print w-100 mb-3">
            <thead>
                <tr>
                    <th width="32">NO</th>
                    <th>URAIAN / NAMA BARANG</th>
                    <th width="65">VOL</th>
                    <th width="55">SATUAN</th>
                    <th width="105">HARGA SATUAN</th>
                    <th width="115">TOTAL HARGA</th>
                    <th width="130">SPESIFIKASI / KET.</th>
                    <th width="45" class="text-center">CEK</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groupsData as $kegiatanName => $rekeningList)
                    {{-- Header Grup 1: Program & Kegiatan --}}
                    <tr class="group-kegiatan-header">
                        <td colspan="8" class="py-1 px-2">
                            <span class="font-weight-bold">&#9658; PROGRAM / KEGIATAN:</span> {{ $kegiatanName }}
                        </td>
                    </tr>

                    @foreach($rekeningList as $rekeningCode => $itemsInRekening)
                        {{-- Header Grup 2: Kode Rekening Belanja --}}
                        <tr class="group-rekening-header">
                            <td colspan="8" class="py-1 px-3">
                                <span class="font-weight-bold">&#8627; KODE REKENING:</span> {{ $rekeningCode }}
                            </td>
                        </tr>

                        @php $subTotalRekening = 0; @endphp
                        @foreach($itemsInRekening as $item)
                            @php
                                $globalCounter++;
                                $itemTotal = (float) $item->total_amount;
                                $subTotalRekening += $itemTotal;
                                $grandTotal += $itemTotal;
                                $kk = $item->kertasKerjaItem;

                                // Pemaduan pertama jika ada
                                $firstPengajuan = $kk?->pengajuanBarangs?->first();
                                $spek = $item->spesifikasi ?: ($firstPengajuan?->spesifikasi ?: '-');
                            @endphp
                            <tr>
                                <td class="text-center">{{ $globalCounter }}</td>
                                <td>
                                    @if($item->nama_pasar)
                                        <div class="font-weight-bold text-dark">{{ $item->nama_pasar }}</div>
                                        <div class="small text-muted" style="font-size: 8pt;">(ARKAS: {{ $kk?->description ?: '-' }})</div>
                                    @else
                                        <div class="font-weight-bold text-dark">{{ $kk?->description ?: '-' }}</div>
                                        @if($firstPengajuan)
                                            <div class="small text-muted font-italic" style="font-size: 8pt;">(Ref. Pemaduan: {{ $firstPengajuan->nama_barang }})</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center">{{ number_format($item->volume_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $item->unit }}</td>
                                <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-right font-weight-bold">Rp {{ number_format($itemTotal, 0, ',', '.') }}</td>
                                <td style="font-size: 8.5pt;">{{ $spek }}</td>
                                <td class="text-center" style="font-size: 13pt;">[ &nbsp; ]</td>
                            </tr>
                        @endforeach

                        {{-- Subtotal per Kode Rekening --}}
                        <tr class="subtotal-row">
                            <td colspan="5" class="text-right font-weight-bold" style="font-size: 8.5pt;">
                                Sub-total Rekening [{{ $rekeningCode }}]:
                            </td>
                            <td class="text-right font-weight-bold text-primary">
                                Rp {{ number_format($subTotalRekening, 0, ',', '.') }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">Tidak ada data rincian barang.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="grand-total-row">
                    <th colspan="5" class="text-right font-weight-bold" style="font-size: 9.5pt;">
                        GRAND TOTAL PO SIPLAH (KESELURUHAN):
                    </th>
                    <th class="text-right font-weight-bold text-success" style="font-size: 10pt;">
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>

        @if($group->notes)
            <div class="notes-box">
                <strong>Catatan Tambahan / Keterangan Toko:</strong><br>
                <p class="mb-0">{{ $group->notes }}</p>
            </div>
        @endif

        {{-- Tanda Tangan --}}
        <div class="row signature-section">
            <div class="col-6 text-center">
                <p style="margin-bottom: 55px;">Mengetahui,<br>Kepala Sekolah</p>
                <p class="font-weight-bold mb-0">( <u>{{ $resolvedKepsekName }}</u> )</p>
            </div>
            <div class="col-6 text-center">
                <p style="margin-bottom: 55px;">
                    Kediri, {{ $group->planned_date ? $group->planned_date->translatedFormat('d F Y') : date('d F Y') }}<br>
                    Penanggung Jawab Belanja / Bendahara
                </p>
                <p class="font-weight-bold mb-0">( _______________________ )</p>
            </div>
        </div>

    </div>
</body>
</html>

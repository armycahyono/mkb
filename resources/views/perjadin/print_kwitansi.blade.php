<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Perjadin - {{ $perjadin->destination_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 15mm 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .kwitansi-container {
            background-color: #fff;
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            padding: 20mm 20mm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .no-print-bar {
            background: #343a40;
            color: #fff;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
            max-width: 210mm;
            margin: 0 auto 20px auto;
        }

        .btn-print {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-close-window {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Header Box Kanan Atas */
        .header-box-table {
            float: right;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .header-box-table td {
            border: 1.5px solid #000;
            padding: 4px 10px;
            font-size: 10.5pt;
            font-weight: 500;
        }

        .header-box-table td.label {
            width: 110px;
            font-weight: bold;
        }

        .header-box-table td.value {
            width: 160px;
            font-weight: bold;
            text-align: left;
        }

        .clear {
            clear: both;
        }

        /* Judul Kwitansi */
        .title-block {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 35px;
        }

        .title-block h1 {
            font-size: 17pt;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }

        .title-block h2 {
            font-size: 13.5pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        /* Tabel Rincian Data Kwitansi */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .content-table td {
            vertical-align: top;
            padding: 4px 0;
        }

        .content-table td.col-label {
            width: 170px;
            white-space: nowrap;
        }

        .content-table td.col-colon {
            width: 20px;
            text-align: center;
        }

        .content-table td.col-value {
            text-align: justify;
        }

        .sub-details {
            margin-top: 4px;
            padding-left: 0;
        }

        .sub-details table {
            border-collapse: collapse;
            width: 100%;
        }

        .sub-details td {
            padding: 2px 0;
        }

        /* Tabel Perhitungan Potongan Pajak */
        .tax-table-container {
            margin-left: auto;
            margin-right: 0;
            width: 340px;
            margin-top: 15px;
            margin-bottom: 40px;
        }

        .tax-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tax-table td {
            padding: 3px 0;
            font-size: 10.5pt;
        }

        .tax-table td.tax-label {
            width: 180px;
        }

        .tax-table td.tax-colon {
            width: 20px;
            text-align: center;
        }

        .tax-table td.tax-val {
            text-align: left;
            font-family: Arial, Helvetica, sans-serif;
        }

        .tax-line {
            border-bottom: 1.5px solid #000;
        }

        /* Blok Tanda Tangan 3 Pihak */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: center;
        }

        .signature-table td {
            vertical-align: top;
            width: 33.33%;
            padding: 0 6px;
            font-size: 10.5pt;
            line-height: 1.35;
        }

        .sign-space {
            height: 75px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .kwitansi-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                min-height: auto;
                page-break-after: always;
            }

            .kwitansi-container:last-child {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong>Cetak Kwitansi Pembayaran Perjalanan Dinas</strong>
        <span style="font-size: 12px; margin-left: 10px; color: #ced4da;">(Total {{ $items->count() }} lembar kwitansi)</span>
    </div>
    <div>
        <button type="button" class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        <button type="button" class="btn-close-window" onclick="window.close()">Tutup</button>
    </div>
</div>

@php
    $schoolName = $school ? ($school->name ?: 'SMK PGRI KRAS KEDIRI') : 'SMK PGRI KRAS KEDIRI';
    $kepsek = $perjadin->kepalaSekolah;
    $bendahara = $perjadin->bendahara;

    $formatGuruName = function ($guru, string $default = '-') {
        if (! $guru) {
            return $default;
        }
        $prefix = trim((string) $guru->title_prefix);
        $suffix = trim((string) $guru->title_suffix);
        $name = strtoupper(trim((string) $guru->name));

        $res = $prefix ? $prefix . ' ' . $name : $name;
        if ($suffix) {
            $res .= ', ' . $suffix;
        }
        return $res;
    };

    $kepsekName = $kepsek ? $formatGuruName($kepsek) : 'ENDANG PATMOWATI, S.Pd';
    $bendaharaName = $bendahara ? $formatGuruName($bendahara) : 'ARMY WIDHI CAHYONO, S.Pt.';

    $paymentDateStr = $perjadin->payment_date ? $perjadin->payment_date->translatedFormat('d F Y') : date('d F Y');
    $depDateStr = $perjadin->departure_date ? $perjadin->departure_date->translatedFormat('d F Y') : '-';
    if ($perjadin->return_date && $perjadin->return_date->ne($perjadin->departure_date)) {
        $depDateStr .= ' s/d ' . $perjadin->return_date->translatedFormat('d F Y');
    }

    $bendaharaTitle = ($fundingSource === 'BPOPP') ? 'Bendahara BPOPP' : 'Bendahara BOS';
@endphp

@foreach($items as $index => $item)
<div class="kwitansi-container">
    {{-- Header Box Kanan Atas --}}
    <table class="header-box-table">
        <tr>
            <td class="label">No. BKU</td>
            <td class="value">{{ $perjadin->getBkuNumberForItem($index) }}</td>
        </tr>
        <tr>
            <td class="label">No. Rekening</td>
            <td class="value">{{ $perjadin->account_code ?: '-' }}</td>
        </tr>
    </table>
    <div class="clear"></div>

    {{-- Judul Kwitansi --}}
    <div class="title-block">
        <h1>KWITANSI</h1>
        <h2>PEMBAYARAN PERJALANAN DINAS</h2>
    </div>

    {{-- Isi Kwitansi --}}
    <table class="content-table">
        <tr>
            <td class="col-label">Sudah terima dari</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ strtoupper($schoolName) }}</td>
        </tr>
        <tr>
            <td class="col-label">Uang sejumlah</td>
            <td class="col-colon">:</td>
            <td class="col-value">Rp. {{ number_format($item->net_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="col-label">Terbilang</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $item->terbilang }}</td>
        </tr>
        <tr>
            <td class="col-label">Untuk Pembayaran</td>
            <td class="col-colon">:</td>
            <td class="col-value">
                Biaya Perjalanan Dinas atas nama <strong>{{ $formatGuruName($item->guru, '-') }}</strong>
                <div class="sub-details">
                    <table>
                        <tr>
                            <td width="15px" style="vertical-align: top;">-</td>
                            <td width="115px">Nomor Surat</td>
                            <td width="15px">:</td>
                            <td>{{ $perjadin->letter_number ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td>-</td>
                            <td>Maksud Dinas</td>
                            <td>:</td>
                            <td>{{ $perjadin->activity_purpose }}</td>
                        </tr>
                        <tr>
                            <td>-</td>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td>{{ $depDateStr }}</td>
                        </tr>
                        <tr>
                            <td>-</td>
                            <td>Tujuan Dinas</td>
                            <td>:</td>
                            <td>{{ $perjadin->destination_name }}</td>
                        </tr>
                        <tr>
                            <td>-</td>
                            <td>Alamat Tujuan</td>
                            <td>:</td>
                            <td>{{ $perjadin->destination_address ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Rincian Pajak & Penerimaan Bersih --}}
    <div class="tax-table-container">
        <table class="tax-table">
            <tr>
                <td class="tax-label">Jumlah yang diterima</td>
                <td class="tax-colon">:</td>
                <td class="tax-val">Rp. {{ number_format($item->amount, 0, ',', '.') }},-</td>
            </tr>
            <tr>
                <td class="tax-label">PPN</td>
                <td class="tax-colon">:</td>
                <td class="tax-val">Rp. {{ number_format($item->ppn, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="tax-label tax-line">PPH 23</td>
                <td class="tax-colon tax-line">:</td>
                <td class="tax-val tax-line">Rp. {{ number_format($item->pph23, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="tax-label" style="padding-top: 5px;">Jumlah bersih yang diterima</td>
                <td class="tax-colon" style="padding-top: 5px;">:</td>
                <td class="tax-val" style="padding-top: 5px;">Rp. {{ number_format($item->net_amount, 0, ',', '.') }},-</td>
            </tr>
        </table>
    </div>

    {{-- Blok Tanda Tangan 3 Pihak --}}
    <table class="signature-table">
        <tr>
            <td style="vertical-align: top;">
                Setuju dibayar<br>
                Kepala {{ strtoupper($schoolName) }}
            </td>
            <td style="vertical-align: top;">
                Lunas dibayar,<br>
                Tanggal {{ $paymentDateStr }}<br>
                {{ $bendaharaTitle }}
            </td>
            <td style="vertical-align: top;">
                {{ $perjadin->city }}, {{ $paymentDateStr }}<br>
                Penerima
            </td>
        </tr>
        <tr>
            <td style="vertical-align: bottom;">
                <div class="sign-space"></div>
                <div class="sign-name">{{ $kepsekName }}</div>
            </td>
            <td style="vertical-align: bottom;">
                <div class="sign-space"></div>
                <div class="sign-name">{{ $bendaharaName }}</div>
            </td>
            <td style="vertical-align: bottom;">
                <div class="sign-space"></div>
                <div class="sign-name">{{ $formatGuruName($item->guru, '-') }}</div>
            </td>
        </tr>
    </table>
</div>
@endforeach

<script>
    // Automatically trigger print dialog on load if desired
    window.addEventListener('load', function() {
        // Optional: window.print();
    });
</script>
</body>
</html>

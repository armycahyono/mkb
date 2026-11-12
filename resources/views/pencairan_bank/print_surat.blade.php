<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Kuasa Bank - {{ $pencairanBank->letter_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm 15mm 20mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            line-height: 1.35;
        }

        .document-page {
            background-color: #fff;
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            padding: 15mm 20mm 20mm 20mm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .no-print-bar {
            background: #343a40;
            color: #fff;
            padding: 10px 20px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
            max-width: 210mm;
            font-family: Arial, sans-serif;
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

        .btn-excel {
            background: #198754;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-excel:hover {
            background: #157347;
            color: #fff;
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

        /* Kop Surat Resmi */
        .kop-header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 6px;
            margin-bottom: 20px;
            position: relative;
        }

        .kop-logo {
            position: absolute;
            left: 5px;
            top: 5px;
            width: 75px;
            height: auto;
        }

        .kop-header h3 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .kop-header h4 {
            margin: 2px 0;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-header h2 {
            margin: 2px 0 3px 0;
            font-size: 15pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kop-header p {
            margin: 0;
            font-size: 9pt;
            font-family: Arial, sans-serif;
        }

        /* Halaman 1 Styles */
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11pt;
        }

        .recipient-block {
            margin-bottom: 25px;
            font-size: 11pt;
            line-height: 1.4;
        }

        .perihal-line {
            margin-bottom: 25px;
            font-size: 11pt;
        }

        .paragraph {
            text-indent: 0;
            text-align: justify;
            margin-bottom: 18px;
            font-size: 11.5pt;
            line-height: 1.5;
        }

        .sig-table-hal1 {
            width: 100%;
            margin-top: 60px;
            border-collapse: collapse;
            text-align: center;
        }

        .sig-table-hal1 td {
            width: 50%;
            vertical-align: top;
            font-size: 11pt;
            line-height: 1.35;
        }

        .sig-space {
            height: 80px;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Halaman 2: Surat Kuasa */
        .page-kuasa .paragraph {
            margin-bottom: 11px;
            line-height: 1.42;
            font-size: 11pt;
        }

        .kuasa-title {
            text-align: center;
            margin-top: 5px;
            margin-bottom: 12px;
        }

        .kuasa-title h2 {
            margin: 0 0 2px 0;
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-decoration: underline;
        }

        .kuasa-title div {
            font-size: 10.5pt;
            font-weight: 500;
        }

        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10.5pt;
            line-height: 1.35;
        }

        .identity-table td {
            vertical-align: top;
            padding: 1.5px 0;
        }

        .center-block {
            text-align: center;
            margin: 10px 0;
            font-size: 10.5pt;
            font-weight: bold;
            line-height: 1.3;
        }

        .center-block h3 {
            margin: 10px 0 8px 0;
            font-size: 12.5pt;
            letter-spacing: 4px;
            text-decoration: underline;
            font-weight: bold;
        }

        .materai-box {
            border: 1px dashed #777;
            width: 78px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 7.5pt;
            color: #555;
            font-family: Arial, sans-serif;
            text-transform: uppercase;
            line-height: 1.25;
            text-align: center;
        }

        /* Halaman 3: Lampiran Tabel */
        .lampiran-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .lampiran-header h2 {
            margin: 0 0 4px 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .lampiran-header h3 {
            margin: 0 0 4px 0;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .lampiran-header div {
            font-size: 11pt;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10.5pt;
        }

        .data-table th {
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-tabular { font-family: "Courier New", Courier, monospace; }

        @media print {
            body {
                background: none;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .document-page {
                box-shadow: none;
                margin: 0;
                padding: 0 !important;
                width: 100%;
                min-height: auto;
                page-break-after: always;
            }

            .document-page:last-child {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
@php
    $routePrefix = $routePrefix ?? (($fundingSource ?? '') === 'BPOPP' ? 'bpopp' : 'bos');
@endphp

<div class="no-print-bar">
    <div>
        <strong>Dokumen Surat Kuasa Pemindahbukuan Bank Jatim</strong>
        <span style="font-size: 12px; margin-left: 10px; color: #ced4da;">(1 Set Lengkap: 3 Halaman Resmi)</span>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <a href="{{ route($routePrefix . '.pencairan-bank.export-excel', $pencairanBank->id) }}" class="btn-excel" title="Unduh format file Excel untuk diserahkan ke Bank Jatim">
            <i class="fas fa-file-excel"></i> Unduh Excel (Softcopy Bank)
        </a>
        <button type="button" class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        <button type="button" class="btn-close-window" onclick="window.close()">Tutup</button>
    </div>
</div>

@php
    $schoolName = $school ? ($school->name ?: 'SMK PGRI KRAS') : 'SMK PGRI KRAS';
    $foundation = $school ? ($school->foundation_name ?: 'YPLP DASMEN PGRI JAWA TIMUR') : 'YPLP DASMEN PGRI JAWA TIMUR';
    $schoolAddress = $school ? ($school->address ?: 'JL. RAYA DESA KRAS KEC. KRAS KAB. KEDIRI') : 'JL. RAYA DESA KRAS KEC. KRAS KAB. KEDIRI';
    $schoolPhone = $school ? ($school->phone ?: '(0354) 4740750') : '(0354) 4740750';
    $schoolEmail = $school ? ($school->email ?: 'smk_pgri_kras007@yahoo.co.id') : 'smk_pgri_kras007@yahoo.co.id';
    $schoolWebsite = $school ? ($school->website ?: 'www.smkpgrikras.sch.id') : 'www.smkpgrikras.sch.id';

    $kepsek = $pencairanBank->kepalaSekolah;
    $bendahara = $pencairanBank->bendahara;

    $kepsekName = $kepsek ? $kepsek->full_name_with_title : 'ENDANG PATMOWATI, S.Pd';
    $kepsekAddress = $kepsek ? ($kepsek->address ?: 'Dsn. Nglarangan, Rt 01/03, Ds. Selosari, Kec. Kandat, Kab. Kediri') : 'Dsn. Nglarangan, Rt 01/03, Ds. Selosari, Kec. Kandat, Kab. Kediri';

    $bendaharaName = $bendahara ? $bendahara->full_name_with_title : 'ARMY WIDHI CAHYONO, S.Pt';
    $bendaharaAddress = $bendahara ? ($bendahara->address ?: 'Rt. 03/04, Ds. Banjaranyar, Kec. Kras, Kab. Kediri') : 'Rt. 03/04, Ds. Banjaranyar, Kec. Kras, Kab. Kediri';

    $letterDateStr = $pencairanBank->letter_date ? $pencairanBank->letter_date->translatedFormat('d F Y') : date('d F Y');
    $bendaharaJabatan = ($fundingSource === 'BPOPP') ? 'Bendahara BPOPP SMKS PGRI KRAS' : 'Bendahara BOSP SMKS PGRI KRAS';
    $bendaharaSignTitle = ($fundingSource === 'BPOPP') ? 'Bendahara BPOPP' : 'Bendahara BOSP';
@endphp

{{-- =========================================================================
     HALAMAN 1: SURAT PENGANTAR KUASA PEMINDAHBUKUAN
     ========================================================================= --}}
<div class="document-page">
    {{-- Kop Surat --}}
    @if($school && $school->letterhead_portrait)
        <div style="margin-bottom: 20px;">
            <img src="{{ asset('storage/' . $school->letterhead_portrait) }}" style="width: 100%; height: auto;" alt="Kop Surat">
        </div>
    @else
        <div class="kop-header">
            @if($school && $school->logo_school)
                <img src="{{ asset('storage/' . $school->logo_school) }}" class="kop-logo" alt="Logo">
            @endif
            <h3>YAYASAN PEMBINA LEMBAGA PENDIDIKAN DASAR DAN MENENGAH</h3>
            <h4>PERSATUAN GURU REPUBLIK INDONESIA JAWA TIMUR</h4>
            <h4>({{ $foundation }})</h4>
            <h2>{{ strtoupper($schoolName) }}</h2>
            <p>{{ $schoolAddress }} ☎ {{ $schoolPhone }}</p>
            <p>Laman : {{ $schoolWebsite }} &nbsp; Surel : {{ $schoolEmail }}</p>
        </div>
    @endif

    {{-- Baris Nomor & Tanggal --}}
    <table style="width: 100%; margin-bottom: 20px; font-size: 11pt;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                Nomor: {{ $pencairanBank->letter_number }}
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                Kediri, {{ $letterDateStr }}
            </td>
        </tr>
    </table>

    {{-- Tujuan Surat --}}
    <div class="recipient-block">
        Kepada Yth.<br>
        <strong>Pimpinan {{ $pencairanBank->bank_name }} {{ $pencairanBank->bank_branch }}</strong><br>
        {{ $pencairanBank->bank_address }}<br>
        <u>KEDIRI</u>
    </div>

    {{-- Perihal --}}
    <div class="perihal-line">
        Perihal: <u>Kuasa Pemindahbukuan</u>
    </div>

    {{-- Isi Surat --}}
    <div class="paragraph">
        Sehubungan dengan adanya rekening kami di {{ $pencairanBank->bank_name }} {{ $pencairanBank->bank_branch }} atas nama <strong>{{ $pencairanBank->source_account_name }}</strong> nomor rekening <strong>{{ $pencairanBank->source_account_number }}</strong>, bersama ini kami mengajukan kuasa pemindahbukuan (Terlampir).
    </div>

    <div class="paragraph">
        Kami harapkan dengan adanya kuasa tersebut dapat dilakukan pemindahbukuan secara otomatis dari rekening Giro kami yang ada di {{ $pencairanBank->bank_name }} {{ $pencairanBank->bank_branch }}.
    </div>

    <div class="paragraph">
        Demikian atas kerjasama yang baik kami sampaikan terima kasih.
    </div>

    {{-- Tanda Tangan 2 Pihak --}}
    <table class="sig-table-hal1">
        <tr>
            <td>
                Kepala Sekolah<br>
                SMKS PGRI KRAS
                <div class="sig-space"></div>
                <div class="sig-name">{{ $kepsekName }}</div>
            </td>
            <td>
                {{ $bendaharaSignTitle }}<br>
                SMK PGRI KRAS
                <div class="sig-space"></div>
                <div class="sig-name">{{ $bendaharaName }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- =========================================================================
     HALAMAN 2: SURAT KUASA KHUSUS PEMINDAHBUKUAN (DENGAN MATERAI)
     ========================================================================= --}}
<div class="document-page page-kuasa">
    {{-- Kop Surat --}}
    @if($school && $school->letterhead_portrait)
        <div style="margin-bottom: 20px;">
            <img src="{{ asset('storage/' . $school->letterhead_portrait) }}" style="width: 100%; height: auto;" alt="Kop Surat">
        </div>
    @else
        <div class="kop-header">
            @if($school && $school->logo_school)
                <img src="{{ asset('storage/' . $school->logo_school) }}" class="kop-logo" alt="Logo">
            @endif
            <h3>YAYASAN PEMBINA LEMBAGA PENDIDIKAN DASAR DAN MENENGAH</h3>
            <h4>PERSATUAN GURU REPUBLIK INDONESIA JAWA TIMUR</h4>
            <h4>({{ $foundation }})</h4>
            <h2>{{ strtoupper($schoolName) }}</h2>
            <p>{{ $schoolAddress }} ☎ {{ $schoolPhone }}</p>
            <p>Laman : {{ $schoolWebsite }} &nbsp; Surel : {{ $schoolEmail }}</p>
        </div>
    @endif

    {{-- Judul Surat Kuasa --}}
    <div class="kuasa-title">
        <h2>SURAT KUASA</h2>
        <div>No. {{ $pencairanBank->kuasa_letter_number }}</div>
    </div>

    <div style="font-size: 10.5pt; margin-bottom: 6px;">Yang bertanda tangan dibawah ini :</div>

    {{-- Identitas Pemberi Kuasa --}}
    <table class="identity-table">
        <tr>
            <td width="25px"><strong>1.</strong></td>
            <td width="90px">Nama</td>
            <td width="15px">:</td>
            <td><strong>{{ $kepsekName }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>Kepala SMKS PGRI KRAS</td>
        </tr>
        <tr>
            <td></td>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $kepsekAddress }}</td>
        </tr>
        <tr><td colspan="4" style="height: 4px;"></td></tr>
        <tr>
            <td><strong>2.</strong></td>
            <td>Nama</td>
            <td>:</td>
            <td><strong>{{ $bendaharaName }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $bendaharaJabatan }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $bendaharaAddress }}</td>
        </tr>
    </table>

    <div style="font-size: 10.5pt; margin-bottom: 4px;">
        Bertindak untuk dan atas nama <strong>SMKS PGRI KRAS KEDIRI</strong>
    </div>

    <div style="font-size: 10.5pt; margin-bottom: 6px;">
        Dengan ini memberikan kuasa penuh yang tidak dapat dicabut kembali dengan Hak Subsitusi
    </div>

    <div class="center-block">
        {{ strtoupper($pencairanBank->bank_name) }}<br>
        {{ strtoupper($pencairanBank->bank_branch) }}<br>
        <span style="font-weight: normal; font-size: 10pt;">Berkedudukan di {{ $pencairanBank->bank_address }}</span>
        <h3>K H U S U S</h3>
    </div>

    <div class="paragraph" style="margin-top: 8px;">
        Untuk memindahbukukan dari rekening Giro kami yang ada di {{ $pencairanBank->bank_name }} {{ $pencairanBank->bank_branch }} dengan nomor rekening <strong>{{ $pencairanBank->source_account_number }}</strong> nama rekening <strong>{{ $pencairanBank->source_account_name }}</strong> untuk di limpahkan kepada rekening terlampir yang tidak terpisahkan dari surat kuasa ini dengan jumlah rekening <strong>{{ $pencairanBank->total_accounts }} ({{ strtolower($pencairanBank->total_accounts_words) }})</strong> dan total nominal <strong>Rp. {{ number_format($pencairanBank->total_nominal, 0, ',', '.') }},- ({{ strtolower($pencairanBank->total_terbilang) }})</strong> (sesuai lampiran).
    </div>

    <div class="paragraph">
        Demikian surat kuasa ini dibuat untuk dipergunakan sebagaimana mestinya. Segala akibat yang timbul atas pemberian kuasa ini menjadi tanggung jawab pemberi kuasa sepenuhnya dengan membebaskan {{ $pencairanBank->bank_name }} {{ $pencairanBank->bank_branch }} dari segala akibat tuntutan atau gugatan yang timbul dari transaksi rekening tersebut di atas.
    </div>

    {{-- Tanda Tangan Pemberi & Penerima Kuasa + Materai --}}
    <table style="width: 100%; border-collapse: collapse; margin-top: 25px; text-align: center; font-size: 10.5pt;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                Yang diberi kuasa<br>
                {{ strtoupper($pencairanBank->bank_name) }} {{ strtoupper($pencairanBank->bank_branch) }}
                <div style="height: 105px;"></div>
                <div style="width: 80%; border-bottom: 1px solid #000; margin: 0 auto;"></div>
            </td>
            <td width="50%" style="vertical-align: top;">
                Kediri, {{ $letterDateStr }}<br>
                Yang memberi kuasa,
                <table style="width: 100%; height: 105px; border-collapse: collapse; margin: 0;">
                    <tr>
                        <td style="width: 48%; vertical-align: middle; text-align: right; padding-right: 12px;">
                            <div class="materai-box" style="margin-left: auto;">
                                MATERAI<br>10.000
                            </div>
                        </td>
                        <td style="width: 52%; vertical-align: middle; text-align: left;">
                            {{-- Ruang luas untuk tanda tangan menimpa materai --}}
                        </td>
                    </tr>
                </table>
                <div class="sig-name">{{ $kepsekName }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- =========================================================================
     HALAMAN 3: LAMPIRAN DAFTAR SURAT PERINTAH PENCAIRAN DANA
     ========================================================================= --}}
<div class="document-page">
    <div class="lampiran-header">
        <h2>DAFTAR SURAT PERINTAH PENCAIRAN DANA</h2>
        <h3>SMKS PGRI KRAS</h3>
        <div>YANG TIDAK TERPISAHKAN DARI SURAT KUASA</div>
        <div>{{ $pencairanBank->kuasa_letter_number }}</div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="6%">NO</th>
                <th width="32%">NAMA PEMILIK REKENING</th>
                <th width="26%">NO REK BANK</th>
                <th width="18%">NILAI TRANSFER</th>
                <th width="18%">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pencairanBank->items as $idx => $item)
                @php
                    $desc = $item->description ?: '-';
                    if ($item->type === 'perjadin' || str_starts_with($desc, 'Perjadin:')) {
                        $desc = 'Perjadin';
                    }
                    if (str_starts_with($desc, 'Belanja: ')) {
                        $desc = substr($desc, 9);
                    }

                    // Resolusi nama pemilik rekening agar sesuai dengan rekening tujuan transfer
                    $recipientName = $item->recipient_name;
                    if ($item->type === 'perjadin' && $item->perjadinItem) {
                        $pItem = $item->perjadinItem;
                        $bank = $pItem->bankAccount ?? ($pItem->guru ? $pItem->guru->primaryBankAccount : null);
                        if ($bank && !empty($bank->account_holder)) {
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
                            if (!$accHolder) {
                                $accHolder = $supplier->account_holder;
                            }
                            if (!$accHolder && $supplier->relationLoaded('bankAccounts')) {
                                $accHolder = $supplier->bankAccounts->firstWhere('is_primary', true)?->account_holder
                                    ?? $supplier->bankAccounts->first()?->account_holder;
                            }
                            if ($accHolder) {
                                $recipientName = $accHolder;
                            }
                        }
                    } elseif ($item->utilityReceipt) {
                        $guru = $item->utilityReceipt->profile?->defaultReimburseGuru;
                        $bank = $guru?->primaryBankAccount;
                        if ($bank && ! empty($bank->account_holder)) {
                            $recipientName = $bank->account_holder;
                        } elseif ($guru) {
                            $recipientName = $guru->name;
                        }
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ strtoupper($recipientName) }}</td>
                    <td class="font-tabular" style="font-weight: bold;">{{ $item->account_number }}</td>
                    <td class="text-right font-tabular">{{ number_format($item->amount, 0, ',', '.') }}</td>
                    <td>{{ $desc }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="3" class="text-center" style="letter-spacing: 1px;">TOTAL</td>
                <td class="text-right font-tabular">{{ number_format($pencairanBank->total_nominal, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 35px; text-align: center; font-size: 11pt;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                Yang diberi kuasa<br>
                {{ strtoupper($pencairanBank->bank_name) }} {{ strtoupper($pencairanBank->bank_branch) }}
                <div style="height: 80px;"></div>
                <div style="width: 80%; border-bottom: 1px solid #000; margin: 0 auto;"></div>
            </td>
            <td width="50%" style="vertical-align: top;">
                Kediri, {{ $letterDateStr }}<br>
                Yang memberi kuasa,
                <div style="height: 80px;"></div>
                <div class="sig-name">{{ $bendaharaName }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>

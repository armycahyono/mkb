<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Kas Opname {{ $fundingSource }} - {{ $school ? $school->name : 'SMK PGRI KRAS' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; background-color: #fff; color: #000; }
        .table-print th, .table-print td { border: 1px solid #000 !important; padding: 4px 8px; vertical-align: middle; }
        .table-print thead th { background-color: #f2f2f2 !important; text-align: center; font-weight: bold; }
        .header-divider { border-bottom: 2px solid #000; margin-bottom: 15px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            @page { size: A4 portrait; margin: 15mm 20mm 20mm 20mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container my-2">
        <div class="no-print mb-3 text-right">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Cetak Berita Acara</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Tutup</button>
        </div>

        {{-- Kop / Judul --}}
        <div class="text-center mb-2">
            <h4 class="font-weight-bold mb-1" style="text-transform: uppercase;">BERITA ACARA PEMERIKSAAN KAS</h4>
            <h5 class="font-weight-bold mb-1" style="text-transform: uppercase;">(KAS OPNAME) DANA {{ $fundingSource }}</h5>
            <h6 class="font-weight-bold mb-1" style="text-transform: uppercase;">{{ $school ? $school->name : 'SMK PGRI KRAS KEDIRI' }}</h6>
        </div>
        <div class="header-divider"></div>

        <p style="text-align: justify; line-height: 1.5;" class="mb-3">
            Pada hari ini tanggal <strong>{{ \Carbon\Carbon::parse($opnameDate)->translatedFormat('d F Y') }}</strong>, telah dilakukan pemeriksaan fisik kas operasional (kas tunai) untuk dana <strong>{{ $fundingSource }}</strong> pada {{ $school ? $school->name : 'SMK PGRI KRAS' }}, dengan hasil rincian fisik uang di brankas sebagai berikut:
        </p>

        {{-- Tabel Rincian Pecahan Uang Fisik --}}
        <table class="table table-print w-100 mb-3">
            <thead>
                <tr>
                    <th width="40">NO</th>
                    <th>PECAHAN UANG</th>
                    <th width="140">JUMLAH (LEMBAR / KEPING)</th>
                    <th width="180">JUMLAH NILAI (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($denominations as $denom => $count)
                    @if($denom !== 'koin')
                        @php $sub = (int) $denom * $count; @endphp
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>Uang Kertas Rp {{ number_format($denom, 0, ',', '.') }}</td>
                            <td class="text-center">{{ number_format($count, 0, ',', '.') }} lembar</td>
                            <td class="text-right">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>Uang Logam / Koin</td>
                    <td class="text-center">-</td>
                    <td class="text-right">Rp {{ number_format($denominations['koin'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="font-weight-bold" style="background-color: #f8fafc;">
                    <th colspan="3" class="text-right">TOTAL UANG FISIK DI BRANKAS (A):</th>
                    <th class="text-right">Rp {{ number_format($totalFisik, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>

        {{-- Tabel Rekonsiliasi Saldo --}}
        <table class="table table-print w-100 mb-3">
            <tr>
                <td width="300"><strong>A. Total Fisik Uang di Brankas</strong></td>
                <td width="20">:</td>
                <td class="text-right font-weight-bold">Rp {{ number_format($totalFisik, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>B. Saldo Menurut Buku Pembantu Kas Tunai</strong></td>
                <td>:</td>
                <td class="text-right font-weight-bold">Rp {{ number_format($saldoBuku, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #f8fafc;">
                <td><strong>C. Selisih Kas (A - B)</strong></td>
                <td>:</td>
                <td class="text-right font-weight-bold">
                    @if($selisih === 0.0 || $selisih == 0)
                        <span class="text-success">Rp 0 (NIHIL / COCOK)</span>
                    @elseif($selisih > 0)
                        <span class="text-info">+ Rp {{ number_format($selisih, 0, ',', '.') }} (LEBIH KAS)</span>
                    @else
                        <span class="text-danger">- Rp {{ number_format(abs($selisih), 0, ',', '.') }} (KURANG KAS)</span>
                    @endif
                </td>
            </tr>
        </table>

        @if($notes)
            <div class="border p-2 mb-4">
                <strong>Catatan Pemeriksaan:</strong><br>
                <p class="mb-0">{{ $notes }}</p>
            </div>
        @endif

        <p style="text-align: justify; line-height: 1.5;" class="mb-4">
            Demikian Berita Acara Pemeriksaan Kas ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
        </p>

        {{-- Tanda Tangan 2 Pihak --}}
        <div class="row pt-2">
            <div class="col-6 text-center">
                <p style="margin-bottom: 70px;">
                    Yang Memeriksa,<br>
                    Kepala Sekolah
                </p>
                <p class="font-weight-bold mb-0">( <u>{{ $kepsekName }}</u> )</p>
            </div>
            <div class="col-6 text-center">
                <p style="margin-bottom: 70px;">
                    Yang Mengelola Kas,<br>
                    {{ $bendaharaTitle }}
                </p>
                <p class="font-weight-bold mb-0">( <u>{{ $bendaharaName }}</u> )</p>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Pembantu Kas Tunai {{ $fundingSource }} - {{ $school ? $school->name : 'SMK PGRI KRAS' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; background-color: #fff; color: #000; }
        .table-print th, .table-print td { border: 1px solid #000 !important; padding: 4px 6px; vertical-align: middle; }
        .table-print thead th { background-color: #f2f2f2 !important; text-align: center; font-weight: bold; }
        .header-divider { border-bottom: 2px solid #000; margin-bottom: 15px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            @page { size: A4 landscape; margin: 12mm 15mm 15mm 15mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid my-2">
        <div class="no-print mb-3 text-right">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Cetak Laporan</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Tutup</button>
        </div>

        {{-- Kop / Judul --}}
        <div class="text-center mb-2">
            <h4 class="font-weight-bold mb-1" style="text-transform: uppercase;">BUKU PEMBANTU KAS TUNAI</h4>
            <h5 class="font-weight-bold mb-1" style="text-transform: uppercase;">SUMBER DANA: {{ $fundingSource }}</h5>
            <h6 class="font-weight-bold mb-1" style="text-transform: uppercase;">{{ $school ? $school->name : 'SMK PGRI KRAS KEDIRI' }}</h6>
            <div class="small text-muted">
                Periode: {{ $month ? DateTime::createFromFormat('!m', $month)->format('F') . ' ' : '' }}{{ $year }}
            </div>
        </div>
        <div class="header-divider"></div>

        {{-- Tabel Kas --}}
        <table class="table table-print w-100 mb-4">
            <thead>
                <tr>
                    <th width="35">NO</th>
                    <th width="85">TANGGAL</th>
                    <th width="110">NO. BUKTI</th>
                    <th>URAIAN / KETERANGAN TRANSAKSI</th>
                    <th width="130">PENERIMAAN (Rp)</th>
                    <th width="130">PENGELUARAN (Rp)</th>
                    <th width="135">SALDO (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $runningSaldo = (float) ($saldoAwal ?? 0);
                    $totPenerimaan = 0;
                    $totPengeluaran = 0;
                @endphp
                @if(($saldoAwal ?? 0) != 0)
                    <tr style="background-color: #f8fafc; font-style: italic;">
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td><strong>Saldo Pindahan / Saldo Awal Kas Tunai</strong></td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-weight-bold">{{ number_format($saldoAwal, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @php
                    $hasPanjarSpj = false;
                @endphp
                @forelse($transactions as $idx => $t)
                    @php
                        $masuk = ($t->transaction_type === 'masuk') ? (float) $t->amount : 0;
                        $isPanjarChildNonReimburse = ($t->parent_kas_id && $t->category !== 'reimburse' && $t->transaction_type === 'keluar');
                        if ($isPanjarChildNonReimburse) {
                            $hasPanjarSpj = true;
                        }
                        $keluarFisik = ($t->transaction_type === 'keluar' && ! $isPanjarChildNonReimburse) ? (float) $t->amount : 0;
                        $runningSaldo += ($masuk - $keluarFisik);
                        $totPenerimaan += $masuk;
                        $totPengeluaran += $keluarFisik;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">{{ $t->transaction_date ? $t->transaction_date->format('d/m/Y') : '-' }}</td>
                        <td class="text-center">{{ $t->evidence_number ?: '-' }}</td>
                        <td>
                            {{ $t->description }}
                            @if($isPanjarChildNonReimburse)
                                <span class="badge badge-info text-dark" style="font-size: 80%;">[SPJ Kasbon]</span>
                            @endif
                            @if($t->person_name || $t->guru)
                                <br><small class="text-muted">({{ $t->guru ? $t->guru->name : $t->person_name }})</small>
                            @endif
                        </td>
                        <td class="text-right">{{ $masuk > 0 ? number_format($masuk, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">
                            @if($isPanjarChildNonReimburse)
                                <span title="Didanai dari Panjar">{{ number_format($t->amount, 0, ',', '.') }} *</span>
                            @elseif($keluarFisik > 0)
                                {{ number_format($keluarFisik, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right font-weight-bold">{{ number_format($runningSaldo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Tidak ada catatan transaksi kas pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-weight-bold" style="background-color: #f8fafc;">
                    <th colspan="4" class="text-right">TOTAL MUTASI KAS & SALDO AKHIR:</th>
                    <th class="text-right">Rp {{ number_format($totPenerimaan, 0, ',', '.') }}</th>
                    <th class="text-right">Rp {{ number_format($totPengeluaran, 0, ',', '.') }}</th>
                    <th class="text-right text-primary">Rp {{ number_format($runningSaldo, 0, ',', '.') }}</th>
                </tr>
                @if($hasPanjarSpj)
                    <tr style="font-size: 85%; background-color: #fff; color: #64748b;">
                        <td colspan="7">
                            <em>*) Catatan Akuntansi: Baris bertanda asteris (*) merupakan bukti pengeluaran riil nota SPJ yang didanai dari kasbon/panjar kerja yang telah dibukukan sebelumnya, sehingga mutasi kas fisik brankas tidak dipotong dua kali (mencegah double-counting).</em>
                        </td>
                    </tr>
                @endif
            </tfoot>
        </table>

        {{-- Blok Tanda Tangan --}}
        <div class="row pt-4">
            <div class="col-6 text-center">
                <p style="margin-bottom: 70px;">
                    Mengetahui,<br>
                    Kepala Sekolah
                </p>
                <p class="font-weight-bold mb-0">( <u>{{ $kepsekName }}</u> )</p>
            </div>
            <div class="col-6 text-center">
                <p style="margin-bottom: 70px;">
                    Kediri, {{ date('d F Y') }}<br>
                    {{ $bendaharaTitle }}
                </p>
                <p class="font-weight-bold mb-0">( <u>{{ $bendaharaName }}</u> )</p>
            </div>
        </div>
    </div>
</body>
</html>

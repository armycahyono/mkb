<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Barang - {{ $dokumentasi->no_nota }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            background-color: #fff;
            color: #000;
        }
        .header-divider {
            border-bottom: 3px double #000;
            margin-bottom: 12px;
        }
        .meta-table td {
            padding: 3px 6px;
            vertical-align: top;
        }
        .photo-card {
            border: 1px solid #333;
            padding: 6px;
            text-align: center;
            background: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .photo-card img {
            max-height: 175px;
            max-width: 100%;
            object-fit: contain;
            border: 1px solid #ddd;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm 12mm 15mm;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid my-2">
        {{-- Tombol Navigasi Cetak --}}
        <div class="no-print mb-3 text-right">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Cetak Dokumen</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Tutup</button>
        </div>

        {{-- Kop Surat Sekolah --}}
        <div class="text-center mb-1">
            <h4 class="font-weight-bold mb-0" style="text-transform: uppercase; letter-spacing: 0.5px;">
                {{ $school ? $school->name : 'SMK PGRI KRAS KEDIRI' }}
            </h4>
            <div class="small text-muted mb-1">
                {{ $school && $school->address ? $school->address : 'Jl. Raya Kras No. 123 Kediri - Jawa Timur' }}
                @if($school && $school->phone) | Telp: {{ $school->phone }} @endif
            </div>
            <h5 class="font-weight-bold mt-2 mb-0" style="text-transform: uppercase; text-decoration: underline;">
                LEMBAR BUKTI DOKUMENTASI FISIK PENGADAAN BARANG
            </h5>
            <div class="font-weight-bold small text-uppercase">
                BANTUAN OPERASIONAL SATUAN PENDIDIKAN (BOSP) TAHUN ANGGARAN {{ $dokumentasi->year }}
            </div>
        </div>
        <div class="header-divider"></div>

        {{-- Informasi Metadata Nota --}}
        <table class="meta-table w-100 mb-3" style="font-size: 10pt;">
            <tr>
                <td width="20%" class="font-weight-bold">Nomor Bukti / Nota</td>
                <td width="2%">:</td>
                <td width="38%"><strong>{{ $dokumentasi->no_nota }}</strong></td>
                <td width="18%" class="font-weight-bold">Tanggal Terima</td>
                <td width="2%">:</td>
                <td width="20%">{{ $dokumentasi->tanggal_terima ? $dokumentasi->tanggal_terima->format('d F Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Nama Rekanan / Toko</td>
                <td>:</td>
                <td>{{ $dokumentasi->nama_toko ?: '-' }}</td>
                <td class="font-weight-bold">Total Belanja</td>
                <td>:</td>
                <td><strong>{{ $dokumentasi->nominal ? 'Rp ' . number_format($dokumentasi->nominal, 0, ',', '.') : '-' }}</strong></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Uraian Barang</td>
                <td>:</td>
                <td>{{ $dokumentasi->uraian ?: '-' }}</td>
                <td class="font-weight-bold">Pengirim Foto</td>
                <td>:</td>
                <td>{{ $dokumentasi->sender_name }} @if($dokumentasi->sender_detail && $dokumentasi->sender_detail !== '-') ({{ $dokumentasi->sender_detail }}) @endif</td>
            </tr>
        </table>

        {{-- Grid Foto Dokumentasi Barang (Foto 1, Foto 2, Foto 3) --}}
        <div class="mb-4">
            <div class="row">
                @php
                    $fotos = $dokumentasi->fotos;
                    $totalFotos = max(3, $fotos->count());
                @endphp
                @for($i = 1; $i <= $totalFotos; $i++)
                    @php
                        $f = $fotos->firstWhere('urutan', $i);
                    @endphp
                    <div class="col-4 mb-2">
                        <div class="photo-card">
                            <div class="font-weight-bold text-center border-bottom pb-1 mb-2 bg-light small">
                                FOTO {{ $i }}
                            </div>
                            <div class="my-auto py-2 text-center" style="min-height: 180px;">
                                @if($f && Storage::disk('public')->exists($f->file_path))
                                    <img src="{{ Storage::disk('public')->url($f->file_path) }}" alt="Foto {{ $i }}">
                                @else
                                    <div class="text-muted p-4 border border-dashed rounded" style="border-style: dashed !important;">
                                        <i class="fas fa-camera fa-2x mb-2 text-secondary"></i><br>
                                        <small>(Foto Belum Dilampirkan)</small>
                                    </div>
                                @endif
                            </div>
                            <div class="border-top pt-1 mt-2 text-center small" style="min-height: 42px;">
                                @if($f && $f->keterangan)
                                    <strong>Keterangan:</strong> {{ $f->keterangan }}
                                @elseif($dokumentasi->uraian)
                                    <strong>Keterangan:</strong> {{ $dokumentasi->uraian }}
                                @else
                                    <span class="text-muted font-italic">(Dokumentasi Barang)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- Blok Tanda Tangan --}}
        <div class="row mt-4 pt-2">
            <div class="col-4 text-center">
                <p style="margin-bottom: 60px;">
                    Yang Menyerahkan,<br>
                    Rekanan / Penyedia Toko
                </p>
                <p class="font-weight-bold mb-0">( <u>{{ $dokumentasi->nama_toko ?: '........................' }}</u> )</p>
            </div>
            <div class="col-4 text-center">
                <p style="margin-bottom: 60px;">
                    Diterima Oleh,<br>
                    Pengurus / Penerima Barang
                </p>
                <p class="font-weight-bold mb-0">( <u>........................................</u> )</p>
            </div>
            <div class="col-4 text-center">
                <p style="margin-bottom: 60px;">
                    Mengetahui,<br>
                    Kepala Sekolah
                </p>
                <p class="font-weight-bold mb-0">( <u>{{ $kepsekName }}</u> )</p>
            </div>
        </div>
    </div>
</body>
</html>

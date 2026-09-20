@extends('adminlte::page')

@section('title', 'Rencana Barang yang diajukan')

@section('plugins.Select2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-file-invoice text-primary mr-1"></i> Rencana Pengajuan Barang</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pengajuan-barang.index') }}">Data Pokok</a></li>
                <li class="breadcrumb-item active">Rencana Pengajuan Barang</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route('pengajuan-barang.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, table, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    h1.page-title {
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }
    .content-header .breadcrumb {
        font-size: 0.76rem !important;
        margin-top: 2px;
    }
    .font-tabular {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    #tableItems th {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.5rem 0.45rem !important;
        background-color: #f8fafc;
        color: #334155;
        vertical-align: middle;
        white-space: nowrap;
    }
    #tableItems td {
        padding: 0.35rem 0.4rem !important;
        vertical-align: middle;
    }
    #tableItems .form-control-sm {
        font-size: 0.8125rem;
        height: calc(1.95rem + 2px);
        padding: 0.25rem 0.45rem;
    }

    .top-header-bar {
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    .select2-container .select2-selection--single {
        height: calc(1.95rem + 2px) !important;
        font-size: 0.8125rem !important;
        padding: 0.2rem 0.4rem;
        border-color: #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.95rem + 2px) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.45rem !important;
    }
</style>
@stop

@section('content')
<div class="container-fluid px-0">
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kesalahan input:</strong>
            <ul class="mb-0 mt-1 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Datalist untuk Satuan & Kegiatan -->
    <datalist id="list_satuan">
        @foreach($satuanList as $sat)
            <option value="{{ $sat }}"></option>
        @endforeach
    </datalist>

    <datalist id="list_kegiatan">
        @foreach($kegiatanList as $keg)
            <option value="{{ $keg }}"></option>
        @endforeach
    </datalist>

    <form action="{{ route('pengajuan-barang.store') }}" method="POST" id="formPengajuan">
        @csrf

        <div class="card shadow-sm border-0 mb-4">
            <!-- HEADER BAR FORMAT SESUAI PERMINTAAN:
                 Rencana Barang yang diajukan | Tahun anggaran : | Pemohon : | + tambah barang -->
            <div class="card-header top-header-bar py-2 px-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                    <div class="d-flex align-items-center">
                        <h2 class="card-title font-weight-bold text-dark mb-0" style="font-size: 1.05rem;">
                            <i class="fas fa-list-check text-primary mr-1"></i> Rencana Barang yang diajukan
                        </h2>
                    </div>

                    <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                        <!-- Tahun Anggaran -->
                        <div class="d-inline-flex align-items-center">
                            <label for="tahun_anggaran" class="mb-0 mr-1 font-weight-bold text-secondary text-nowrap" style="font-size: 0.85rem;">
                                Tahun anggaran :
                            </label>
                            <input type="number" name="tahun_anggaran" id="tahun_anggaran" 
                                   class="form-control form-control-sm text-center font-weight-bold font-tabular @error('tahun_anggaran') is-invalid @enderror" 
                                   style="width: 80px;" 
                                   value="{{ old('tahun_anggaran', date('Y')) }}" required>
                        </div>

                        <!-- Pemohon (Dropdown Guru) -->
                        <div class="d-inline-flex align-items-center">
                            <label for="guru_id" class="mb-0 mr-1 font-weight-bold text-secondary text-nowrap" style="font-size: 0.85rem;">
                                Pemohon :
                            </label>
                            <select name="guru_id" id="guru_id" class="form-control form-control-sm select2 @error('guru_id') is-invalid @enderror" style="min-width: 220px; max-width: 320px;" required>
                                <option value="">-- Pilih Guru / Pemohon --</option>
                                @foreach($gurus as $guru)
                                    <option value="{{ $guru->id }}" {{ old('guru_id') == $guru->id ? 'selected' : '' }}>
                                        {{ $guru->full_name_with_title ?? $guru->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- + Tambah Barang -->
                        <button type="button" class="btn btn-sm btn-success shadow-sm font-weight-bold text-nowrap" id="btnAddRow">
                            <i class="fas fa-plus mr-1"></i> + tambah barang
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABEL SEDERHANA -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="tableItems">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">NO</th>
                                <th style="min-width: 170px;">NAMA BARANG <span class="text-danger">*</span></th>
                                <th style="min-width: 160px;">SPESIFIKASI <span class="text-danger">*</span></th>
                                <th style="width: 75px;" class="text-center">VOL <span class="text-danger">*</span></th>
                                <th style="width: 95px;" class="text-center">SATUAN <span class="text-danger">*</span></th>
                                <th style="width: 120px;" class="text-right">HARGA <span class="text-danger">*</span></th>
                                <th style="width: 130px;" class="text-right">JUMLAH</th>
                                <th style="min-width: 170px;">KEGIATAN <span class="text-danger">*</span></th>
                                <th style="width: 125px;">BULAN</th>
                                <th style="min-width: 130px;">KETERANGAN</th>
                                <th style="width: 45px;" class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyItems">
                            @php
                                $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                $oldItems = old('items', [
                                    [
                                        'nama_barang' => '',
                                        'spesifikasi' => '',
                                        'volume' => 1,
                                        'satuan' => '',
                                        'harga' => 0,
                                        'jumlah' => 0,
                                        'kegiatan' => '',
                                        'bulan_pakai' => '',
                                        'keterangan' => ''
                                    ]
                                ]);
                            @endphp

                            @foreach($oldItems as $idx => $item)
                                <tr class="item-row" data-index="{{ $idx }}">
                                    <td class="text-center align-middle font-weight-bold font-tabular row-number">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][nama_barang]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Nama barang..." 
                                               value="{{ $item['nama_barang'] ?? '' }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][spesifikasi]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Spesifikasi / merk..." 
                                               value="{{ $item['spesifikasi'] ?? '' }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][volume]" 
                                               class="form-control form-control-sm font-tabular text-center row-volume" 
                                               placeholder="1" value="{{ $item['volume'] ?? 1 }}" required>
                                    </td>
                                    <td>
                                        <input type="text" list="list_satuan" name="items[{{ $idx }}][satuan]" 
                                               class="form-control form-control-sm text-center row-satuan" 
                                               placeholder="Pilih/Ketik" 
                                               value="{{ $item['satuan'] ?? '' }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][harga]" 
                                               class="form-control form-control-sm font-tabular text-right row-harga" 
                                               placeholder="0" value="{{ isset($item['harga']) && $item['harga'] > 0 ? number_format((float)$item['harga'], 0, ',', '.') : 0 }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][jumlah]" 
                                               class="form-control form-control-sm font-tabular text-right row-jumlah bg-light font-weight-bold text-primary" 
                                               value="{{ isset($item['jumlah']) && $item['jumlah'] > 0 ? number_format((float)$item['jumlah'], 0, ',', '.') : 0 }}" readonly>
                                    </td>
                                    <td>
                                        <input type="text" list="list_kegiatan" name="items[{{ $idx }}][kegiatan]" 
                                               class="form-control form-control-sm row-kegiatan" 
                                               placeholder="Pilih/Ketik kegiatan" 
                                               value="{{ $item['kegiatan'] ?? '' }}" required>
                                    </td>
                                    <td>
                                        <select name="items[{{ $idx }}][bulan_pakai]" class="form-control form-control-sm row-bulan">
                                            <option value="">- Pilih Bulan -</option>
                                            @foreach($months as $m)
                                                <option value="{{ $m }}" {{ ($item['bulan_pakai'] ?? '') == $m ? 'selected' : '' }}>
                                                    {{ $m }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][keterangan]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Keterangan..." 
                                               value="{{ $item['keterangan'] ?? '' }}">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-xs btn-outline-danger btn-remove-row" title="Hapus baris">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="6" class="text-right font-weight-bold align-middle py-2">
                                    TOTAL ESTIMASI:
                                </td>
                                <td class="text-right font-weight-bold font-tabular text-success align-middle py-2" id="tableFooterTotal" style="font-size: 0.95rem;">
                                    Rp 0
                                </td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- FOOTER: TOTAL ESTIMASI & TOMBOL BATAL - SIMPAN -->
            <div class="card-footer bg-white border-top py-3 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 15px;">
                <div class="d-flex align-items-center">
                    <span class="text-muted font-weight-bold mr-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.9rem;">
                        TOTAL ESTIMASI :
                    </span>
                    <span class="font-tabular font-weight-bold text-success" id="grandTotalDisplay" style="font-size: 1.35rem;">
                        Rp 0
                    </span>
                </div>
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <a href="{{ route('pengajuan-barang.index') }}" class="btn btn-secondary px-4 shadow-sm">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm font-weight-bold" id="btnSubmitForm">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@stop

@section('js')
<script>
    const monthsList = @json($months);

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(number);
    }

    function formatThousands(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().replace(/[^0-9]/g, '');
        if (!str) return '';
        return parseInt(str, 10).toLocaleString('id-ID');
    }

    function parseThousands(val) {
        if (!val) return 0;
        let str = val.toString().replace(/[^0-9]/g, '');
        return str ? parseFloat(str) : 0;
    }

    function updateCalculations() {
        let grandTotal = 0;
        const rows = document.querySelectorAll('#tbodyItems .item-row');

        rows.forEach((row, index) => {
            const numTd = row.querySelector('.row-number');
            if (numTd) numTd.textContent = (index + 1);

            const volInput = row.querySelector('.row-volume');
            const hargaInput = row.querySelector('.row-harga');
            const jumlahInput = row.querySelector('.row-jumlah');

            const volume = parseFloat(volInput ? volInput.value.replace(/[^0-9.]/g, '') : 0) || 0;
            const harga = parseThousands(hargaInput ? hargaInput.value : 0);
            const subtotal = Math.round(volume * harga);

            if (jumlahInput) jumlahInput.value = formatThousands(subtotal);
            grandTotal += subtotal;

            const deleteBtn = row.querySelector('.btn-remove-row');
            if (deleteBtn) {
                deleteBtn.disabled = (rows.length === 1);
            }
        });

        const totalFooter = document.getElementById('tableFooterTotal');
        if (totalFooter) totalFooter.textContent = formatRupiah(grandTotal);

        const totalDisplay = document.getElementById('grandTotalDisplay');
        if (totalDisplay) totalDisplay.textContent = formatRupiah(grandTotal);
    }

    function createNewTableRow(index) {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.setAttribute('data-index', index);

        let monthOptions = '<option value="">- Pilih Bulan -</option>';
        monthsList.forEach(m => {
            monthOptions += `<option value="${m}">${m}</option>`;
        });

        tr.innerHTML = `
            <td class="text-center align-middle font-weight-bold font-tabular row-number">
                ${index + 1}
            </td>
            <td>
                <input type="text" name="items[${index}][nama_barang]" 
                       class="form-control form-control-sm" 
                       placeholder="Nama barang..." required>
            </td>
            <td>
                <input type="text" name="items[${index}][spesifikasi]" 
                       class="form-control form-control-sm" 
                       placeholder="Spesifikasi / merk..." required>
            </td>
            <td>
                <input type="text" name="items[${index}][volume]" 
                       class="form-control form-control-sm font-tabular text-center row-volume" 
                       placeholder="1" value="1" required>
            </td>
            <td>
                <input type="text" list="list_satuan" name="items[${index}][satuan]" 
                       class="form-control form-control-sm text-center row-satuan" 
                       placeholder="Pilih/Ketik" required>
            </td>
            <td>
                <input type="text" name="items[${index}][harga]" 
                       class="form-control form-control-sm font-tabular text-right row-harga" 
                       placeholder="0" value="0" required>
            </td>
            <td>
                <input type="text" name="items[${index}][jumlah]" 
                       class="form-control form-control-sm font-tabular text-right row-jumlah bg-light font-weight-bold text-primary" 
                       value="0" readonly>
            </td>
            <td>
                <input type="text" list="list_kegiatan" name="items[${index}][kegiatan]" 
                       class="form-control form-control-sm row-kegiatan" 
                       placeholder="Pilih/Ketik kegiatan" required>
            </td>
            <td>
                <select name="items[${index}][bulan_pakai]" class="form-control form-control-sm row-bulan">
                    ${monthOptions}
                </select>
            </td>
            <td>
                <input type="text" name="items[${index}][keterangan]" 
                       class="form-control form-control-sm" 
                       placeholder="Keterangan...">
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-xs btn-outline-danger btn-remove-row" title="Hapus baris">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        return tr;
    }

    function reindexRows() {
        const rows = document.querySelectorAll('#tbodyItems .item-row');
        rows.forEach((row, newIndex) => {
            row.setAttribute('data-index', newIndex);
            const numTd = row.querySelector('.row-number');
            if (numTd) numTd.textContent = (newIndex + 1);

            row.querySelectorAll('input, select, textarea').forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.startsWith('items[')) {
                    input.setAttribute('name', name.replace(/^items\[\d+\]/, `items[${newIndex}]`));
                }
            });
        });
    }

    function attachEventListeners() {
        const tbody = document.getElementById('tbodyItems');

        tbody.addEventListener('input', function(e) {
            if (e.target.classList.contains('row-harga')) {
                let originalValue = e.target.value;
                let originalSelection = e.target.selectionStart;
                let originalLength = originalValue.length;

                let rawVal = originalValue.replace(/[^0-9]/g, '');
                let formatted = rawVal ? parseInt(rawVal, 10).toLocaleString('id-ID') : '';
                e.target.value = formatted;

                let newLength = formatted.length;
                let newSelection = originalSelection + (newLength - originalLength);
                if (newSelection >= 0 && newSelection <= newLength) {
                    e.target.setSelectionRange(newSelection, newSelection);
                }
                updateCalculations();
            } else if (e.target.classList.contains('row-volume')) {
                updateCalculations();
            }
        });

        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-row');
            if (btn) {
                const rows = document.querySelectorAll('#tbodyItems .item-row');
                if (rows.length > 1) {
                    const tr = btn.closest('.item-row');
                    tr.remove();
                    reindexRows();
                    updateCalculations();
                } else {
                    alert('Minimal harus ada 1 barang dalam pengajuan.');
                }
            }
        });

        const btnAdd = document.getElementById('btnAddRow');
        if (btnAdd) {
            btnAdd.addEventListener('click', function() {
                const tbody = document.getElementById('tbodyItems');
                const currentIndex = tbody.querySelectorAll('.item-row').length;
                const newRow = createNewTableRow(currentIndex);
                tbody.appendChild(newRow);
                updateCalculations();

                const namaInput = newRow.querySelector('input[name$="[nama_barang]"]');
                if (namaInput) namaInput.focus();
            });
        }

        const form = document.getElementById('formPengajuan');
        if (form) {
            form.addEventListener('submit', function() {
                document.querySelectorAll('.row-harga').forEach(function(inp) {
                    inp.value = inp.value.replace(/\./g, '');
                });
                document.querySelectorAll('.row-jumlah').forEach(function(inp) {
                    inp.value = inp.value.replace(/\./g, '');
                });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#guru_id').select2({
                placeholder: '-- Pilih Guru / Pemohon --',
                allowClear: true,
                width: 'resolve'
            });
        }
        attachEventListeners();
        updateCalculations();
    });
</script>
@stop

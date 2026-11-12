{{-- Modal Import Excel Perjadin --}}
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <form id="importPerjadinForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2 px-3 border-bottom">
                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 0.95rem;">
                        <i class="fas fa-file-excel text-success mr-1"></i> Import Perjalanan Dinas Massal (Excel)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <div class="alert alert-info py-2 px-3 mb-3 small">
                        <h6 class="font-weight-bold mb-1" style="font-size: 0.82rem;"><i class="fas fa-info-circle mr-1"></i> Cara Kerja & Kemudahan Import Excel:</h6>
                        <ul class="mb-0 pl-3" style="line-height: 1.5;">
                            <li>Satu file Excel dapat memuat <strong>banyak tanggal, banyak instansi tujuan, dan banyak guru sekaligus</strong>.</li>
                            <li>Baris data dengan <strong>Tanggal, Tujuan Dinas, dan Maksud yang sama</strong> otomatis dikelompokkan menjadi <strong>1 Surat Perjadin</strong>.</li>
                            <li><strong>Dukungan Merge Cells:</strong> Jika tanggal atau instansi tujuan sama untuk beberapa guru, sel cukup diisi pada baris pertama atau di-merge.</li>
                            <li><strong>Pencocokan Guru Fleksibel:</strong> Mendukung NIP, Nama Lengkap dengan gelar (misal: <code>Siti Aminah, M.Pd.</code>), maupun nama dasar tanpa gelar.</li>
                            <li><strong>Format Tanggal Bebas:</strong> Mendukung format standar (<code>YYYY-MM-DD</code>), format Indonesia (<code>DD/MM/YYYY</code>), maupun teks bulan (<code>3 Mei 2026</code>).</li>
                            <li>Template memuat <strong>3 Sheet</strong>: <em>Sheet 1</em> (Form Input), <em>Sheet 2</em> (Referensi Guru & Rekening Bank), dan <em>Sheet 3</em> (Referensi Instansi & Alamat Tujuan).</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                        <div>
                            <span class="font-weight-bold small text-dark d-block">Format Template Excel Resmi</span>
                            <small class="text-muted">Dilengkapi contoh nyata, format angka otomatis, daftar 54 guru & rekening, serta daftar instansi</small>
                        </div>
                        <a href="{{ route($routePrefix . '.perjadin.template') }}" class="btn btn-sm btn-outline-success font-weight-bold shadow-sm">
                            <i class="fas fa-download mr-1"></i> Unduh Format Template
                        </a>
                    </div>

                    @php
                        $activeYear = ($selectedYear && $selectedYear !== 'all') ? (int) $selectedYear : (int) session('fiscal_year', date('Y'));
                        $activeKkItems = isset($kertasKerjaItems) ? $kertasKerjaItems->where('year', $activeYear) : collect([]);
                    @endphp

                    @if(isset($kertasKerjaItems) && $kertasKerjaItems->count() > 0)
                    <div class="p-3 mb-3 rounded border" style="background-color: #f0fdf4; border-color: #86efac !important;">
                        <div class="form-group mb-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="small font-weight-bold text-success mb-0">
                                    <i class="fas fa-link mr-1"></i> Hubungkan ke Kertas Kerja (BOS) <small class="text-muted font-weight-normal">(Opsional)</small>:
                                </label>
                                <span class="badge bg-success text-white font-weight-normal" style="font-size: 0.72rem;">
                                    <i class="fas fa-calendar-alt mr-1"></i> Tahun Anggaran: {{ $activeYear }}
                                </span>
                            </div>
                            <select name="bos_kertas_kerja_item_id" id="importKertasKerjaItemId" class="form-control form-control-sm font-weight-500">
                                <option value="">-- Tidak Dihubungkan / Input Bebas --</option>
                                @foreach($activeKkItems as $kk)
                                    @php
                                        $remaining = isset($kk->available_volume) ? (float) $kk->available_volume : max(0, (float) $kk->volume - (float) $kk->realisasis->sum('volume'));
                                        $isExhausted = ($remaining <= 0.001);
                                    @endphp
                                    <option value="{{ $kk->id }}" {{ $isExhausted ? 'disabled' : '' }}>
                                        [{{ $kk->month }} {{ $kk->year }}] {{ $kk->kegiatan }} &mdash; {{ $kk->description }}
                                        @if($isExhausted)
                                            (⛔ HABIS - Sisa: 0 {{ $kk->unit }})
                                        @else
                                            (Tarif: Rp {{ number_format($kk->price, 0, ',', '.') }} / {{ $kk->unit }} | Sisa: {{ $remaining }} {{ $kk->unit }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="text-muted d-block mt-1 font-italic" style="font-size: 0.74rem;">
                            <i class="fas fa-info-circle mr-1"></i> Seluruh kegiatan perjalanan dinas dari file Excel ini akan otomatis dikaitkan ke pos Kertas Kerja yang dipilih dan mengisi kode rekening belanjanya.
                        </small>
                    </div>
                    @endif

                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Pilih File Excel (.xlsx / .xls / .csv) <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" name="excel_file" id="excelFilePerjadin" class="custom-file-input form-control-sm" accept=".xlsx,.xls,.csv" required>
                            <label class="custom-file-label small" for="excelFilePerjadin">Pilih file excel...</label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.74rem;">
                            Maksimal ukuran file: 10 MB. Format yang didukung: Microsoft Excel (.xlsx, .xls) atau CSV.
                        </small>
                    </div>

                    <div id="importAlertPerjadin" class="alert alert-danger py-2 px-3 small d-none mt-2"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3" id="btnSubmitImportPerjadin">
                        <i class="fas fa-upload mr-1"></i> Upload & Proses Import
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Buka Modal
    const btnOpen = document.getElementById('btnOpenImport');
    if (btnOpen) {
        btnOpen.addEventListener('click', function() {
            const form = document.getElementById('importPerjadinForm');
            if (form) form.reset();
            const alertBox = document.getElementById('importAlertPerjadin');
            if (alertBox) alertBox.classList.add('d-none');
            const fileLabel = document.querySelector('#importModal .custom-file-label');
            if (fileLabel) fileLabel.textContent = 'Pilih file excel...';

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('importModal')).show();
            } else if (typeof $ !== 'undefined') {
                $('#importModal').modal('show');
            }
        });
    }

    // Update label file saat dipilih
    const fileInput = document.getElementById('excelFilePerjadin');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Pilih file excel...';
            const label = document.querySelector('#importModal .custom-file-label');
            if (label) label.textContent = fileName;
        });
    }

    // Submit Form Import
    const form = document.getElementById('importPerjadinForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const file = fileInput.files[0];
            if (!file) {
                alert('Pilih file Excel terlebih dahulu.');
                return;
            }

            const alertBox = document.getElementById('importAlertPerjadin');
            alertBox.classList.add('d-none');

            const submitBtn = document.getElementById('btnSubmitImportPerjadin');
            const origText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Mengimpor Data...';

            const formData = new FormData(form);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Memproses File Excel',
                    html: 'Sedang membaca, mengelompokkan kegiatan dinas, dan menyimpan ke database...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            fetch("{{ route($routePrefix . '.perjadin.import') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
            .then(res => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;

                if (!res.ok || !res.body.success) {
                    const msg = res.body.message || 'Terjadi kesalahan saat memproses file Excel.';
                    alertBox.textContent = msg;
                    alertBox.classList.remove('d-none');

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengimpor',
                            text: msg
                        });
                    }
                    return;
                }

                // Sukses
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(document.getElementById('importModal'))?.hide();
                } else if (typeof $ !== 'undefined') {
                    $('#importModal').modal('hide');
                }

                if (typeof Swal !== 'undefined') {
                    let detailHtml = `<p class="mb-2">${res.body.message}</p>`;
                    if (res.body.results && res.body.results.summary && res.body.results.summary.length > 0) {
                        detailHtml += `<div class="text-left small bg-light p-2 rounded border" style="max-height: 180px; overflow-y: auto;"><strong>Rincian Kegiatan:</strong><ul class="mb-0 pl-3 mt-1">`;
                        res.body.results.summary.forEach(g => {
                            detailHtml += `<li><strong>${g.destination}</strong> (${g.date}) — ${g.guru_count} Guru <span class="text-muted font-tabular">[BKU: ${g.bku}]</span></li>`;
                        });
                        detailHtml += `</ul></div>`;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Import Berhasil!',
                        html: detailHtml,
                        confirmButtonText: 'Buka Daftar Perjadin'
                    }).then(() => {
                        window.location.href = res.body.redirect || "{{ route($routePrefix . '.perjadin.index') }}";
                    });
                } else {
                    alert(res.body.message);
                    window.location.href = res.body.redirect || "{{ route($routePrefix . '.perjadin.index') }}";
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
                alertBox.textContent = 'Gagal mengirim permintaan: ' + err.message;
                alertBox.classList.remove('d-none');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Koneksi',
                        text: err.message
                    });
                }
            });
        });
    }
});
</script>

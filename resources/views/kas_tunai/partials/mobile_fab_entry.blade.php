{{-- Floating Action Button for Mobile --}}
<div class="mobile-fab-container d-md-none">
    <button type="button" class="mobile-fab-btn" id="btnOpenMobileQuickEntry" title="Ambil Foto Nota & Catat Belanja">
        <i class="fas fa-camera"></i>
    </button>
</div>

{{-- Mobile Quick Receipt Entry Modal --}}
<div class="modal fade" id="modalMobileQuickReceipt" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-2 px-3 d-flex align-items-center justify-content-between">
                <h5 class="modal-title font-weight-bold m-0" style="font-size: 1rem;">
                    <i class="fas fa-camera mr-1"></i> Catat Nota Belanja (Mobile)
                </h5>
                <button type="button" class="close text-white btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formMobileQuickReceipt" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-3">
                    {{-- 1. Camera Capture Box --}}
                    <div class="mb-3">
                        <label class="small font-weight-bold text-muted mb-1 d-block">
                            <i class="fas fa-image mr-1 text-primary"></i> Foto Nota / Kuitansi Toko
                        </label>
                        <input type="file" id="mobileReceiptInput" name="receipt_file" accept="image/*" capture="environment" class="d-none">
                        
                        <div id="mobileCameraPlaceholder" onclick="document.getElementById('mobileReceiptInput').click();" 
                             class="p-3 text-center border border-primary rounded bg-light" 
                             style="cursor: pointer; border-style: dashed !important; border-width: 2px !important;">
                            <i class="fas fa-camera fa-2x text-primary mb-1 d-block"></i>
                            <strong class="d-block text-dark" style="font-size: 0.95rem;">Jepret Foto Nota Sekarang</strong>
                            <small class="text-muted">Ketuk di sini untuk membuka kamera HP atau galeri</small>
                        </div>

                        {{-- Preview Box & Scan Controls --}}
                        <div id="mobileCameraPreviewBox" class="text-center mt-2" style="display: none;">
                            <div class="position-relative d-inline-block w-100">
                                <img id="mobilePreviewImage" src="#" alt="Preview Nota" class="img-fluid rounded border shadow-xs" style="max-height: 220px; object-fit: contain;">
                                <button type="button" class="btn btn-xs btn-danger position-absolute" id="btnRemoveMobilePhoto" style="top: 8px; right: 8px;">
                                    <i class="fas fa-times mr-1"></i> Ganti
                                </button>
                            </div>

                            <div class="mt-2" id="scanActionContainer">
                                <button type="button" class="btn btn-sm btn-primary w-100 font-weight-bold shadow-xs py-2" id="btnTriggerOcrScan" style="background: linear-gradient(135deg, #4f46e5, #3b82f6); border: none;">
                                    <i class="fas fa-magic mr-1"></i> Pindai Isi Nota Otomatis (AI / OCR)
                                </button>
                            </div>

                            <div id="mobileScanStatus" class="alert alert-info py-2 px-3 small text-center mt-2 mb-0" style="display: none;">
                                <i class="fas fa-spinner fa-spin mr-1"></i> <span id="scanStatusText">Menganalisis isi nota...</span>
                            </div>

                            <div id="ocrResultBadge" class="mt-2 text-center" style="display: none;">
                                <span class="badge" id="ocrBadgeContent" style="font-size: 0.8rem; padding: 5px 10px;"></span>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Nominal Belanja Riil --}}
                    <div class="mb-3">
                        <label class="small font-weight-bold text-muted mb-1 d-block">
                            <i class="fas fa-money-bill-wave mr-1 text-success"></i> Total Belanja sesuai Nota <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light font-weight-bold text-dark">Rp</span>
                            <input type="text" name="amount" id="mobileInputAmount" 
                                   class="form-control form-control-lg font-weight-bold text-dark rupiah-input" 
                                   inputmode="numeric" placeholder="0" autocomplete="off" required
                                   style="font-size: 1.35rem; text-align: right;">
                        </div>
                    </div>

                    {{-- 3. Jenis Transaksi / Kasbon --}}
                    <div class="mb-3">
                        <label class="small font-weight-bold text-muted mb-1 d-block">
                            <i class="fas fa-user-check mr-1 text-info"></i> Penerima Uang / Jenis Transaksi <span class="text-danger">*</span>
                        </label>
                        <select name="target_type" id="mobileSelectTarget" class="form-select form-select-sm font-weight-bold">
                            @if(isset($panjarList) && count($panjarList) > 0)
                                <optgroup label="Selesaikan Tanggungan Kasbon:">
                                    @foreach($panjarList as $p)
                                        <option value="settle_{{ $p['id'] }}" data-amount="{{ $p['amount'] }}">
                                            Selesaikan: {{ $p['person_name'] }} (Kasbon: {{ $p['formatted_amount'] }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="Belanja Langsung / Operasional:">
                                <option value="direct_belanja_tunai" selected>Belanja Tunai Langsung (Kas Keluar)</option>
                                <option value="direct_talangan_belanja">Talangan Toko (SIPLah Offline)</option>
                                <option value="direct_operasional_laporan">Operasional / Transport / SPJ</option>
                            </optgroup>
                        </select>
                    </div>

                    {{-- 4. Keterangan Belanja & Nama Toko --}}
                    <div class="mb-3">
                        <label class="small font-weight-bold text-muted mb-1 d-block">
                            <i class="fas fa-store mr-1 text-secondary"></i> Toko / Uraian Belanja <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="description" id="mobileInputDescription" class="form-control form-control-sm" placeholder="Contoh: Beli ATK di Toko Makmur" required>
                    </div>

                    {{-- 5. Nomor Nota (Opsional) --}}
                    <div class="mb-2">
                        <label class="small font-weight-bold text-muted mb-1 d-block">
                            <i class="fas fa-receipt mr-1 text-muted"></i> Nomor Nota / Kuitansi (Opsional)
                        </label>
                        <input type="text" name="evidence_number" id="mobileInputEvidence" class="form-control form-control-sm" placeholder="Contoh: Nota #889">
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-4 shadow-sm" id="btnSubmitMobileQuick">
                        <i class="fas fa-check-circle mr-1"></i> Simpan Nota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Load Tesseract.js for Offline Client-Side OCR Fallback --}}
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Open Mobile Quick Entry Modal
    $('#btnOpenMobileQuickEntry').on('click', function(e) {
        e.preventDefault();
        $('#formMobileQuickReceipt')[0].reset();
        $('#mobileCameraPreviewBox').hide();
        $('#mobileCameraPlaceholder').show();
        $('#ocrResultBadge').hide();
        $('#mobileScanStatus').hide();
        $('#scanActionContainer').show();
        showKasModal('#modalMobileQuickReceipt');
    });

    let compressedReceiptFile = null;

    // Helper to compress large smartphone photos (e.g. 10MB -> 400KB)
    function compressReceiptImage(file, callback) {
        if (!file || !file.type.startsWith('image/')) {
            callback(file, null);
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const maxDim = 1600;
                let width = img.width;
                let height = img.height;

                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(function(blob) {
                    if (blob && (blob.size < file.size || file.size > 2 * 1024 * 1024)) {
                        const newFile = new File([blob], 'nota_' + Date.now() + '.jpg', {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        callback(newFile, canvas.toDataURL('image/jpeg', 0.85));
                    } else {
                        callback(file, e.target.result);
                    }
                }, 'image/jpeg', 0.85);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // Preview photo when selected from camera/gallery
    $('#mobileReceiptInput').on('change', function(e) {
        const file = this.files[0];
        if (file) {
            $('#mobileCameraPlaceholder').hide();
            $('#mobileScanStatus').show();
            $('#scanStatusText').html('<i class="fas fa-spinner fa-spin mr-1"></i> Mempersiapkan foto...');

            compressReceiptImage(file, function(optimizedFile, previewUrl) {
                compressedReceiptFile = optimizedFile;
                if (previewUrl) {
                    $('#mobilePreviewImage').attr('src', previewUrl);
                }
                $('#mobileCameraPreviewBox').show();
                $('#ocrResultBadge').hide();
                $('#scanActionContainer').show();

                // Auto-trigger scan for seamless experience
                runSmartReceiptScan();
            });
        }
    });

    // Manual Trigger Scan Button
    $('#btnTriggerOcrScan').on('click', function(e) {
        e.preventDefault();
        runSmartReceiptScan();
    });

    // Remove selected photo
    $('#btnRemoveMobilePhoto').on('click', function(e) {
        e.preventDefault();
        compressedReceiptFile = null;
        $('#mobileReceiptInput').val('');
        $('#mobilePreviewImage').attr('src', '#');
        $('#mobileCameraPreviewBox').hide();
        $('#mobileCameraPlaceholder').show();
        $('#ocrResultBadge').hide();
        $('#mobileScanStatus').hide();
    });

    // Master Hybrid OCR Scanner function
    function runSmartReceiptScan() {
        const fileInput = document.getElementById('mobileReceiptInput');
        if (!fileInput.files || !fileInput.files[0]) {
            return;
        }

        const $statusBox = $('#mobileScanStatus');
        const $statusText = $('#scanStatusText');
        const $actionContainer = $('#scanActionContainer');
        const $badgeBox = $('#ocrResultBadge');
        const $badgeContent = $('#ocrBadgeContent');

        $actionContainer.hide();
        $statusBox.show();
        $badgeBox.hide();
        $statusText.html('<i class="fas fa-spinner fa-spin mr-1"></i> AI sedang menganalisis foto nota...');

        // Step 1: Attempt Cloud AI Vision (Google Gemini) if online
        if (navigator.onLine) {
            const formData = new FormData();
            formData.append('receipt_file', compressedReceiptFile || fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');


            const scanUrl = "{{ route($routePrefix . '.kas-tunai.scan-receipt') }}";

            $.ajax({
                url: scanUrl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                timeout: 15000,
                success: function(res) {
                    if (res.success && res.data) {
                        applyOcrExtractedData(res.data, 'gemini');
                        $statusBox.hide();
                    } else if (res.fallback_to_offline) {
                        $statusText.html('<i class="fas fa-spinner fa-spin mr-1"></i> AI Cloud gagal. Mengalihkan ke OCR Offline (Tesseract)...');
                        runOfflineTesseractOcr();
                    } else {
                        $statusBox.hide();
                        $actionContainer.show();
                    }
                },
                error: function() {
                    $statusText.html('<i class="fas fa-spinner fa-spin mr-1"></i> Koneksi lambat/gagal. Mengalihkan ke OCR Offline (Tesseract)...');
                    runOfflineTesseractOcr();
                }
            });
        } else {
            // Device is offline: Go straight to offline OCR
            $statusText.html('<i class="fas fa-spinner fa-spin mr-1"></i> HP sedang offline. Menjalankan OCR Lokal (Tesseract)...');
            runOfflineTesseractOcr();
        }
    }

    // Step 2: Offline OCR using Tesseract.js inside browser
    function runOfflineTesseractOcr() {
        const imgElement = document.getElementById('mobilePreviewImage');
        const $statusBox = $('#mobileScanStatus');
        const $statusText = $('#scanStatusText');
        const $actionContainer = $('#scanActionContainer');

        if (typeof Tesseract === 'undefined') {
            $statusBox.hide();
            $actionContainer.show();
            return;
        }

        Tesseract.recognize(imgElement, 'eng', {
            logger: m => {
                if (m.status === 'recognizing text') {
                    $statusText.text('Membaca teks nota offline: ' + Math.round((m.progress || 0) * 100) + '%');
                }
            }
        }).then(({ data: { text } }) => {
            $statusBox.hide();
            const extracted = parseOfflineReceiptText(text);
            applyOcrExtractedData(extracted, 'offline');
        }).catch(err => {
            console.warn('Offline OCR error:', err);
            $statusBox.hide();
            $actionContainer.show();
        });
    }

    // Parser for Offline OCR Text
    function parseOfflineReceiptText(rawText) {
        const lines = rawText.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        let storeName = '';
        let totalAmount = 0;
        let evidenceNumber = '';

        // Store name from top lines
        if (lines.length > 0) {
            storeName = lines[0].replace(/[^a-zA-Z0-9\s.,-]/g, '').trim();
        }

        // Search for total amount using regex patterns
        for (let i = lines.length - 1; i >= 0; i--) {
            const line = lines[i];
            if (/total|jumlah|subtotal|bayar|rp/i.test(line)) {
                const numbers = line.match(/[0-9]{1,3}(?:[.,][0-9]{3})*(?:[.,][0-9]{2})?/g);
                if (numbers && numbers.length > 0) {
                    const cleanNum = numbers[numbers.length - 1].replace(/[^0-9]/g, '');
                    const val = parseFloat(cleanNum);
                    if (val > 1000) {
                        totalAmount = val;
                        break;
                    }
                }
            }
        }

        // Search for receipt number
        for (const line of lines) {
            const match = line.match(/(?:nota|no\.?|kwt|bukti)\s*[:#-]?\s*([a-zA-Z0-9/-]+)/i);
            if (match && match[1]) {
                evidenceNumber = match[1];
                break;
            }
        }

        return {
            store_name: storeName,
            total_amount: totalAmount,
            evidence_number: evidenceNumber
        };
    }

    // Apply Extracted Data into Form Fields
    function applyOcrExtractedData(data, engine) {
        if (data.total_amount && data.total_amount > 0) {
            $('#mobileInputAmount').val(Number(data.total_amount).toLocaleString('id-ID')).trigger('input');
        }
        if (data.store_name) {
            let desc = 'Belanja di ' + data.store_name;
            if (data.items && data.items.length > 0) {
                const itemNames = data.items.map(i => i.item_name).filter(Boolean).slice(0, 3).join(', ');
                if (itemNames) desc += ' (' + itemNames + ')';
            }
            $('#mobileInputDescription').val(desc);
        }
        if (data.evidence_number) {
            $('#mobileInputEvidence').val(data.evidence_number);
        }

        // Display Success Badge
        const $badgeBox = $('#ocrResultBadge');
        const $badgeContent = $('#ocrBadgeContent');
        if (engine === 'gemini') {
            $badgeContent.attr('class', 'badge bg-success text-white shadow-xs')
                .html('<i class="fas fa-robot mr-1"></i> Terbaca Otomatis oleh AI Gemini Vision');
        } else {
            $badgeContent.attr('class', 'badge bg-warning text-dark shadow-xs')
                .html('<i class="fas fa-bolt mr-1"></i> Terbaca Otomatis oleh OCR Offline (Tesseract)');
        }
        $badgeBox.show();
        $('#scanActionContainer').show();
    }

    // Submit Mobile Quick Receipt
    $('#formMobileQuickReceipt').on('submit', function(e) {
        e.preventDefault();

        const rawAmount = $('#mobileInputAmount').val().replace(/[^0-9]/g, '');
        if (!rawAmount || parseInt(rawAmount) <= 0) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Silakan masukkan nominal belanja nota!' });
            return;
        }

        const targetVal = $('#mobileSelectTarget').val();
        const isSettle = targetVal.startsWith('settle_');
        const $btnSubmit = $('#btnSubmitMobileQuick');

        $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        let url = '';
        let formData = new FormData(this);
        if (compressedReceiptFile) {
            formData.set('receipt_file', compressedReceiptFile, compressedReceiptFile.name);
        }
        formData.set('transaction_date', new Date().toISOString().split('T')[0]);


        if (isSettle) {
            const panjarId = targetVal.replace('settle_', '');
            url = "{{ url($routePrefix . '/kas-tunai') }}/" + panjarId + "/settle-panjar";
            formData.set('settle_date', new Date().toISOString().split('T')[0]);
            formData.set('real_amount', rawAmount);
            formData.set('notes', $('#mobileInputDescription').val());
        } else {
            url = "{{ route($routePrefix . '.kas-tunai.store') }}";
            formData.set('transaction_type', 'keluar');
            const category = targetVal.replace('direct_', '');
            formData.set('category', category);
            formData.set('amount', rawAmount);
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                hideKasModal('#modalMobileQuickReceipt');
                if (typeof kasTable !== 'undefined' && kasTable && typeof kasTable.ajax === 'object') {
                    kasTable.ajax.reload(null, false);
                    if (typeof loadAvailableDocuments === 'function') {
                        loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Nota belanja berhasil dicatat.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Nota belanja berhasil dicatat.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                }
            },
            error: function(err) {
                let msg = err.responseJSON?.message || 'Gagal menyimpan nota.';
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            },
            complete: function() {
                $btnSubmit.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Simpan Nota');
            }
        });
    });
});
</script>

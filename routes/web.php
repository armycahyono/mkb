<?php

use App\Http\Controllers\ArkasReferenceController;
use App\Http\Controllers\BarangDokumentasiController;
use App\Http\Controllers\BendaharaNoteController;
use App\Http\Controllers\BendaharaTaskController;
use App\Http\Controllers\BosKertasKerjaDataController;
use App\Http\Controllers\BosKertasKerjaFileController;
use App\Http\Controllers\BosPanduanBelanjaController;
use App\Http\Controllers\BosPemaduanController;
use App\Http\Controllers\BosRealisasiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\GuruJabatanController;
use App\Http\Controllers\InstansiController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\KasCategoryController;
use App\Http\Controllers\KasTunaiController;
use App\Http\Controllers\KegiatanSekolahController;
use App\Http\Controllers\PencairanBankController;
use App\Http\Controllers\PengajuanBarangController;
use App\Http\Controllers\PerjadinController;
use App\Http\Controllers\RencanaRkasController;
use App\Http\Controllers\SchoolProfileController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TelegramBotSettingController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UtilityProfileController;
use App\Http\Controllers\UtilityReceiptController;
use App\Models\BosKertasKerjaFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes - MKB (Manajemen Keuangan Bantuan)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {
    // Pengaturan Tahun Anggaran Aktif
    Route::post('fiscal-year/switch', [FiscalYearController::class, 'switch'])->name('fiscal-year.switch');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Data Pokok: Profil Sekolah, Guru, Mitra, Supplier, Jabatan, Kategori
    Route::middleware(['can:access-data-pokok'])->group(function () {
        Route::prefix('school')->name('school.')->group(function () {
            Route::get('/', [SchoolProfileController::class, 'index'])->name('index');
            Route::put('/', [SchoolProfileController::class, 'update'])->name('update');
        });
        Route::get('school-profile', [SchoolProfileController::class, 'index'])->name('school-profile.index');
        Route::put('school-profile', [SchoolProfileController::class, 'update'])->name('school-profile.update');

        // Data Pokok: Data Guru & Pegawai
        Route::resource('guru', GuruController::class);

        // Data Pokok: Master Instansi & Mitra
        Route::get('instansi/template', [InstansiController::class, 'downloadTemplate'])->name('instansi.template');
        Route::post('instansi/import', [InstansiController::class, 'import'])->name('instansi.import');
        Route::resource('instansi', InstansiController::class)->except(['create', 'edit']);
        Route::get('api/instansis/search', [InstansiController::class, 'search'])->name('api.instansis.search');

        // Data Pokok: Daftar Supplier / Rekanan
        Route::resource('supplier', SupplierController::class)->except(['create', 'edit']);
        Route::post('supplier/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('supplier.toggle-status');
        Route::post('supplier/categories', [SupplierController::class, 'storeCategory'])->name('supplier.categories.store');
        Route::get('api/suppliers/search', [SupplierController::class, 'search'])->name('api.suppliers.search');

        // Data Pokok: Master Jabatan & Penetapan Penugasan
        Route::get('jabatan/penugasan', [GuruJabatanController::class, 'index'])->name('jabatan.penugasan');
        Route::resource('jabatan', JabatanController::class)->except(['create', 'show', 'edit']);
        Route::resource('master/guru-jabatan', GuruJabatanController::class)->except(['create', 'show', 'edit'])->names('guru-jabatan');
        Route::post('master/guru-jabatan/{guruJabatan}/toggle-status', [GuruJabatanController::class, 'toggleStatus'])->name('guru-jabatan.toggle-status');
        Route::get('master/guru-jabatan/{guruJabatan}/sk-file', [GuruJabatanController::class, 'serveSkFile'])->name('guru-jabatan.sk-file');

        // Data Pokok: Master Kategori Transaksi Kas
        Route::resource('kas-category', KasCategoryController::class)->except(['create', 'show', 'edit']);
        Route::post('kas-category/{kasCategory}/toggle-status', [KasCategoryController::class, 'toggleStatus'])->name('kas-category.toggle-status');
        // Data Pokok: Master Identitas Utilitas (Listrik, Telepon, Internet, dll)
        Route::resource('master/utilitas', UtilityProfileController::class)->names('master.utilitas');

        // Data Pokok: Rencana Pengajuan Barang
        Route::resource('pengajuan-barang', PengajuanBarangController::class);
        Route::post('pengajuan-barang/{pengajuanBarang}/realisasi', [PengajuanBarangController::class, 'realisasi'])->name('pengajuan-barang.realisasi');

        // Data Pokok: Master Program / Kegiatan Sekolah
        Route::resource('kegiatan-sekolah', KegiatanSekolahController::class)->except(['create', 'show', 'edit']);
        Route::post('kegiatan-sekolah/{kegiatanSekolah}/toggle-status', [KegiatanSekolahController::class, 'toggleStatus'])->name('kegiatan-sekolah.toggle-status');
    });

    // Modul Keuangan: BOS (Bantuan Operasional Sekolah)
    Route::prefix('bos')->name('bos.')->middleware(['can:access-bosp'])->group(function () {
        // Rencana RKAS (Pra-ARKAS)
        Route::prefix('rencana-rkas')->name('rkas.')->group(function () {
            Route::get('/', [RencanaRkasController::class, 'index'])->name('index');
            Route::post('/', [RencanaRkasController::class, 'store'])->name('store');
            Route::get('/search-kegiatan', [RencanaRkasController::class, 'searchKegiatan'])->name('search-kegiatan');
            Route::get('/search-barang', [RencanaRkasController::class, 'searchBarang'])->name('search-barang');
            Route::get('/search-rekening', [RencanaRkasController::class, 'searchRekening'])->name('search-rekening');
            Route::put('/{id}', [RencanaRkasController::class, 'update'])->name('update');
            Route::delete('/{id}', [RencanaRkasController::class, 'destroy'])->name('destroy');
            Route::get('/template', [RencanaRkasController::class, 'exportTemplate'])->name('template');
            Route::post('/import', [RencanaRkasController::class, 'importExcel'])->name('import');
            Route::post('/pagu', [RencanaRkasController::class, 'savePagu'])->name('pagu.save');
        });

        // File Kertas Kerja
        Route::prefix('files')->name('files.')->group(function () {
            Route::get('/', [BosKertasKerjaFileController::class, 'index'])->name('index');
            Route::post('/', [BosKertasKerjaFileController::class, 'store'])->name('store');
            Route::get('/check-version', [BosKertasKerjaFileController::class, 'checkVersion'])->name('check-version');
            Route::get('/compare/{file}', [BosKertasKerjaFileController::class, 'compare'])->name('compare');
            Route::post('/set-active/{file}', [BosKertasKerjaFileController::class, 'setActive'])->name('set-active');
            Route::put('/{file}', [BosKertasKerjaFileController::class, 'update'])->name('update');
            Route::delete('/{file}', [BosKertasKerjaFileController::class, 'destroy'])->name('destroy');
            Route::get('/serve/{file}', function (BosKertasKerjaFile $file) {
                $path = Storage::disk('public')->path($file->file_path);
                if (! file_exists($path)) {
                    abort(404, 'File tidak ditemukan.');
                }

                return response()->file($path, ['Content-Type' => 'application/pdf']);
            })->name('serve');
        });

        // Data Kertas Kerja
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/', [BosKertasKerjaDataController::class, 'index'])->name('index');
            Route::post('/', [BosKertasKerjaDataController::class, 'store'])->name('store');
            Route::get('/export', [BosKertasKerjaDataController::class, 'export'])->name('export');
            Route::post('/import', [BosKertasKerjaDataController::class, 'import'])->name('import');
            Route::delete('/{item}', [BosKertasKerjaDataController::class, 'destroy'])->name('destroy');
        });

        // Pemaduan Rencana RKAS & Kertas Kerja BOS
        Route::prefix('pemaduan')->name('pemaduan.')->group(function () {
            Route::get('/', [BosPemaduanController::class, 'index'])->name('index');
            Route::get('/available-pengajuan', [BosPemaduanController::class, 'availablePengajuan'])->name('available-pengajuan');
            Route::get('/available-recurring-pengajuan', [BosPemaduanController::class, 'availableRecurringPengajuan'])->name('available-recurring-pengajuan');
            Route::post('/pair', [BosPemaduanController::class, 'pair'])->name('pair');
            Route::post('/unpair', [BosPemaduanController::class, 'unpair'])->name('unpair');
            Route::post('/batch-pair', [BosPemaduanController::class, 'batchPair'])->name('batch-pair');
            Route::post('/batch-unpair', [BosPemaduanController::class, 'batchUnpair'])->name('batch-unpair');
        });

        // Realisasi BOS
        Route::prefix('realisasi')->name('realisasi.')->group(function () {
            Route::get('/', [BosRealisasiController::class, 'index'])->name('index');
            Route::get('/available-items', [BosRealisasiController::class, 'availableItems'])->name('available-items');
            Route::get('/summary', [BosRealisasiController::class, 'summary'])->name('summary');
            Route::get('/nota-details', [BosRealisasiController::class, 'notaDetails'])->name('nota-details');
            Route::post('/bulk-update-nota', [BosRealisasiController::class, 'bulkUpdateNota'])->name('bulk-update-nota');
            Route::post('/', [BosRealisasiController::class, 'store'])->name('store');
            Route::put('/{realisasi}', [BosRealisasiController::class, 'update'])->name('update');
            Route::patch('/{realisasi}/field', [BosRealisasiController::class, 'updateField'])->name('update-field');
            Route::post('/penerimaan', [BosRealisasiController::class, 'storePenerimaan'])->name('penerimaan.store');
            Route::put('/penerimaan/{penerimaan}', [BosRealisasiController::class, 'updatePenerimaan'])->name('penerimaan.update');
            Route::delete('/penerimaan/{penerimaan}', [BosRealisasiController::class, 'destroyPenerimaan'])->name('penerimaan.destroy');
            Route::delete('/{realisasi}', [BosRealisasiController::class, 'destroy'])->name('destroy');
        });

        // Panduan Belanja BOS
        Route::prefix('panduan')->name('panduan.')->group(function () {
            Route::get('/', [BosPanduanBelanjaController::class, 'index'])->name('index');
            Route::get('/next-nota', [BosPanduanBelanjaController::class, 'nextNotaNumber'])->name('next-nota');
            Route::get('/unrealized-data', [BosPanduanBelanjaController::class, 'unrealizedData'])->name('unrealized-data');
            Route::get('/groups-data', [BosPanduanBelanjaController::class, 'groupsData'])->name('groups-data');
            Route::post('/store-group', [BosPanduanBelanjaController::class, 'storeGroup'])->name('store-group');
            Route::get('/edit-group/{id}', [BosPanduanBelanjaController::class, 'editGroup'])->name('edit-group');
            Route::get('/show-group/{id}', [BosPanduanBelanjaController::class, 'showGroup'])->name('show-group');
            Route::put('/update-group/{id}', [BosPanduanBelanjaController::class, 'updateGroup'])->name('update-group');
            Route::get('/print/{id}', [BosPanduanBelanjaController::class, 'printGroup'])->name('print');
            Route::post('/convert/{id}', [BosPanduanBelanjaController::class, 'convertToRealisasi'])->name('convert');
            Route::post('/toggle-order/{id}', [BosPanduanBelanjaController::class, 'toggleOrderStatus'])->name('toggle-order');
            Route::delete('/group/{id}', [BosPanduanBelanjaController::class, 'destroyGroup'])->name('destroy-group');
        });

        // Perjalanan Dinas (BOSP)
        Route::get('perjadin/template', [PerjadinController::class, 'downloadTemplate'])->name('perjadin.template');
        Route::post('perjadin/import', [PerjadinController::class, 'import'])->name('perjadin.import');
        Route::patch('perjadin/{perjadin}/update-bku', [PerjadinController::class, 'updateBku'])->name('perjadin.update-bku');
        Route::post('perjadin/batch-update-bku', [PerjadinController::class, 'batchUpdateBku'])->name('perjadin.batch-update-bku');
        Route::resource('perjadin', PerjadinController::class);
        Route::get('perjadin/{perjadin}/print/{item?}', [PerjadinController::class, 'printKwitansi'])->name('perjadin.print');

        // Kuasa Pemindahbukuan Bank (BOSP)
        Route::resource('pencairan-bank', PencairanBankController::class)->parameters(['pencairan-bank' => 'pencairanBank']);
        Route::post('pencairan-bank/{pencairanBank}/cairkan', [PencairanBankController::class, 'confirmCair'])->name('pencairan-bank.cairkan');
        Route::get('pencairan-bank/{pencairanBank}/print', [PencairanBankController::class, 'printSurat'])->name('pencairan-bank.print');
        Route::get('pencairan-bank/{pencairanBank}/export-excel', [PencairanBankController::class, 'exportExcel'])->name('pencairan-bank.export-excel');
        Route::get('api/unpaid-perjadins', [PencairanBankController::class, 'getUnpaidPerjadinItems'])->name('api.unpaid-perjadins');
        Route::get('api/unpaid-rencana-notas', [PencairanBankController::class, 'getUnpaidRencanaNotas'])->name('api.unpaid-rencana-notas');
        Route::get('api/unpaid-utility-receipts', [PencairanBankController::class, 'getUnpaidUtilityReceipts'])->name('api.unpaid-utility-receipts');

        // Dokumentasi Barang Belanja (BOSP)
        Route::prefix('dokumentasi-barang')->name('dokumentasi-barang.')->group(function () {
            Route::get('/', [BarangDokumentasiController::class, 'index'])->name('index');
            Route::get('/available-notas', [BarangDokumentasiController::class, 'availableNotas'])->name('available-notas');
            Route::post('/', [BarangDokumentasiController::class, 'store'])->name('store');
            Route::get('/{id}', [BarangDokumentasiController::class, 'show'])->name('show');
            Route::put('/{id}', [BarangDokumentasiController::class, 'update'])->name('update');
            Route::delete('/{id}', [BarangDokumentasiController::class, 'destroy'])->name('destroy');
            Route::delete('/foto/{fotoId}', [BarangDokumentasiController::class, 'destroyFoto'])->name('destroy-foto');
            Route::get('/foto/{fotoId}/download', [BarangDokumentasiController::class, 'downloadFoto'])->name('download-foto');
            Route::get('/{id}/download-zip', [BarangDokumentasiController::class, 'downloadZip'])->name('download-zip');
            Route::get('/{id}/print', [BarangDokumentasiController::class, 'printDokumentasi'])->name('print');
        });

        // Kas Operasional / Kas Tunai (BOSP) - Khusus Kepala Sekolah & Bendahara BOS
        Route::middleware(['can:access-bosp-kas'])->group(function () {
            Route::get('kas-tunai/available-documents', [KasTunaiController::class, 'getAvailableDocuments'])->name('kas-tunai.available-documents');
            Route::get('kas-tunai-print', [KasTunaiController::class, 'printKas'])->name('kas-tunai.print');
            Route::post('kas-tunai/opname', [KasTunaiController::class, 'printOpname'])->name('kas-tunai.opname');
            Route::get('kas-tunai/kasbon', [KasTunaiController::class, 'kasbonIndex'])->name('kas-tunai.kasbon');
            Route::get('kas-tunai/belanja-barang', [KasTunaiController::class, 'belanjaBarangIndex'])->name('kas-tunai.belanja-barang');
            Route::get('kas-tunai/belanja-barang/data', [KasTunaiController::class, 'belanjaBarangData'])->name('kas-tunai.belanja-barang.data');
            Route::post('kas-tunai/belanja-barang/store', [KasTunaiController::class, 'storeBelanjaBarang'])->name('kas-tunai.belanja-barang.store');
            Route::get('kas-tunai/{kasTunai}/items', [KasTunaiController::class, 'getItems'])->name('kas-tunai.items');
            Route::get('kas-tunai/{kasTunai}/detail', [KasTunaiController::class, 'getDetail'])->name('kas-tunai.detail');
            Route::post('kas-tunai/{kasTunai}/save-items', [KasTunaiController::class, 'saveNotaItems'])->name('kas-tunai.save-items');
            Route::post('kas-tunai/{kasTunai}/settle-panjar', [KasTunaiController::class, 'settlePanjar'])->name('kas-tunai.settle-panjar');
            Route::post('kas-tunai/scan-receipt', [KasTunaiController::class, 'scanReceipt'])->name('kas-tunai.scan-receipt');
            Route::resource('kas-tunai', KasTunaiController::class)->parameters(['kas-tunai' => 'kasTunai']);
        });

        // Catatan BOSP
        Route::prefix('notes')->name('notes.')->group(function () {
            Route::get('/', [BendaharaNoteController::class, 'index'])->defaults('tipe', 'bosp')->name('index');
            Route::post('/', [BendaharaNoteController::class, 'store'])->defaults('tipe', 'bosp')->name('store');
            Route::post('/tasks', [BendaharaTaskController::class, 'store'])->defaults('tipe', 'bosp')->name('tasks.store');
        });

        // Struk Utilitas (Listrik, Telepon, Internet)
        Route::prefix('utilitas')->name('utilitas.')->group(function () {
            Route::get('/', [UtilityReceiptController::class, 'index'])->name('index');
            Route::get('/data', [UtilityReceiptController::class, 'data'])->name('data');
            Route::post('/scan-receipt', [UtilityReceiptController::class, 'scanReceipt'])->name('scan-receipt');
            Route::post('/batch-upload', [UtilityReceiptController::class, 'batchUpload'])->name('batch-upload');
            Route::get('/poll-status', [UtilityReceiptController::class, 'pollStatus'])->name('poll-status');
            Route::post('/{id}/retry-ocr', [UtilityReceiptController::class, 'retryOcr'])->name('retry-ocr');
            Route::post('/', [UtilityReceiptController::class, 'store'])->name('store');
            Route::get('/{id}', [UtilityReceiptController::class, 'show'])->name('show');
            Route::put('/{id}', [UtilityReceiptController::class, 'update'])->name('update');
            Route::delete('/{id}', [UtilityReceiptController::class, 'destroy'])->name('destroy');
        });

        // Referensi ARKAS (Kegiatan & Referensi Barang)
        Route::prefix('referensi-arkas')->name('referensi-arkas.')->group(function () {
            Route::get('/', [ArkasReferenceController::class, 'index'])->name('index');
            Route::get('/data-kegiatan', [ArkasReferenceController::class, 'dataKegiatan'])->name('data-kegiatan');
            Route::get('/data-barang', [ArkasReferenceController::class, 'dataBarang'])->name('data-barang');
        });
    });

    // Resource alias for utility-receipts
    Route::resource('utility-receipts', UtilityReceiptController::class)->except(['create', 'edit']);
    Route::get('utility-receipts-data', [UtilityReceiptController::class, 'data'])->name('utility-receipts.data');
    Route::post('utility-receipts-scan', [UtilityReceiptController::class, 'scanReceipt'])->name('utility-receipts.scan-receipt');
    Route::post('utility-receipts-batch-upload', [UtilityReceiptController::class, 'batchUpload'])->name('utility-receipts.batch-upload');
    Route::get('utility-receipts-poll-status', [UtilityReceiptController::class, 'pollStatus'])->name('utility-receipts.poll-status');
    Route::post('utility-receipts/{id}/retry-ocr', [UtilityReceiptController::class, 'retryOcr'])->name('utility-receipts.retry-ocr');

    // Modul Keuangan: BPOPP (Biaya Penunjang Operasional Penyelenggaraan Pendidikan)
    Route::prefix('bpopp')->name('bpopp.')->middleware(['can:access-bpopp'])->group(function () {
        // Perjalanan Dinas (BPOPP)
        Route::get('perjadin/template', [PerjadinController::class, 'downloadTemplate'])->name('perjadin.template');
        Route::post('perjadin/import', [PerjadinController::class, 'import'])->name('perjadin.import');
        Route::patch('perjadin/{perjadin}/update-bku', [PerjadinController::class, 'updateBku'])->name('perjadin.update-bku');
        Route::post('perjadin/batch-update-bku', [PerjadinController::class, 'batchUpdateBku'])->name('perjadin.batch-update-bku');
        Route::resource('perjadin', PerjadinController::class);
        Route::get('perjadin/{perjadin}/print/{item?}', [PerjadinController::class, 'printKwitansi'])->name('perjadin.print');

        // Kuasa Pemindahbukuan Bank (BPOPP)
        Route::resource('pencairan-bank', PencairanBankController::class)->parameters(['pencairan-bank' => 'pencairanBank']);
        Route::post('pencairan-bank/{pencairanBank}/cairkan', [PencairanBankController::class, 'confirmCair'])->name('pencairan-bank.cairkan');
        Route::get('pencairan-bank/{pencairanBank}/print', [PencairanBankController::class, 'printSurat'])->name('pencairan-bank.print');
        Route::get('pencairan-bank/{pencairanBank}/export-excel', [PencairanBankController::class, 'exportExcel'])->name('pencairan-bank.export-excel');
        Route::get('api/unpaid-perjadins', [PencairanBankController::class, 'getUnpaidPerjadinItems'])->name('api.unpaid-perjadins');
        Route::get('api/unpaid-rencana-notas', [PencairanBankController::class, 'getUnpaidRencanaNotas'])->name('api.unpaid-rencana-notas');
        Route::get('api/unpaid-utility-receipts', [PencairanBankController::class, 'getUnpaidUtilityReceipts'])->name('api.unpaid-utility-receipts');

        // Kas Operasional / Kas Tunai (BPOPP) - Khusus Kepala Sekolah & Bendahara BPOPP
        Route::middleware(['can:access-bpopp-kas'])->group(function () {
            Route::get('kas-tunai/available-documents', [KasTunaiController::class, 'getAvailableDocuments'])->name('kas-tunai.available-documents');
            Route::get('kas-tunai-print', [KasTunaiController::class, 'printKas'])->name('kas-tunai.print');
            Route::post('kas-tunai/opname', [KasTunaiController::class, 'printOpname'])->name('kas-tunai.opname');
            Route::get('kas-tunai/kasbon', [KasTunaiController::class, 'kasbonIndex'])->name('kas-tunai.kasbon');
            Route::get('kas-tunai/belanja-barang', [KasTunaiController::class, 'belanjaBarangIndex'])->name('kas-tunai.belanja-barang');
            Route::get('kas-tunai/belanja-barang/data', [KasTunaiController::class, 'belanjaBarangData'])->name('kas-tunai.belanja-barang.data');
            Route::post('kas-tunai/belanja-barang/store', [KasTunaiController::class, 'storeBelanjaBarang'])->name('kas-tunai.belanja-barang.store');
            Route::get('kas-tunai/{kasTunai}/items', [KasTunaiController::class, 'getItems'])->name('kas-tunai.items');
            Route::get('kas-tunai/{kasTunai}/detail', [KasTunaiController::class, 'getDetail'])->name('kas-tunai.detail');
            Route::post('kas-tunai/{kasTunai}/save-items', [KasTunaiController::class, 'saveNotaItems'])->name('kas-tunai.save-items');
            Route::post('kas-tunai/{kasTunai}/settle-panjar', [KasTunaiController::class, 'settlePanjar'])->name('kas-tunai.settle-panjar');
            Route::post('kas-tunai/scan-receipt', [KasTunaiController::class, 'scanReceipt'])->name('kas-tunai.scan-receipt');
            Route::resource('kas-tunai', KasTunaiController::class)->parameters(['kas-tunai' => 'kasTunai']);
        });

        // Catatan BPOPP
        Route::prefix('notes')->name('notes.')->group(function () {
            Route::get('/', [BendaharaNoteController::class, 'index'])->defaults('tipe', 'bpopp')->name('index');
            Route::post('/', [BendaharaNoteController::class, 'store'])->defaults('tipe', 'bpopp')->name('store');
            Route::post('/tasks', [BendaharaTaskController::class, 'store'])->defaults('tipe', 'bpopp')->name('tasks.store');
        });
    });

    // Aksi Catatan & Agenda Tugas Bendahara (BOSP & BPOPP)
    Route::put('notes/{note}', [BendaharaNoteController::class, 'update'])->name('notes.update');
    Route::patch('notes/{note}/toggle-pin', [BendaharaNoteController::class, 'togglePin'])->name('notes.toggle-pin');
    Route::patch('notes/{note}/toggle-complete', [BendaharaNoteController::class, 'toggleComplete'])->name('notes.toggle-complete');
    Route::delete('notes/{note}', [BendaharaNoteController::class, 'destroy'])->name('notes.destroy');

    Route::put('notes/tasks/{task}', [BendaharaTaskController::class, 'update'])->name('notes.tasks.update');
    Route::patch('notes/tasks/{task}/toggle-complete', [BendaharaTaskController::class, 'toggleComplete'])->name('notes.tasks.toggle-complete');
    Route::delete('notes/tasks/{task}', [BendaharaTaskController::class, 'destroy'])->name('notes.tasks.destroy');

    // Pengaturan Sistem: Manajemen User & Bot Telegram
    Route::middleware(['can:manage-settings'])->group(function () {
        Route::resource('user', UserController::class)->except(['create', 'show', 'edit']);

        Route::prefix('telegram-bot')->name('telegram-bot.')->group(function () {
            Route::get('/', [TelegramBotSettingController::class, 'index'])->name('index');
            Route::post('/sync-webhook', [TelegramBotSettingController::class, 'syncWebhook'])->name('sync-webhook');
            Route::post('/delete-webhook', [TelegramBotSettingController::class, 'deleteWebhook'])->name('delete-webhook');
            Route::post('/test-send', [TelegramBotSettingController::class, 'testSend'])->name('test-send');
            Route::post('/sync-commands', [TelegramBotSettingController::class, 'syncCommandsToTelegram'])->name('sync-commands');
            Route::post('/commands', [TelegramBotSettingController::class, 'storeCommand'])->name('commands.store');
            Route::put('/commands/{command}', [TelegramBotSettingController::class, 'updateCommand'])->name('commands.update');
            Route::post('/commands/{command}/toggle', [TelegramBotSettingController::class, 'toggleCommand'])->name('commands.toggle');
            Route::delete('/commands/{command}', [TelegramBotSettingController::class, 'destroyCommand'])->name('commands.destroy');
            Route::get('/commands/{command}/flow', [TelegramBotSettingController::class, 'getFlowConfig'])->name('commands.flow.get');
            Route::put('/commands/{command}/flow', [TelegramBotSettingController::class, 'updateFlowConfig'])->name('commands.flow.update');
            Route::post('/commands/{command}/flow/reset', [TelegramBotSettingController::class, 'resetFlowConfig'])->name('commands.flow.reset');
        });
    });
});

// Health check for Telegram webhook
Route::get('telegram/ping', fn () => response('pong'));

// Telegram Bot Webhook Endpoint with rate limiting and secret token verification
Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(['throttle:60,1', 'verify.telegram'])
    ->name('telegram.webhook');

Route::post('api/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(['throttle:60,1', 'verify.telegram']);

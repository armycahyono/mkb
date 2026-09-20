<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('login', [V1\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [V1\AuthController::class, 'logout']);
        Route::get('user', [V1\AuthController::class, 'user']);

        Route::get('dashboard', [V1\DashboardController::class, 'index']);
        Route::get('saldo', [V1\SaldoController::class, 'index']);

        Route::get('kas-categories', [V1\KasCategoryController::class, 'index']);
        Route::apiResource('kas-tunai', V1\KasTunaiController::class)->only(['index', 'store', 'show']);

        Route::get('kasbon', [V1\KasbonController::class, 'index']);
        Route::post('kasbon', [V1\KasbonController::class, 'store']);
        Route::get('kasbon/{kasTunai}', [V1\KasbonController::class, 'show']);
        Route::post('kasbon/{kasTunai}/settle', [V1\KasbonController::class, 'settle']);

        Route::get('foto-barang', [V1\FotoBarangController::class, 'index']);
        Route::post('foto-barang', [V1\FotoBarangController::class, 'store']);

        Route::get('guru', [V1\GuruController::class, 'index']);
        Route::get('fiscal-years', [V1\FiscalYearController::class, 'index']);

        // === V2 MODULES ===
        // Kertas Kerja RKAS (Read-Only)
        Route::get('kertas-kerja', [V1\KertasKerjaController::class, 'index']);

        // Rencana Belanja
        Route::get('rencana-belanja', [V1\RencanaBelanjaController::class, 'index']);
        Route::get('rencana-belanja/{id}', [V1\RencanaBelanjaController::class, 'show']);

        // TNT (Pencairan Bank Jatim)
        Route::get('tnt', [V1\TntController::class, 'index']);
        Route::get('tnt/{id}', [V1\TntController::class, 'show']);

        // Pengajuan Barang
        Route::get('pengajuan-barang', [V1\PengajuanBarangController::class, 'index']);
        Route::post('pengajuan-barang', [V1\PengajuanBarangController::class, 'store']);

        // Tagihan Utilitas
        Route::get('utility-profiles', [V1\UtilityController::class, 'profiles']);
        Route::get('utility-receipts', [V1\UtilityController::class, 'index']);
        Route::post('utility-receipts', [V1\UtilityController::class, 'store']);

        // Smart OCR Scan Nota
        Route::post('ocr-scan', [V1\OcrController::class, 'scan']);
    });
});

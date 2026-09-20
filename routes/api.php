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
    });
});

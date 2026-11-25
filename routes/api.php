<?php

use App\Http\Controllers\CLaporan;
use App\Http\Controllers\CPenjualan;
use App\Http\Controllers\CStock;
use App\Http\Controllers\CUsers;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [CUsers::class, 'login'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Bearer Token via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Current user & auth session
    Route::get('/user', [CUsers::class, 'me']);
    Route::get('/me', [CUsers::class, 'me']);
    Route::post('/logout', [CUsers::class, 'logout']);

    // Stocks (Operational read + create for all authenticated staff; edit/delete owner-only)
    Route::get('/stocks', [CStock::class, 'index']);
    Route::post('/stocks', [CStock::class, 'store']);
    Route::get('/stocks/search-imei/{imei}', [CStock::class, 'searchByImei']);
    Route::get('/stocks/lookup-tac/{imei}', [CStock::class, 'lookupTac']);
    Route::get('/stocks/{id}', [CStock::class, 'show']);

    // Penjualan & POS Checkout (Operational read + checkout for all authenticated staff)
    Route::get('/penjualan', [CPenjualan::class, 'index']);
    Route::post('/penjualan', [CPenjualan::class, 'store']);
    Route::post('/penjualan/checkout', [CPenjualan::class, 'checkout']);
    Route::get('/penjualan/invoice/{no_invoice}', [CPenjualan::class, 'getInvoice'])->where('no_invoice', '.*');
    Route::get('/penjualan/{id}', [CPenjualan::class, 'show']);

    // Dashboard Analytics (Role-scoped inside controller)
    Route::get('/analytics/dashboard', [CLaporan::class, 'getDashboardAnalytics']);

    // Owner-Only Management & Financial Reports
    Route::middleware('owner')->group(function () {
        Route::get('/users', [CUsers::class, 'index']);
        Route::post('/users', [CUsers::class, 'store']);
        Route::get('/users/{id_user}', [CUsers::class, 'show']);
        Route::put('/users/{id_user}', [CUsers::class, 'update']);
        Route::delete('/users/{id_user}', [CUsers::class, 'destroy']);

        Route::put('/stocks/{id}', [CStock::class, 'update']);
        Route::delete('/stocks/{id}', [CStock::class, 'destroy']);

        Route::put('/penjualan/{id}', [CPenjualan::class, 'update']);
        Route::delete('/penjualan/{id}', [CPenjualan::class, 'destroy']);

        Route::get('/report/monthly', [CLaporan::class, 'getMonthlyReport']);
        Route::get('/report', [CLaporan::class, 'getMonthlyReport']);
        Route::get('/report/{year}/{month}', [CLaporan::class, 'getMonthlyReport'])->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}']);
        Route::get('/available-dates', [CLaporan::class, 'getAvailableDates']);
        Route::get('/sales-details', [CLaporan::class, 'getSalesDetails']);
        Route::get('/sales-performance', [CLaporan::class, 'getSalesPerformance']);
    });
});

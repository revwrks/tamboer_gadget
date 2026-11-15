<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CStock;
use App\Http\Controllers\CUsers;
use App\Http\Controllers\CPenjualan;
use App\Http\Controllers\CLaporan;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [CUsers::class, 'login']);

// Proxy endpoint to force native browser downloads for generated Excel files
Route::post('/download-excel-proxy', function (Request $request) {
    $filename = $request->input('filename', 'export.xlsx');
    $base64 = $request->input('base64');
    
    if (!$base64) {
        return response('No data provided', 400);
    }

    $data = base64_decode($base64);
    
    return response($data)
        ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Bearer Token via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Current user & auth session
    Route::get('/user', [CUsers::class, 'me']);
    Route::get('/me', [CUsers::class, 'me']);
    Route::post('/logout', [CUsers::class, 'logout']);

    // User management (Protected with internal owner check)
    Route::get('/users', [CUsers::class, 'index']);
    Route::post('/users', [CUsers::class, 'store']);
    Route::get('/users/{id_user}', [CUsers::class, 'show']);
    Route::put('/users/{id_user}', [CUsers::class, 'update']);
    Route::delete('/users/{id_user}', [CUsers::class, 'destroy']);

    // Stocks
    Route::get('/stocks', [CStock::class, 'index']);
    Route::post('/stocks', [CStock::class, 'store']);
    Route::get('/stocks/search-imei/{imei}', [CStock::class, 'searchByImei']);
    Route::get('/stocks/{id}', [CStock::class, 'show']);
    Route::put('/stocks/{id}', [CStock::class, 'update']);
    Route::delete('/stocks/{id}', [CStock::class, 'destroy']);

    // Penjualan & POS Checkout
    Route::get('/penjualan', [CPenjualan::class, 'index']);
    Route::post('/penjualan', [CPenjualan::class, 'store']);
    Route::post('/penjualan/checkout', [CPenjualan::class, 'checkout']);
    Route::get('/penjualan/invoice/{no_invoice}', [CPenjualan::class, 'getInvoice'])->where('no_invoice', '.*');
    Route::get('/penjualan/{id}', [CPenjualan::class, 'show']);
    Route::put('/penjualan/{id}', [CPenjualan::class, 'update']);
    Route::delete('/penjualan/{id}', [CPenjualan::class, 'destroy']);

    // Reports & Analytics
    Route::get('/analytics/dashboard', [CLaporan::class, 'getDashboardAnalytics']);
    Route::get('/report/monthly', [CLaporan::class, 'getMonthlyReport']);
    Route::get('/report', [CLaporan::class, 'getMonthlyReport'])->middleware('throttle:60,1');
    Route::get('/report/{year}/{month}', [CLaporan::class, 'getMonthlyReport'])->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}']);
    Route::get('/available-dates', [CLaporan::class, 'getAvailableDates']);
    Route::get('/sales-details', [CLaporan::class, 'getSalesDetails']);
    Route::get('/sales-performance', [CLaporan::class, 'getSalesPerformance']);
});

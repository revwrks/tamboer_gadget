<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CStock;
use App\Http\Controllers\CUsers;
use App\Http\Controllers\CPenjualan;
use App\Http\Controllers\CLaporan;


// Define routes for CStock controller
Route::get('/stocks', [CStock::class, 'index']);
Route::post('/stocks', [CStock::class, 'store']);
Route::get('/stocks/{id}', [CStock::class, 'show']);
Route::put('/stocks/{id}', [CStock::class, 'update']);
Route::delete('/stocks/{id}', [CStock::class, 'destroy']);

// Define routes for CUsers controller
Route::get('/users', [CUsers::class, 'index']);
Route::post('/users', [CUsers::class, 'store']);
Route::get('/users/{id_user}', [CUsers::class, 'show']);
Route::put('/users/{id_user}', [CUsers::class, 'update']);
Route::delete('/users/{id_user}', [CUsers::class, 'destroy']);
Route::post('/login', [CUsers::class, 'login']);

// Define routes for CPenjualan controller
Route::get('/penjualan', [CPenjualan::class, 'index']);
Route::post('/penjualan', [CPenjualan::class, 'store']);
Route::get('/penjualan/{id}', [CPenjualan::class, 'show']);
Route::put('/penjualan/{id}', [CPenjualan::class, 'update']);
Route::delete('/penjualan/{id}', [CPenjualan::class, 'destroy']);

Route::get('/report', [CLaporan::class, 'getMonthlyReport'])->middleware('throttle:60,1'); // 60 requests per minute
Route::get('/report/{year?}/{month?}', [CLaporan::class, 'getMonthlyReport']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

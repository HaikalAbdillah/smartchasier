<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RecommendationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rute untuk produk
Route::apiResource('products', ProductController::class);

// Rute untuk transaksi (GET /api/transactions, GET /api/transactions/{id}, dll)
Route::apiResource('transactions', TransactionController::class);

// Rute khusus untuk checkout (POST /api/checkout)
Route::post('checkout', [TransactionController::class, 'store']);

// Rute untuk rekomendasi
Route::get('recommendations', [RecommendationController::class, 'index']);
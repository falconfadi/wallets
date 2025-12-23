<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::get('/hello', function () {
    return "Hello World!";
});


//use App\Http\Controllers\Api\TransactionController;

// Wallet routes
Route::apiResource('wallets', WalletController::class);

//wallet transactions
Route::prefix('wallets/{wallet}')->group(function () {
    Route::post('/deposit', [TransactionController::class, 'deposit']);
    Route::post('/withdraw', [TransactionController::class, 'withdraw']);
    Route::get('/transactions', [TransactionController::class, 'index']);
});
//transfer route
Route::post('/transfers', [TransferController::class, 'store']);
// Nested routes for wallet transactions
//Route::prefix('wallets/{wallet}')->group(function () {
//    Route::apiResource('transactions', TransactionController::class);
//});


// Additional standalone transaction routes if needed
//Route::apiResource('transactions', TransactionController::class)->only(['index', 'show']);


<?php

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

// Nested routes for wallet transactions
//Route::prefix('wallets/{wallet}')->group(function () {
//    Route::apiResource('transactions', TransactionController::class);
//});

// Additional standalone transaction routes if needed
//Route::apiResource('transactions', TransactionController::class)->only(['index', 'show']);


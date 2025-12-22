<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;

// Wallet routes
Route::apiResource('wallets', WalletController::class);

// Nested routes for wallet transactions
Route::prefix('wallets/{wallet}')->group(function () {
    Route::apiResource('transactions', TransactionController::class);
});

// Additional standalone transaction routes if needed
Route::apiResource('transactions', TransactionController::class)->only(['index', 'show']);

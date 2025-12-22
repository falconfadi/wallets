<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WalletService
{
    /**
     * Create a new wallet with zero balance
     */
    public function createWallet(array $data): Wallet
    {
        // Ensure balance starts at zero
        $data['balance'] = 0;

        return Wallet::create($data);
    }

    /**
     * Get wallet by ID with currency loaded
     */
    public function getWallet(int $id): ?Wallet
    {
        return Wallet::with('currency')->find($id);
    }

    /**
     * List all wallets with optional filters
     */
    public function listWallets(array $filters = []): Collection
    {
        $query = Wallet::with('currency');

        // Apply owner filter
        if (!empty($filters['owner'])) {
            $query->byOwner($filters['owner']);
        }

        // Apply currency filter
        if (!empty($filters['currency'])) {
            $query->byCurrency($filters['currency']);
        }

        return $query->get();
    }

    /**
     * List wallets with pagination and filters
     */
    public function paginateWallets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Wallet::with('currency');

        // Apply owner filter
        if (!empty($filters['owner'])) {
            $query->byOwner($filters['owner']);
        }

        // Apply currency filter
        if (!empty($filters['currency'])) {
            $query->byCurrency($filters['currency']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Update wallet
     */
    public function updateWallet(Wallet $wallet, array $data): Wallet
    {
        // Prevent balance updates through this method
        unset($data['balance']);

        $wallet->update($data);

        return $wallet->fresh(['currency']);
    }

    /**
     * Delete wallet
     */
    public function deleteWallet(Wallet $wallet): bool
    {
        return $wallet->delete();
    }

    /**
     * Get wallet balance
     */
    public function getWalletBalance(int $walletId): float
    {
        $wallet = Wallet::find($walletId);

        return $wallet ? $wallet->balance : 0;
    }

    /**
     * Validate currency exists
     */
    public function currencyExists(int $currencyId): bool
    {
        return Currency::where('id', $currencyId)->exists();
    }

    /**
     * Get all available currencies
     */
    public function getCurrencies(): Collection
    {
        return Currency::all();
    }
}

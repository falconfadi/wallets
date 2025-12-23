<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TransactionQueryService
{
    /**
     * Build query with filters and sorting
     */
    public function buildQuery(int $walletId, array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Transaction::where('wallet_id', $walletId)
            ->with(['wallet']);

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply sorting
        $query = $this->applySorting($query, $filters);

        return $query;
    }

    /**
     * Apply filters to query
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        // Filter by transaction type
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $query->ofCategory($filters['category']);
        }

        // Filter by date range
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $query->dateRange($filters['start_date'] ?? null, $filters['end_date'] ?? null);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    private function applySorting(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $sortBy = $filters['sort_by'] ?? 'transaction_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Paginate query results
     */
    public function paginateResults(\Illuminate\Database\Eloquent\Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }

    /**
     * Get transaction summary statistics
     */
    public function getTransactionSummary(int $walletId, array $filters = []): array
    {
        $query = Transaction::where('wallet_id', $walletId);
        $query = $this->applyFilters($query, $filters);

        $deposits = (clone $query)->where('type', 'deposit')->sum('amount');
        $withdrawals = (clone $query)->where('type', 'withdrawal')->sum('amount');
        $count = $query->count();

        return [
            'total_transactions' => $count,
            'total_deposits' => round($deposits, 0),
            'total_withdrawals' => round($withdrawals, 0),
            'net_flow' => round($deposits - $withdrawals, 0)
        ];
    }

    /**
     * Extract filters from request
     */
    public function extractFilters(Request $request): array
    {
        return [
            'type' => $request->input('type'),
            'category' => $request->input('category'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'sort_by' => $request->input('sort_by', 'transaction_date'),
            'sort_order' => $request->input('sort_order', 'desc'),
            'per_page' => $request->input('per_page', 15),
        ];
    }

    /**
     * Get applied filters for response
     */
    public function getAppliedFilters(array $filters): array
    {
        return [
            'type' => $filters['type'],
            'category' => $filters['category'],
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'sort_by' => $filters['sort_by'],
            'sort_order' => $filters['sort_order'],
            'per_page' => $filters['per_page'],
        ];
    }

    /**
     * Get wallet summary for response
     */
    public function getWalletSummary(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'owner_name' => $wallet->owner_name,
            'current_balance' => $wallet->balance,
            'currency' => $wallet->currency->code ?? null,
        ];
    }
}

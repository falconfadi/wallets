<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransactionListRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Resources\TransactionListResource;
use App\Models\Transaction;
use App\Services\TransactionQueryService;
use App\Services\TransactionService;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService, TransactionQueryService $queryService)
    {
        $this->transactionService = $transactionService;
        $this->queryService = $queryService;
    }

    /**
     * POST /wallets/{id}/deposit
     * Deposit funds with idempotency
     */
    public function deposit(DepositRequest $request, Wallet $wallet): JsonResponse
    {
        try {
            // Get idempotency key from header
            $idempotencyKey = $request->header('Idempotency-Key');
            //Log::info($idempotencyKey);
            // Process deposit
            $result = $this->transactionService->deposit(
                $wallet,
                $request->validated(),
                $idempotencyKey
            );

            // Add idempotency info to response headers
            $headers = [
                'Idempotency-Key' => $result['idempotency_key'],
                'Idempotency-Status' => $result['cached'] ? 'cached' : 'processed',
            ];

            return response()->json(
                $result['response'],
                $result['status_code'],
                $headers
            );

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Invalid request',
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'message' => 'Transaction failed',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * POST /wallets/{id}/withdraw
     * Withdraw funds with idempotency
     */
    public function withdraw(DepositRequest $request, Wallet $wallet): JsonResponse
    {
        try {
            // Get idempotency key from header
            $idempotencyKey = $request->header('Idempotency-Key');
            // Process withdrawal
            $result = $this->transactionService->withdraw(
                $wallet,
                $request->validated(),
                $idempotencyKey
            );

            // Add idempotency info to response headers
            $headers = [
                'Idempotency-Key' => $result['idempotency_key'],
                'Idempotency-Status' => $result['cached'] ? 'cached' : 'processed',
            ];

            return response()->json(
                $result['response'],
                $result['status_code'],
                $headers
            );

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Invalid request',
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'message' => 'Transaction failed',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * GET /wallets/{id}/transactions
     * Return chronological list of all wallet transactions with filters
     */
    public function index(TransactionListRequest $request, Wallet $wallet): JsonResponse
    {
        // Extract filters from request
        $filters = $this->queryService->extractFilters($request);

        // Build and execute query
        $query = $this->queryService->buildQuery($wallet->id, $filters);
        $transactions = $this->queryService->paginateResults($query, $filters['per_page']);

        // Get summaries
        $summary = $this->queryService->getTransactionSummary($wallet->id, $filters);
        $appliedFilters = $this->queryService->getAppliedFilters($filters);
        $walletSummary = $this->queryService->getWalletSummary($wallet);

        return response()->json([
            'message' => 'Transactions retrieved successfully',
            'wallet' => $walletSummary,
            'data' => TransactionListResource::make($transactions),
            'filters' => $appliedFilters,
            'summary' => $summary,
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, TransactionListRequest $request)
    {
        // Filter by transaction type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->ofCategory($request->category);
        }

        // Filter by date range
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, TransactionListRequest $request)
    {
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get applied filters for response
     */
    private function getAppliedFilters(TransactionListRequest $request): array
    {
        return [
            'type' => $request->type,
            'category' => $request->category,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'per_page' => $request->input('per_page', 15),
            'sort_by' => $request->input('sort_by', 'transaction_date'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];
    }

    /**
     * Get transaction summary
     */
    private function getTransactionSummary(int $walletId, TransactionListRequest $request): array
    {
        $query = Transaction::where('wallet_id', $walletId);

        // Apply same filters for summary
        $query = $this->applyFilters($query, $request);

        $deposits = (clone $query)->where('type', 'deposit')->sum('amount');
        $withdrawals = (clone $query)->where('type', 'withdrawal')->sum('amount');
        $count = $query->count();

        return [
            'total_transactions' => $count,
            'total_deposits' => $deposits,
            'total_withdrawals' => $withdrawals,
            'net_flow' => $deposits - $withdrawals,
        ];
    }

}

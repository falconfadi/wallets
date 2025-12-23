<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Services\TransactionService;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
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
     * List wallet transactions
     */
    public function index(Wallet $wallet): JsonResponse
    {
        $transactions = $wallet->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Transactions retrieved successfully',
            'data' => [
                'wallet_id' => $wallet->id,
                'balance' => $wallet->balance,
                'transactions' => $transactions,
            ]
        ]);
    }
}

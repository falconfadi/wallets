<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * POST /transfers
     * Transfer funds between wallets with idempotency
     */
    public function store(TransferRequest $request): JsonResponse
    {
        try {
            // Get idempotency key from header
            $idempotencyKey = $request->header('Idempotency-Key');

            // Process transfer
            $result = $this->transactionService->transfer(
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
                'message' => 'Transfer failed',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * GET /transfers
     * List all transfers (optional filtering)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'wallet_id' => $request->query('wallet_id'),
            'status' => $request->query('status'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
        ];

        $query = \App\Models\Transfer::with(['fromWallet', 'toWallet'])
            ->orderBy('created_at', 'desc');

        // Filter by wallet
        if ($filters['wallet_id']) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_wallet_id', $filters['wallet_id'])
                    ->orWhere('to_wallet_id', $filters['wallet_id']);
            });
        }

        // Filter by status
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if ($filters['from_date']) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if ($filters['to_date']) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $transfers = $query->paginate($request->query('per_page', 15));

        return response()->json([
            'message' => 'Transfers retrieved successfully',
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
                'total_pages' => $transfers->lastPage(),
            ]
        ]);
    }

    /**
     * GET /transfers/{id}
     * Get transfer details
     */
    public function show(string $id): JsonResponse
    {
        // Accept either numeric ID or reference
        $transfer = \App\Models\Transfer::where('id', $id)
            ->orWhere('reference', $id)
            ->with(['fromWallet', 'toWallet'])
            ->first();

        if (!$transfer) {
            return response()->json([
                'message' => 'Transfer not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Transfer retrieved successfully',
            'data' => $transfer
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    /**
     * GET /wallets
     * List all wallets. Optional filters for owner or currency.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'owner' => $request->query('owner'),
            'currency' => $request->query('currency'),
        ];
        Log::info($request->query('owner'));
        $wallets = $this->walletService->listWallets($filters);

        return response()->json([
            'message' => 'Wallets retrieved successfully',
            'data' => WalletResource::collection($wallets),
            'meta' => [
                'total' => $wallets->count(),
                'filters' => array_filter($filters) // Only show applied filters
            ]
        ]);
    }

    /**
     * POST /wallets
     * Create a wallet with owner_name and currency. Starts with zero balance.
     */
    public function store(StoreWalletRequest  $request):JsonResponse
    {
        $wallet = $this->walletService->createWallet($request->validated());
        Log::info("hellos");
        return response()->json([
            'message' => 'Wallet created successfully',
            'data' => new WalletResource($wallet->load('currency'))
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /wallets/{id}
     * Retrieve wallet details including current balance.
     */
    public function show(int $id): JsonResponse
    {
        $wallet = $this->walletService->getWallet($id);

        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Wallet retrieved successfully',
            'data' => new WalletResource($wallet)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wallet $wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wallet $wallet)
    {
        //
    }
}

<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\IdempotencyKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransactionService
{
    /**
     * Process deposit with idempotency
     */
    public function deposit(Wallet $wallet, array $data, string $idempotencyKey = null): array
    {
        // Validate amount is integer
        if ($data['amount'] < 1) {
            throw new \InvalidArgumentException('Amount must be a positive integer');
        }
        // Generate idempotency key if not provided
        if (!$idempotencyKey) {
            $idempotencyKey = IdempotencyKey::generateKey();
        }

        // Validate idempotency key format
        if (!IdempotencyKey::isValidKey($idempotencyKey)) {
            throw new \InvalidArgumentException('Invalid idempotency key format');
        }

        // Create request hash
        $data['category'] = 'deposit';
        $requestHash = $this->createRequestHash($wallet->id, $data);

        // Check if we've already processed this request
        $existingKey = IdempotencyKey::where('key', $idempotencyKey)
            ->where('type', 'deposit')
            ->first();

        if ($existingKey) {
            // Verify it's the same request
            if ($existingKey->request_hash === $requestHash) {
                // Return cached response
                return [
                    'cached' => true,
                    'status_code' => $existingKey->status_code,
                    'response' => $existingKey->response,
                    'idempotency_key' => $existingKey->key,
                ];
            } else {
                // Same key, different request - conflict
                throw new \Exception('Idempotency key conflict: same key used for different request', 409);
            }
        }

        // Process the deposit
        return DB::transaction(function () use ($wallet, $data, $idempotencyKey, $requestHash) {
            // Create transaction
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $data['amount'],
                'type' => 'deposit',
                'description' => $data['description'] ?? null,
                'transaction_date' => now(),
            ]);

            // Update wallet balance
            $wallet->increment('balance', $data['amount']);
            $wallet->refresh();

            // Prepare response
            $response = [
                'message' => 'Deposit successful',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $data['amount'],
                    'new_balance' => round($wallet->balance,0),
                    'transaction_date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                ],
            ];

            // Store idempotency key
            IdempotencyKey::create([
                'key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'type' => 'deposit',
                'resource_id' => $wallet->id,
                'resource_type' => Wallet::class,
                'response' => $response,
                'status_code' => Response::HTTP_OK,
                'expires_at' => now()->addDays(7), // Keep for 7 days
            ]);

            return [
                'cached' => false,
                'status_code' => Response::HTTP_OK,
                'response' => $response,
                'idempotency_key' => $idempotencyKey,
            ];
        });
    }

    /**
     * Create a hash of the request for idempotency checking
     */
    private function createRequestHash(int $walletId, array $data): string
    {
        $requestData = [
            'wallet_id' => $walletId,
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'deposit',
        ];

        // Sort keys to ensure consistent ordering
        ksort($requestData);

        return hash('sha256', json_encode($requestData));
    }

    /**
     * Process withdrawal
     */
    public function withdraw(Wallet $wallet, array $data, string $idempotencyKey = null): array
    {
        // Validate amount is integer
        if ( $data['amount'] < 1) {
            throw new \InvalidArgumentException('Amount must be a positive integer');
        }
        // Validate sufficient funds
        if ($wallet->balance < $data['amount']) {
            throw new \Exception('Insufficient funds', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        //Log::info($idempotencyKey);
        // Generate idempotency key if not provided
        if (!$idempotencyKey) {
            $idempotencyKey = IdempotencyKey::generateKey();
        }

        // Validate idempotency key format
        if (!IdempotencyKey::isValidKey($idempotencyKey)) {
            throw new \InvalidArgumentException('Invalid idempotency key format');
        }

        // Create request hash
        $data['category'] = 'withdrawal';
        $requestHash = $this->createRequestHash($wallet->id, $data);

        // Check if we've already processed this request
        $existingKey = IdempotencyKey::where('key', $idempotencyKey)
            ->where('type', 'withdrawal')
            ->first();

        if ($existingKey) {
            // Verify it's the same request
            if ($existingKey->request_hash === $requestHash) {
                // Return cached response
                return [
                    'cached' => true,
                    'status_code' => $existingKey->status_code,
                    'response' => $existingKey->response,
                    'idempotency_key' => $existingKey->key,
                ];
            } else {
                // Same key, different request - conflict
                throw new \Exception('Idempotency key conflict: same key used for different request', 409);
            }
        }

        // Process the withdrawal
        return DB::transaction(function () use ($wallet, $data, $idempotencyKey, $requestHash) {
            // Create transaction
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $data['amount'],
                'type' => 'withdrawal',
                'description' => $data['description'] ?? null,
                'transaction_date' => now(),
            ]);

            // Update wallet balance
            $wallet->decrement('balance', $data['amount']);
            $wallet->refresh();

            // Prepare response
            $response = [
                'message' => 'Withdrawal successful',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $data['amount'],
                    'new_balance' => $wallet->balance,
                    'transaction_date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                ],
            ];

            // Store idempotency key
            IdempotencyKey::create([
                'key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'type' => 'withdrawal',
                'resource_id' => $wallet->id,
                'resource_type' => Wallet::class,
                'response' => $response,
                'status_code' => Response::HTTP_OK,
                'expires_at' => now()->addDays(7),
            ]);

            return [
                'cached' => false,
                'status_code' => Response::HTTP_OK,
                'response' => $response,
                'idempotency_key' => $idempotencyKey,
            ];
        });
    }

    /**
     * Process transfer between wallets with idempotency
     */
    public function transfer(array $data, string $idempotencyKey = null): array
    {
        // Validate wallets exist
        $fromWallet = Wallet::findOrFail($data['from_wallet_id']);
        $toWallet = Wallet::findOrFail($data['to_wallet_id']);

        // Check sufficient balance
        if ($fromWallet->balance < $data['amount']) {
            throw new \Exception('Insufficient funds in source wallet', 422);
        }

        // Generate idempotency key if not provided
        if (!$idempotencyKey) {
            $idempotencyKey = IdempotencyKey::generateKey();
        }

        // Validate idempotency key format
        if (!IdempotencyKey::isValidKey($idempotencyKey)) {
            throw new \InvalidArgumentException('Invalid idempotency key format');
        }

        // Create request hash
        $requestHash = $this->createTransferRequestHash($data);

        // Check if we've already processed this request
        $existingKey = IdempotencyKey::where('key', $idempotencyKey)
            ->where('type', 'transfer')
            ->first();

        if ($existingKey) {
            // Verify it's the same request
            if ($existingKey->request_hash === $requestHash) {
                // Return cached response
                return [
                    'cached' => true,
                    'status_code' => $existingKey->status_code,
                    'response' => $existingKey->response,
                    'idempotency_key' => $existingKey->key,
                ];
            } else {
                // Same key, different request - conflict
                throw new \Exception('Idempotency key conflict: same key used for different request', 409);
            }
        }

        // Process the transfer atomically
        return DB::transaction(function () use ($fromWallet, $toWallet, $data, $idempotencyKey, $requestHash) {
            // Create transfer record
            $transfer = Transfer::create([
                'reference' => Transfer::generateReference(),
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $data['amount']
            ]);

            // Create debit transaction
            $debitTransaction = Transaction::create([
                'wallet_id' => $fromWallet->id,
                'amount' => $data['amount'],
                'type' => 'withdrawal',
                'category' => 'transfer',
                'description' => $data['description'] ?? "Transfer to Wallet #{$toWallet->id}",
                'transaction_date' => now(),
                'reference' => $transfer->reference,
            ]);

            // Create credit transaction
            $creditTransaction = Transaction::create([
                'wallet_id' => $toWallet->id,
                'amount' => $data['amount'],
                'type' => 'deposit',
                'category' => 'transfer',
                'description' => $data['description'] ?? "Transfer from Wallet #{$fromWallet->id}",
                'transaction_date' => now(),
                'reference' => $transfer->reference,
            ]);

            // Update wallet balances
            $fromWallet->decrement('balance', $data['amount']);
            $toWallet->increment('balance', $data['amount']);

            // Refresh wallets to get updated balances
            $fromWallet->refresh();
            $toWallet->refresh();

            // Prepare response
            $response = [
                'message' => 'Transfer completed successfully',
                'data' => [
                    'transfer_id' => $transfer->id,
                    'reference' => $transfer->reference,
                    'from_wallet' => [
                        'id' => $fromWallet->id,
                        'name' => $fromWallet->name,
                        'owner_name' => $fromWallet->owner_name,
                        'new_balance' => $fromWallet->balance,
                    ],
                    'to_wallet' => [
                        'id' => $toWallet->id,
                        'name' => $toWallet->name,
                        'owner_name' => $toWallet->owner_name,
                        'new_balance' => $toWallet->balance,
                    ],
                    'amount' => $data['amount'],
                    'transactions' => [
                        'debit_id' => $debitTransaction->id,
                        'credit_id' => $creditTransaction->id,
                    ],
                    'completed_at' => $transfer->created_at->format('Y-m-d H:i:s'),
                ],
            ];

            // Store idempotency key
            IdempotencyKey::create([
                'key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'type' => 'transfer',
                'resource_id' => $transfer->id,
                'resource_type' => Transfer::class,
                'response' => $response,
                'status_code' => Response::HTTP_OK,
                'expires_at' => now()->addDays(7),
            ]);

            return [
                'cached' => false,
                'status_code' => Response::HTTP_OK,
                'response' => $response,
                'idempotency_key' => $idempotencyKey,
            ];
        });
    }

    /**
     * Create a hash of transfer request for idempotency checking
     */
    private function createTransferRequestHash(array $data): string
    {
        $requestData = [
            'from_wallet_id' => $data['from_wallet_id'],
            'to_wallet_id' => $data['to_wallet_id'],
            'amount' => $data['amount'],
        ];

        // Sort keys to ensure consistent ordering
        ksort($requestData);

        return hash('sha256', json_encode($requestData));
    }

    /**
     * Get transfer history for a wallet
     */
//    public function getWalletTransfers(int $walletId, array $filters = []): \Illuminate\Support\Collection
//    {
//        $query = Transfer::with(['fromWallet', 'toWallet'])
//            ->where(function ($q) use ($walletId) {
//                $q->where('from_wallet_id', $walletId)
//                    ->orWhere('to_wallet_id', $walletId);
//            })
//            ->orderBy('created_at', 'desc');
//
//        // Apply status filter if provided
//        if (!empty($filters['status'])) {
//            $query->where('status', $filters['status']);
//        }
//
//        return $query->get();
//    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => ucfirst($this->type),
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),

            'wallet' => new WalletResource($this->whenLoaded('wallet')),
        ];
    }
}

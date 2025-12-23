<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'failure_reason' => $this->failure_reason,
            'from_wallet' => new WalletResource($this->whenLoaded('fromWallet')),
            'to_wallet' => new WalletResource($this->whenLoaded('toWallet')),

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

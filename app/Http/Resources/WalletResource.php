<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_name' => $this->owner_name,
            'balance' => $this->balance,
            'description' => $this->description ?? '',
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'currency_code' => $this->currency->code ?? null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'links' => [
                'self' => route('wallets.show', $this->id),
            ]
        ];

    }
}

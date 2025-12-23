<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'payee',
        'description',
        'transaction_date',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'transaction_date' => 'date',
    ];

    // Accessor/mutator for amount
    public function getAmountAttribute($value)
    {
        return (int) $value;
    }
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round($value, 0);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    // Scope for filtering by type
    public function scopeOfType($query, $type)
    {
        if ($type) {
            return $query->where('type', $type);
        }
        return $query;
    }

    // Scope for filtering by date range
    public function scopeDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        return $query;
    }


    // Get related wallet info for transfers
    public function getRelatedWalletAttribute()
    {
        if ($this->transfer) {
            if ($this->type === 'withdrawal') {
                // For debit, show destination wallet
                return [
                    'id' => $this->transfer->to_wallet_id,
                    'name' => $this->transfer->toWallet->name ?? null,
                    'owner_name' => $this->transfer->toWallet->owner_name ?? null,
                ];
            } else {
                // For credit, show source wallet
                return [
                    'id' => $this->transfer->from_wallet_id,
                    'name' => $this->transfer->fromWallet->name ?? null,
                    'owner_name' => $this->transfer->fromWallet->owner_name ?? null,
                ];
            }
        }

        return null;
    }
}

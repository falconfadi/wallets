<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'from_wallet_id',
        'to_wallet_id',
        'amount',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    // Relationship with source wallet
    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    // Relationship with destination wallet
    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    // Generate unique reference
    public static function generateReference(): string
    {
        return 'TRF-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    // Check if transfer is to self
    public function isSelfTransfer(): bool
    {
        return $this->from_wallet_id === $this->to_wallet_id;
    }
}

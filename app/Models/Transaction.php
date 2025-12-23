<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    // Optional accessor/mutator for amount
    public function getAmountAttribute($value)
    {
        return (float) $value;
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round($value, 2);
    }
}

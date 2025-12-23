<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'owner_name',
        'currency_id',
        'balance',
        'description',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Accessor/mutator for balance
    public function getAmountAttribute($value)
    {
        return (int) $value;
    }
    public function setAmountAttribute($value)
    {
        $this->attributes['balance'] = round($value, 0);
    }

    // Scope for filtering by owner
    public function scopeByOwner($query, $ownerName)
    {
        if ($ownerName) {
            return $query->where('owner_name', 'like', "%{$ownerName}%");
        }
        return $query;
    }

    // Scope for filtering by currency
    public function scopeByCurrency($query, $currencyCode)
    {
        if ($currencyCode) {
            return $query->whereHas('currency', function ($q) use ($currencyCode) {
                $q->where('code', $currencyCode);
            });
        }
        return $query;
    }
}

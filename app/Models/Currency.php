<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function scopePopular($query)
    {
        return $query->whereIn('code', ['USD', 'EUR', 'GBP', 'SYP']);
    }

    public function format($amount): string
    {
        return $this->symbol . number_format(
                $amount
            );
    }
}

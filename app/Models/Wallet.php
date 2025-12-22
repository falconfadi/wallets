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


    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    //  Mutator to round to 2 decimals
    public function setBalanceAttribute($value)
    {
        $this->attributes['balance'] = round($value, 0);
    }
}

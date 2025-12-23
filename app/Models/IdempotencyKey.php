<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'request_hash',
        'type',
        'resource_id',
        'resource_type',
        'response',
        'status_code',
        'expires_at',
    ];

    protected $casts = [
        'response' => 'array',
        'expires_at' => 'datetime',
    ];

    public static function generateKey(): string
    {
        return (string) Str::uuid();
    }

    public static function isValidKey(string $key): bool
    {
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $key);
    }

    public function resource()
    {
        return $this->morphTo();
    }
}

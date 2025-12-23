<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
//    public function __invoke(): JsonResponse
//    {
//        return response()->json([
//            'status' => 'ok'
//        ]);
//    }

    public function __invoke(): JsonResponse
    {
        $status = 'ok';
        $httpCode = 200;
        $checks = [];

        try {
            // Check database connection
            DB::connection()->getPdo();
            $checks['database'] = 'connected';
        } catch (\Exception $e) {
            $status = 'error';
            $httpCode = 503; // Service Unavailable
            $checks['database'] = 'disconnected';
        }

        // Optional: Check cache if using Redis/Memcached
        try {
            Cache::store()->put('health_check', 'ok', 1);
            $checks['cache'] = 'connected';
        } catch (\Exception $e) {
            // Cache might not be critical, so we don't fail the whole health check
            $checks['cache'] = 'disconnected';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toDateTimeString(),
            'service' => 'Wallet API',
            'version' => '1.0.0',
            'checks' => $checks,
        ], $httpCode);
    }
}

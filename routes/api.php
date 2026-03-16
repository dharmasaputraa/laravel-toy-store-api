<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

Route::get('/health', function () {
    $services = [
        'database' => false,
        'redis' => false,
        'storage' => false,
    ];

    try {
        DB::connection()->getPdo();
        $services['database'] = true;
    } catch (\Exception $e) {
    }
    try {
        Redis::connection()->ping();
        $services['redis'] = true;
    } catch (\Exception $e) {
    }
    try {
        Storage::disk('s3')->put('health.txt', 'ok');
        Storage::disk('s3')->delete('health.txt');
        $services['storage'] = true;
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Storage Health Check Failed: ' . $e->getMessage());
    }

    $isHealthy = !in_array(false, $services, true);

    return response()->json([
        'success' => $isHealthy,
        'message' => $isHealthy ? 'System is operational' : 'System degradation detected',
        'data' => [
            'app_version' => app()->version(),
            'services' => $services,
        ]
    ], $isHealthy ? 200 : 503);
});

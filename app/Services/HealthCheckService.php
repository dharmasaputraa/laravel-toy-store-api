<?php

namespace App\Services;

use App\DTOs\Health\HealthData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    public function basic(): HealthData
    {
        return HealthData::make(
            true,
            'OK',
            [
                'app' => config('app.name'),
                'env' => app()->environment(),
                'response_time_ms' => $this->responseTime(),
            ],
            200
        );
    }

    public function full(): HealthData
    {
        $start = microtime(true);

        $services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'object_storage' => $this->checkObjectStorage(),
        ];

        $isHealthy = !in_array(false, $services, true);

        return HealthData::make(
            $isHealthy,
            $isHealthy ? 'System is operational' : 'System degradation detected',
            [
                'app' => config('app.name'),
                'env' => app()->environment(),
                'app_version' => app()->version(),
                'services' => $services,
                'response_time_ms' => round((microtime(true) - $start) * 1000, 2),
            ],
            $isHealthy ? 200 : 503
        );
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            Log::error('DB Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Throwable $e) {
            Log::error('Redis Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkObjectStorage(): bool
    {
        try {
            Storage::disk('s3')->put('health.txt', 'ok');
            Storage::disk('s3')->delete('health.txt');
            return true;
        } catch (\Throwable $e) {
            Log::error('Object Storage Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function responseTime(): float
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        return round((microtime(true) - $startTime) * 1000, 2);
    }
}

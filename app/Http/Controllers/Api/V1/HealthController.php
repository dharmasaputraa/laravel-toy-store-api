<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\V1\HealthResource;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends BaseApiController
{
    public function __construct(
        private HealthCheckService $healthService
    ) {}

    public function basic(): JsonResponse
    {
        $result = $this->healthService->basic();

        return (new HealthResource($result))
            ->response()
            ->setStatusCode($result->statusCode);
    }

    public function full(): JsonResponse
    {
        $result = $this->healthService->full();

        return (new HealthResource($result))
            ->response()
            ->setStatusCode($result->statusCode);
    }
}

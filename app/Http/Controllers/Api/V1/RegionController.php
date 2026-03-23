<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\V1\RegionResource;
use App\Services\RegionService;
use Illuminate\Http\JsonResponse;

class RegionController extends BaseApiController
{
    public function __construct(
        protected RegionService $regionService
    ) {}

    public function index(): JsonResponse
    {
        $provinces = $this->regionService->getProvinces();

        return $this->successResponse(
            RegionResource::collection($provinces)
        );
    }

    public function cities(string $code): JsonResponse
    {
        $cities = $this->regionService->getCitiesByProvince($code);

        return $this->successResponse(
            RegionResource::collection($cities)
        );
    }
}

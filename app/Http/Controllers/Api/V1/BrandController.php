<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\Brand\StoreBrandRequest;
use App\Http\Requests\V1\Brand\UpdateBrandRequest;
use App\Http\Requests\V1\Brand\UpdateBrandStatusRequest;
use App\Http\Requests\V1\Brand\UploadBrandLogoRequest;
use App\Http\Resources\V1\Brand\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use App\DTOs\Brand\BrandData;
use Illuminate\Http\JsonResponse;

class BrandController extends BaseApiController
{
    public function __construct(
        protected BrandService $service
    ) {}

    public function index(): JsonResponse
    {
        $brands = $this->service->getAll();

        return $this->successResponse(
            BrandResource::collection($brands),
            'Brands fetched'
        );
    }

    public function show(Brand $brand): JsonResponse
    {
        return $this->successResponse(
            new BrandResource($brand),
            'Brand fetched'
        );
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->service->store(
            BrandData::fromRequest($request)
        );

        return $this->successResponse(
            new BrandResource($brand),
            'Brand created',
            201
        );
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $brand = $this->service->update(
            $brand,
            BrandData::fromRequest($request)
        );

        return $this->successResponse(
            new BrandResource($brand),
            'Brand updated'
        );
    }

    public function updateStatus(UpdateBrandStatusRequest $request, Brand $brand): JsonResponse
    {
        $brand = $this->service->updateStatus(
            $brand,
            $request->is_active
        );

        return $this->successResponse(
            new BrandResource($brand),
            'Brand status updated'
        );
    }

    public function updateLogo(UploadBrandLogoRequest $request, Brand $brand): JsonResponse
    {
        $brand = $this->service->updateLogo(
            $brand,
            $request->file('logo')
        );

        return $this->successResponse(
            new BrandResource($brand),
            'Brand logo updated'
        );
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $this->service->delete($brand);

        return $this->successResponse(
            null,
            'Brand deleted'
        );
    }
}

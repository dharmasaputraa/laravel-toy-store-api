<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\Brand\StoreBrandRequest;
use App\Http\Requests\V1\Brand\UpdateBrandRequest;
use App\Http\Requests\V1\Brand\UpdateBrandStatusRequest;
use App\Http\Requests\V1\Brand\UploadBrandLogoRequest;
use App\Http\Resources\V1\Brand\BrandResource;
use App\Http\Resources\V1\Product\ProductListResource;
use App\Models\Brand;
use App\Services\BrandService;
use App\DTOs\Brand\BrandData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends BaseApiController
{
    public function __construct(
        protected BrandService $service
    ) {}

    /**
     * GET /brands - List all brands with pagination and sorting (Public)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $sort = $request->input('sort');

        $brands = $this->service->getAll($perPage, $sort);

        return $this->successResponse(
            BrandResource::collection($brands),
            'Brands fetched'
        );
    }

    /**
     * GET /brands/{brand} - Show single brand with optional includes (Public)
     */
    public function show(Request $request, Brand $brand): JsonResponse
    {
        // Parse includes (comma-separated, supports nested)
        $includes = explode(',', $request->input('include', ''));
        $includeProducts = in_array('products', $includes);

        // Parse nested includes (e.g., products.category, products.brand)
        $nestedIncludes = array_filter($includes, function ($include) {
            return str_starts_with($include, 'products.');
        });

        // Validate and limit products (default 5, max 20)
        $productsLimit = min((int) $request->input('products_limit', 5), 20);

        $brand = $this->service->getById(
            $brand->id,
            $includeProducts,
            $nestedIncludes,
            $productsLimit
        );

        return $this->successResponse(
            new BrandResource($brand),
            'Brand fetched'
        );
    }

    /**
     * GET /brands/{brand}/products - Get paginated products for a brand (Public)
     */
    public function products(Request $request, Brand $brand): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $sort = $request->input('sort');
        $includes = explode(',', $request->input('include', ''));

        $products = $this->service->getProducts(
            $brand->id,
            $perPage,
            $sort,
            $includes
        );

        $resource = ProductListResource::collection($products);

        return response()->json([
            'success' => true,
            'message' => 'Products fetched',
            'data' => [
                'data' => $resource->collection->toArray($request),
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'from' => $products->firstItem(),
                    'last_page' => $products->lastPage(),
                    'links' => $products->getUrlRange(1, $products->lastPage()),
                    'path' => $products->path(),
                    'per_page' => $products->perPage(),
                    'to' => $products->lastItem(),
                    'total' => $products->total(),
                ],
            ],
        ]);
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

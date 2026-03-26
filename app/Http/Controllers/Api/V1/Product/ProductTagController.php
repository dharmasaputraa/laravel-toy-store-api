<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\ProductTag\StoreProductTagRequest;
use App\Http\Requests\V1\ProductTag\UpdateProductTagRequest;
use App\Http\Resources\V1\ProductTag\ProductTagResource;
use App\Http\Resources\V1\Product\ProductListResource;
use App\Models\ProductTag;
use App\Services\ProductTagService;
use App\DTOs\ProductTag\ProductTagData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTagController extends BaseApiController
{
    public function __construct(
        protected ProductTagService $service
    ) {}

    /**
     * GET /api/tags - List all product tags with pagination and sorting
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $sort = $request->input('sort');

        $tags = $this->service->getAll($perPage, $sort);

        return $this->successResponse(
            ProductTagResource::collection($tags),
            'Product tags fetched'
        );
    }

    /**
     * GET /api/tags/{tag} - Show single product tag with optional includes
     */
    public function show(Request $request, ProductTag $productTag): JsonResponse
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

        $tag = $this->service->getById(
            $productTag->id,
            $includeProducts,
            $nestedIncludes,
            $productsLimit
        );

        return $this->successResponse(
            new ProductTagResource($tag),
            'Product tag fetched'
        );
    }

    /**
     * GET /api/tags/{tag}/products - Get paginated products for a tag
     */
    public function products(Request $request, ProductTag $productTag): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $sort = $request->input('sort');
        $includes = explode(',', $request->input('include', ''));

        $products = $this->service->getProducts(
            $productTag->id,
            $perPage,
            $sort,
            $includes
        );

        return $this->successResponse(
            ProductListResource::collection($products),
            'Products fetched'
        );
    }

    /**
     * POST /api/tags - Create new product tag (admin only)
     */
    public function store(StoreProductTagRequest $request): JsonResponse
    {
        $tag = $this->service->store(
            ProductTagData::fromRequest($request)
        );

        return $this->successResponse(
            new ProductTagResource($tag),
            'Product tag created',
            201
        );
    }

    /**
     * PATCH /api/tags/{tag} - Update product tag (admin only)
     */
    public function update(UpdateProductTagRequest $request, ProductTag $productTag): JsonResponse
    {
        $tag = $this->service->update(
            $productTag,
            ProductTagData::fromRequest($request)
        );

        return $this->successResponse(
            new ProductTagResource($tag),
            'Product tag updated'
        );
    }

    /**
     * DELETE /api/tags/{tag} - Delete product tag (admin only)
     */
    public function destroy(ProductTag $productTag): JsonResponse
    {
        $this->service->delete($productTag);

        return $this->successResponse(
            null,
            'Product tag deleted'
        );
    }
}

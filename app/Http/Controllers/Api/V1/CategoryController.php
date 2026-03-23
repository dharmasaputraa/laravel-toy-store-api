<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\Category\StoreCategoryRequest;
use App\Http\Requests\V1\Category\UpdateCategoryRequest;
use App\Http\Requests\V1\Category\UpdateCategoryParentRequest;
use App\Http\Requests\V1\Category\UpdateCategoryStatusRequest;
use App\Http\Requests\V1\Category\UploadCategoryImageRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\DTOs\Category\CategoryData;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseApiController
{
    public function __construct(
        protected CategoryService $service
    ) {}

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->store(
            CategoryData::fromRequest($request)
        );

        return $this->successResponse(
            new CategoryResource($category),
            'Category created',
            201
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->service->update(
            $category,
            CategoryData::fromRequest($request)
        );

        return $this->successResponse(
            new CategoryResource($category),
            'Category updated'
        );
    }

    public function updateParent(UpdateCategoryParentRequest $request, Category $category): JsonResponse
    {
        $category = $this->service->updateParent(
            $category,
            $request->parent_id
        );

        return $this->successResponse(
            new CategoryResource($category),
            'Category parent updated'
        );
    }

    public function updateStatus(UpdateCategoryStatusRequest $request, Category $category): JsonResponse
    {
        $category = $this->service->updateStatus(
            $category,
            $request->is_active
        );

        return $this->successResponse(
            new CategoryResource($category),
            'Category status updated'
        );
    }

    public function updateImage(UploadCategoryImageRequest $request, Category $category): JsonResponse
    {
        $category = $this->service->updateImage(
            $category,
            $request->file('image')
        );

        return $this->successResponse(
            new CategoryResource($category),
            'Category image updated'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->service->delete($category);

        return $this->successResponse(
            null,
            'Category deleted'
        );
    }

    public function tree(): JsonResponse
    {
        $categories = $this->service->getTree();

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Category tree fetched'
        );
    }
}

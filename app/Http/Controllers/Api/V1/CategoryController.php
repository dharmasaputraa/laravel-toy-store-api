<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CategoryDTO;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(): JsonResponse
    {
        // Mengambil bentuk tree (hierarki)
        $categories = $this->categoryService->getCategoryTree();

        return response()->json([
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        // 1. Validasi -> DTO
        $dto = CategoryDTO::fromRequest($request);

        // 2. Eksekusi Service
        $category = $this->categoryService->createCategory($dto);

        // 3. Kembalikan Response
        return response()->json([
            'message' => 'Kategori berhasil dibuat.',
            'data' => new CategoryResource($category)
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('childrenRecursive'); // Load tree spesifik untuk node ini

        return response()->json([
            'data' => new CategoryResource($category)
        ]);
    }

    // /**
    //  * Display a listing of the resource.
    //  */
    // public function index()
    // {
    //     //
    // }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     //
    // }

    /**
     * Display the specified resource.
     */
    // public function show(Category $category)
    // {
    //     //
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
    }
}

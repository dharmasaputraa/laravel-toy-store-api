<?php

namespace App\Services;

use App\DTOs\Brand\BrandData;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandService
{
    public function store(BrandData $data): Brand
    {
        return DB::transaction(function () use ($data) {

            $brand = Brand::create($data->toArray());

            $this->clearCache();

            return $brand;
        });
    }

    public function update(Brand $brand, BrandData $data): Brand
    {
        return DB::transaction(function () use ($brand, $data) {

            $brand->update($data->toArray());

            $this->clearCache();

            return $brand->refresh();
        });
    }

    public function updateStatus(Brand $brand, bool $status): Brand
    {
        $brand->update(['is_active' => $status]);

        $this->clearCache();

        return $brand;
    }

    public function updateLogo(Brand $brand, $file): Brand
    {
        $brand
            ->addMedia($file)
            ->usingFileName(
                Str::slug($brand->slug) . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension()
            )
            ->toMediaCollection('logo');

        Cache::tags(['brands', 'logo'])->flush();

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        DB::transaction(function () use ($brand) {

            $brand->delete();

            $this->clearCache();
        });
    }

    /**
     * Get all brands with pagination and sorting
     */
    public function getAll(int $perPage = 15, ?string $sort = null)
    {
        $cacheKey = 'brands:all:' . $perPage . ':' . ($sort ?? 'default');

        return Cache::tags(['brands'])->remember(
            $cacheKey,
            now()->addDay(),
            function () use ($perPage, $sort) {
                $query = Brand::where('is_active', true)
                    ->withCount('products');

                // Apply sorting
                $query = $this->applySorting($query, $sort);

                return $query->paginate($perPage);
            }
        );
    }

    /**
     * Get brand by ID with optional products include
     */
    public function getById(
        string $id,
        bool $includeProducts = false,
        array $nestedIncludes = [],
        int $productsLimit = 5
    ): Brand {
        $brand = Brand::withCount('products')
            ->where('id', $id)
            ->firstOrFail();

        // Include products if requested
        if ($includeProducts) {
            $productsQuery = $brand->products()
                ->latest()
                ->limit(min($productsLimit, 20));

            // Apply nested includes
            if (in_array('products.category', $nestedIncludes)) {
                $productsQuery->with('category');
            }
            if (in_array('products.brand', $nestedIncludes)) {
                $productsQuery->with('brand');
            }
            if (in_array('products.tags', $nestedIncludes)) {
                $productsQuery->with('tags');
            }
            if (in_array('products.variants', $nestedIncludes)) {
                $productsQuery->with('variants');
            }

            $brand->setRelation('products', $productsQuery->get());
        }

        return $brand;
    }

    /**
     * Get paginated products for a brand
     */
    public function getProducts(
        string $brandId,
        int $perPage = 15,
        ?string $sort = null,
        array $includes = []
    ) {
        $query = Product::where('brand_id', $brandId)
            ->where('is_active', true);

        // Always include category and brand to prevent N+1 queries
        $query->with(['category', 'brand']);

        // Apply additional includes
        if (in_array('tags', $includes)) {
            $query->with('tags');
        }
        if (in_array('variants', $includes)) {
            $query->with('variants');
        }

        // Apply sorting
        $query = $this->applyProductSorting($query, $sort);

        return $query->paginate(min($perPage, 100));
    }

    /**
     * Apply sorting to brand query
     */
    private function applySorting(Builder $query, ?string $sort): Builder
    {
        if (!$sort) {
            return $query->orderBy('name');
        }

        $sortFields = explode(',', $sort);

        foreach ($sortFields as $field) {
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $fieldName = ltrim($field, '-');

            // Validate allowed fields
            if (in_array($fieldName, ['name', 'created_at', 'products_count'])) {
                $query->orderBy($fieldName, $direction);
            }
        }

        // Fallback to name if no valid fields
        if (empty($query->orders)) {
            $query->orderBy('name');
        }

        return $query;
    }

    /**
     * Apply sorting to product query
     */
    private function applyProductSorting(Builder $query, ?string $sort): Builder
    {
        if (!$sort) {
            return $query->orderBy('created_at', 'desc');
        }

        $sortFields = explode(',', $sort);

        foreach ($sortFields as $field) {
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $fieldName = ltrim($field, '-');

            // Validate allowed fields
            if (in_array($fieldName, ['name', 'price', 'created_at', 'sku'])) {
                $query->orderBy($fieldName, $direction);
            }
        }

        // Fallback to created_at desc if no valid fields
        if (empty($query->orders)) {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    private function clearCache(): void
    {
        Cache::tags(['brands'])->flush();
    }
}

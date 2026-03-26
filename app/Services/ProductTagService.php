<?php

namespace App\Services;

use App\DTOs\ProductTag\ProductTagData;
use App\Models\ProductTag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductTagService
{
    protected array $allowedSortFields = ['name', 'created_at', 'updated_at', 'products_count'];

    public function store(ProductTagData $data): ProductTag
    {
        return DB::transaction(function () use ($data) {
            $tag = ProductTag::create($data->toArray());
            $this->clearCache();
            return $tag;
        });
    }

    public function update(ProductTag $tag, ProductTagData $data): ProductTag
    {
        return DB::transaction(function () use ($tag, $data) {
            $tag->update($data->toArray());
            $this->clearCache();
            return $tag->refresh();
        });
    }

    public function delete(ProductTag $tag): void
    {
        DB::transaction(function () use ($tag) {
            $tag->delete();
            $this->clearCache();
        });
    }

    /**
     * Get all product tags with pagination and sorting
     */
    public function getAll(int $perPage = 15, ?string $sort = null)
    {
        $cacheKey = 'product-tags:page:' . request()->get('page', 1) .
            ':per:' . $perPage .
            ':sort:' . $sort;

        return Cache::tags(['product-tags'])->remember(
            $cacheKey,
            now()->addDay(),
            function () use ($perPage, $sort) {
                $query = ProductTag::withCount('products');

                // Apply sorting
                if ($sort) {
                    $this->applySorting($query, $sort, $this->allowedSortFields);
                } else {
                    $query->orderBy('name');
                }

                return $query->paginate($perPage);
            }
        );
    }

    /**
     * Get single product tag with optional products (limited)
     */
    public function getById(
        string $id,
        bool $includeProducts = false,
        array $nestedIncludes = [],
        int $productsLimit = 5
    ): ProductTag {
        $query = ProductTag::where('id', $id);

        if ($includeProducts) {
            $eagerLoad = [
                'products' => function ($query) use ($productsLimit) {
                    $query->select('id', 'name', 'slug', 'category_id', 'brand_id')
                        ->latest()
                        ->limit($productsLimit);
                }
            ];

            // Add nested includes if present
            if (in_array('products.category', $nestedIncludes)) {
                $eagerLoad['products.category'] = function ($query) {
                    $query->select('id', 'name', 'slug');
                };
            }

            if (in_array('products.brand', $nestedIncludes)) {
                $eagerLoad['products.brand'] = function ($query) {
                    $query->select('id', 'name', 'slug', 'logo');
                };
            }

            $query->with($eagerLoad);
        }

        return $query->firstOrFail();
    }

    /**
     * Get paginated products for a tag with sorting and includes
     */
    public function getProducts(
        string $tagId,
        int $perPage = 15,
        ?string $sort = null,
        array $includes = []
    ) {
        $tag = ProductTag::findOrFail($tagId);

        $query = $tag->products();

        // Minimal eager loading by default
        $query->with(['category', 'brand']);

        // Add extra includes if specified
        if (in_array('tags', $includes)) {
            $query->with('tags');
        }

        if (in_array('variants', $includes)) {
            $query->with(['variants' => function ($q) {
                $q->select('id', 'product_id', 'name', 'price', 'is_active');
            }]);
        }

        // Apply sorting
        $productSortFields = ['name', 'created_at', 'updated_at'];
        if ($sort) {
            $this->applySorting($query, $sort, $productSortFields);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting($query, string $sort, array $allowedFields): void
    {
        $sortFields = explode(',', $sort);

        foreach ($sortFields as $field) {
            $direction = 'asc';

            if (str_starts_with($field, '-')) {
                $field = substr($field, 1);
                $direction = 'desc';
            }

            if (in_array($field, $allowedFields)) {
                $query->orderBy($field, $direction);
            }
        }
    }

    /**
     * Clear cache
     */
    private function clearCache(): void
    {
        Cache::tags(['product-tags'])->flush();
    }
}

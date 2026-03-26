<?php

namespace App\Services;

use App\DTOs\Category\CategoryData;
use App\Exceptions\CircularCategoryException;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    public function store(CategoryData $data): Category
    {
        return DB::transaction(function () use ($data) {

            $category = Category::create($data->toArray());

            $this->clearCache();

            return $category;
        });
    }

    public function update(Category $category, CategoryData $data): Category
    {
        return DB::transaction(function () use ($category, $data) {

            $category->update($data->toArray());

            $this->clearCache();

            return $category->refresh();
        });
    }

    public function updateParent(Category $category, ?string $parentId): Category
    {
        return DB::transaction(function () use ($category, $parentId) {

            if ($this->isCircular($category, $parentId)) {
                throw new CircularCategoryException();
            }

            $category->update([
                'parent_id' => $parentId
            ]);

            $this->clearCache();

            return $category->refresh();
        });
    }

    public function updateStatus(Category $category, bool $status): Category
    {
        $category->update(['is_active' => $status]);

        $this->clearCache();

        return $category;
    }

    public function updateImage(Category $category, $file): Category
    {
        $category
            ->addMedia($file)
            ->usingFileName(
                Str::slug($category->slug) . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension()
            )
            ->toMediaCollection('image');

        Cache::tags(['categories', 'image'])->flush();

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {

            $category->delete();

            $this->clearCache();
        });
    }

    public function getTree(?string $sort = null)
    {
        $cacheKey = 'category:tree:' . ($sort ?? 'default');

        return Cache::tags(['categories', 'tree'])->remember(
            $cacheKey,
            now()->addDay(),
            function () use ($sort) {
                $query = Category::with('childrenRecursive')
                    ->whereNull('parent_id')
                    ->withCount('products');

                // Apply sorting to root level
                $query = $this->applySorting($query, $sort);

                return $query->get();
            }
        );
    }

    private function clearCache(): void
    {
        Cache::tags(['categories'])->flush();
    }

    private function isCircular(Category $category, ?string $parentId): bool
    {
        if (!$parentId) return false;

        if ($category->id === $parentId) return true;

        $parent = Category::find($parentId);

        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Apply sorting to category query
     */
    private function applySorting(Builder $query, ?string $sort): Builder
    {
        if (!$sort) {
            return $query->orderBy('sort_order');
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

        // Fallback to sort_order if no valid fields
        if (empty($query->orders)) {
            $query->orderBy('sort_order');
        }

        return $query;
    }
}

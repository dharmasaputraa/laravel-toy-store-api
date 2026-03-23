<?php

namespace App\Services;

use App\DTOs\Category\CategoryData;
use App\Exceptions\CircularCategoryException;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            ->toMediaCollection('image', 's3');

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

    public function getTree()
    {
        return Cache::tags(['categories', 'tree'])->remember(
            'category:tree',
            now()->addDay(),
            function () {
                return Category::with('childrenRecursive')
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get();
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
}

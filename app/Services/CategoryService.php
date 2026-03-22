<?php

namespace App\Services;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function createCategory(CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto) {
            $category = new Category();
            $category->name = $dto->name;
            $category->slug = $this->generateUniqueSlug($dto->name);
            $category->parent_id = $dto->parentId;
            $category->description = $dto->description;
            $category->sort_order = $dto->sortOrder;
            $category->is_active = $dto->isActive;

            // Logic upload image bisa ditaruh di sini atau di FileUploadService terpisah
            if ($dto->image) {
                $category->image = $dto->image;
            }

            $category->save();

            return $category;
        });
    }

    public function updateCategory(Category $category, CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($category, $dto) {
            // Cek circular dependency: Category tidak boleh menjadi child dari dirinya sendiri
            if ($dto->parentId === $category->id) {
                throw new \InvalidArgumentException('Kategori tidak boleh menjadi parent untuk dirinya sendiri.');
            }

            $category->name = $dto->name;
            // Update slug jika nama berubah
            if ($category->isDirty('name')) {
                $category->slug = $this->generateUniqueSlug($dto->name, $category->id);
            }

            $category->parent_id = $dto->parentId;
            $category->description = $dto->description;
            $category->sort_order = $dto->sortOrder;
            $category->is_active = $dto->isActive;

            if ($dto->image) {
                $category->image = $dto->image;
            }

            $category->save();

            return $category;
        });
    }

    // Mendapatkan struktur tree/hierarki penuh
    public function getCategoryTree()
    {
        return Category::whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->get();
    }

    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        $query = Category::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $query = Category::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $count++;
        }

        return $slug;
    }
}

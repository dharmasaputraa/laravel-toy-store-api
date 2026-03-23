<?php

namespace App\Policies\Category;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Public: semua orang bisa lihat list category
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Public: semua orang bisa lihat detail category
     */
    public function view(?User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Butuh permission
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_category');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_category');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_category');
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_category');
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_category');
    }
}

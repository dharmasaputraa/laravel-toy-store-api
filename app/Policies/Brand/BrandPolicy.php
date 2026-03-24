<?php

namespace App\Policies\Brand;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    /**
     * Public: semua orang bisa lihat list brand
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Public: semua orang bisa lihat detail brand
     */
    public function view(?User $user, Brand $brand): bool
    {
        return true;
    }

    /**
     * Butuh permission
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_brand');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->hasPermission('manage_brand');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->hasPermission('manage_brand');
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $user->hasPermission('manage_brand');
    }

    public function forceDelete(User $user, Brand $brand): bool
    {
        return $user->hasPermission('manage_brand');
    }
}

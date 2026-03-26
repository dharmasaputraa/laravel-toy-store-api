<?php

namespace App\Policies\ProductTag;

use App\Models\ProductTag;
use App\Models\User;

class ProductTagPolicy
{
    /**
     * Public: everyone can view list of product tags
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Public: everyone can view single product tag
     */
    public function view(?User $user, ProductTag $productTag): bool
    {
        return true;
    }

    /**
     * Butuh permission
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_product_tag');
    }

    public function update(User $user, ProductTag $productTag): bool
    {
        return $user->hasPermission('manage_product_tag');
    }

    public function delete(User $user, ProductTag $productTag): bool
    {
        return $user->hasPermission('manage_product_tag');
    }

    public function restore(User $user, ProductTag $productTag): bool
    {
        return $user->hasPermission('manage_product_tag');
    }

    public function forceDelete(User $user, ProductTag $productTag): bool
    {
        return $user->hasPermission('manage_product_tag');
    }
}
